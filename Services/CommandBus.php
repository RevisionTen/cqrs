<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Services;

use Exception;
use Ramsey\Uuid\Uuid;
use RevisionTen\CQRS\Exception\CommandValidationException;
use RevisionTen\CQRS\Exception\InterfaceException;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;
use RevisionTen\CQRS\Message\Message;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use function class_implements;
use function get_class;
use function in_array;

class CommandBus
{
    private EventBus $eventBus;

    private MessageBus $messageBus;

    public AggregateFactory $aggregateFactory;

    private ContainerInterface $container;

    /**
     * @var AggregateInterface[]
     */
    private array $aggregates = [];

    public function __construct(EventBus $eventBus, MessageBus $messageBus, AggregateFactory $aggregateFactory, ContainerInterface $container)
    {
        $this->eventBus = $eventBus;
        $this->messageBus = $messageBus;
        $this->aggregateFactory = $aggregateFactory;
        $this->container = $container;
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
     *
     * @throws \RevisionTen\CQRS\Exception\InterfaceException
     * @throws Exception
     */
    public function execute(string $commandClass, string $aggregateUuid, ?array $payload = [], ?int $user = -1, ?bool $queueEvents = false): bool
    {
        /**
         * @var CommandInterface $commandClass
         */
        if (!in_array(CommandInterface::class, class_implements($commandClass), true)) {
            throw new InterfaceException($commandClass.' must implement '.CommandInterface::class);
        }

        if ($queueEvents && -1 !== $user) {
            // Get aggregate as the user sees it.
            $aggregate = $this->aggregateFactory->build($aggregateUuid, $commandClass::getAggregateClass(), null, $user);
        } else {
            $aggregate = $this->aggregateFactory->build($aggregateUuid, $commandClass::getAggregateClass());
        }

        $aggregateVersion = $aggregate->getVersion();

        $commandUuid = Uuid::uuid1()->toString();
        /**
         * @var CommandInterface $command
         */
        $command = new $commandClass($user, $commandUuid, $aggregateUuid, $aggregateVersion, $payload);

        return $this->dispatch($command, $queueEvents);
    }

    /**
     * This performs the following actions:
     * Get the Aggregate.
     * Check if the Command is valid.
     * Create an Event for the Command and push it onto the Aggregates pending Events.
     *
     * @param \RevisionTen\CQRS\Interfaces\CommandInterface $command
     * @param \RevisionTen\CQRS\Interfaces\HandlerInterface $handler
     *
     * @throws Exception
     */
    private function handleCommand(CommandInterface $command, HandlerInterface $handler): void
    {
        $aggregateClass = $command::getAggregateClass();
        $aggregateUuid = $command->getAggregateUuid();
        $user = $command->getUser();

        // Get Aggregate.
        $aggregate = $this->aggregateFactory->build($aggregateUuid, $aggregateClass, null, $user);

        // Check if commands input is valid.
        try {
            $validCommand = $handler->validateCommand($command, $aggregate);
        } catch (CommandValidationException $commandValidationException) {

            $validCommand = false;

            $this->messageBus->dispatch(new Message(
                $commandValidationException->getMessage(),
                $commandValidationException->getCode(),
                $commandValidationException->command->getUuid(),
                $commandValidationException->command->getAggregateUuid(),
                $commandValidationException
            ));
        }

        // Check if the Aggregate and target Version matches.
        $versionMatches = $aggregate->getVersion() === $command->getOnVersion();

        if (!$versionMatches) {
            // Version does not match.
            $this->messageBus->dispatch(new Message(
                'Aggregate target version is outdated or does not exist',
                CODE_CONFLICT,
                $command->getUuid(),
                $aggregateUuid,
                null
            ));
        } elseif ($validCommand) {
            try {
                // Create Event for Command.
                $event = $handler->createEvent($command);

                if ($event instanceof EventInterface) {
                    // Apply Event to Aggregate.
                    $aggregate = $this->aggregateFactory->apply($aggregate, $event);

                    $this->aggregates[] = $aggregate;
                } else {
                    throw new InterfaceException(get_class($event).' must implement '.EventInterface::class);
                }
            } catch (InterfaceException $e) {
                $this->messageBus->dispatch(new Message(
                    $e->getMessage(),
                    $e->getCode(),
                    $command->getUuid(),
                    $aggregateUuid,
                    $e
                ));
            }
        } else {
            // Handle invalid command.
            $this->messageBus->dispatch(new Message(
                'Invalid Command',
                CODE_BAD_REQUEST,
                $command->getUuid(),
                $aggregateUuid,
                null
            ));
        }
    }

    /**
     * This function is used to dispatch a provided Command.
     *
     * @param \RevisionTen\CQRS\Interfaces\CommandInterface $command
     * @param bool                                          $queueEvents
     *
     * @return bool Returns true if the command was accepted.
     * @throws Exception
     */
    public function dispatch(CommandInterface $command, bool $queueEvents = false): bool
    {
        try {
            if ($command instanceof CommandInterface) {
                // Reset aggregates.
                $this->aggregates = [];

                // Get Handler for Command.
                $handlerClass = $command::getHandlerClass();

                // Try to get the handler as a service or instantiate it.
                try {
                    /**
                     * @var HandlerInterface $handler
                     */
                    $handler = $this->container->get($handlerClass);
                } catch (ServiceNotFoundException $e) {
                    /**
                     * @var HandlerInterface $handler
                     */
                    $handler = new $handlerClass();
                }

                if ($handler instanceof HandlerInterface) {

                    // Use the command handler to validate the command and produce events.
                    $this->handleCommand($command, $handler);

                    // Check for events on aggregates, pass them to EventBus#publish()
                    $events = [];

                    foreach ($this->aggregates as $aggregate) {
                        $pendingEvents = $aggregate->getPendingEvents();
                        array_push($events, ...$pendingEvents);
                    }

                    $this->eventBus->publish($events, $queueEvents);
                } else {
                    throw new InterfaceException(get_class($handler).' must implement '.HandlerInterface::class);
                }
            } else {
                throw new InterfaceException(get_class($command).' must implement '.CommandInterface::class);
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

        $messages = $this->messageBus->getMessagesByCommand($command->getUuid());

        // Returns true if the command was accepted.
        return (!empty($messages) && CODE_OK === $messages[0]->code);
    }
}
