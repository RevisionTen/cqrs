<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Services;

use RevisionTen\CQRS\Exception\InterfaceException;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;
use RevisionTen\CQRS\Message\Message;

class CommandBus
{
    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var EventBus
     */
    private $eventBus;

    /**
     * @var MessageBus
     */
    private $messageBus;

    /**
     * @var SnapshotStore
     */
    private $snapshotStore;

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
     * @param EventStore       $eventStore
     * @param EventBus         $eventBus
     * @param MessageBus       $messageBus
     * @param SnapshotStore    $snapshotStore
     * @param AggregateFactory $aggregateFactory
     */
    public function __construct(EventStore $eventStore, EventBus $eventBus, MessageBus $messageBus, SnapshotStore $snapshotStore, AggregateFactory $aggregateFactory)
    {
        $this->eventStore = $eventStore;
        $this->eventBus = $eventBus;
        $this->messageBus = $messageBus;
        $this->snapshotStore = $snapshotStore;
        $this->aggregateFactory = $aggregateFactory;
    }

    /**
     * This function is used to dispatch a provided Command.
     *
     * @param CommandInterface $command
     * @param bool             $qeueEvents
     */
    public function dispatch(CommandInterface $command, bool $qeueEvents = false): void
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
                $handlerClass = $command->getHandlerClass();
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

                        $events = $events + $pendingEvents;
                    }

                    $this->eventBus->publish($events, $this, $qeueEvents);
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
    }
}
