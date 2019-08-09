<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Services;

use Ramsey\Uuid\Uuid;
use RevisionTen\CQRS\Exception\InterfaceException;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;
use RevisionTen\CQRS\Message\Message;

class CommandBus
{
    /**
     * @var EventBus
     */
    private $eventBus;

    /**
     * @var MessageBus
     */
    private $messageBus;

    /**
     * @var AggregateFactory
     */
    public $aggregateFactory;

    /**
     * @var array
     */
    private $aggregates = [];

    /**
     * CommandBus constructor.
     *
     * @param EventBus         $eventBus
     * @param MessageBus       $messageBus
     * @param AggregateFactory $aggregateFactory
     */
    public function __construct(EventBus $eventBus, MessageBus $messageBus, AggregateFactory $aggregateFactory)
    {
        $this->eventBus = $eventBus;
        $this->messageBus = $messageBus;
        $this->aggregateFactory = $aggregateFactory;
    }

    /**
     * A convenience method to dispatch a command.
     *
     * @param string     $commandClass
     * @param string     $aggregateUuid
     * @param array|null $payload
     * @param int|null   $user
     * @param bool|null  $queueEvents
     *
     * @return bool Returns true if the command was accepted.
     * @throws \RevisionTen\CQRS\Exception\InterfaceException
     */
    public function execute(string $commandClass, string $aggregateUuid, ?array $payload = [], ?int $user = -1, ?bool $queueEvents = false): bool
    {
        if (!in_array(CommandInterface::class, class_implements($commandClass), true)) {
            throw new InterfaceException($commandClass.' must implement '.CommandInterface::class);
        }

        /** @var CommandInterface $commandClass */
        $agregate = $this->aggregateFactory->build($aggregateUuid, $commandClass::getAggregateClass());
        $agregateVersion = $agregate->getVersion();

        $commandUuid = Uuid::uuid1()->toString();
        $command = new $commandClass($user, $commandUuid, $aggregateUuid, $agregateVersion, $payload);

        return $this->dispatch($command, $queueEvents);
    }

    /**
     * This function is used to dispatch a provided Command.
     *
     * @param \RevisionTen\CQRS\Interfaces\CommandInterface $command
     * @param bool                                          $queueEvents
     *
     * @return bool Returns true if the command was accepted.
     * @throws \Exception
     */
    public function dispatch(CommandInterface $command, bool $queueEvents = false): bool
    {
        try {
            if ($command instanceof CommandInterface) {
                // Reset aggregates.
                $this->aggregates = [];

                /**
                 * Get Handler for Command.
                 *
                 * @var HandlerInterface $handler
                 */
                $handlerClass = $command::getHandlerClass();
                $handler = new $handlerClass($this->messageBus, $this->aggregateFactory);

                if ($handler instanceof HandlerInterface) {
                    // Invoke Handler.
                    $handler($command, $this->aggregates);

                    // Check for events on aggregates, pass them to EventBus#publish()
                    $events = [];

                    /** @var AggregateInterface $aggregate */
                    foreach ($this->aggregates as $aggregate) {
                        /** @var array $pendingEvents */
                        $pendingEvents = $aggregate->getPendingEvents();

                        $events += $pendingEvents;
                    }

                    $this->eventBus->publish($events, $this, $queueEvents);
                } else {
                    throw new InterfaceException(\get_class($handler).' must implement '.HandlerInterface::class);
                }
            } else {
                throw new InterfaceException(\get_class($command).' must implement '.CommandInterface::class);
            }
        } catch (InterfaceException $e) {
            $this->messageBus->dispatch(new Message(
                $e->getMessage(),
                $e->getCode(),
                $command->getUuid(),
                $command->getAggregateUuid(),
                $e
            ));
        }

        /** @var Message[] $messages */
        $messages = $this->messageBus->getMessagesByCommand($command->getUuid());

        // Returns true if the command was accepted.
        return (!empty($messages) && CODE_OK === $messages[0]->code);
    }
}
