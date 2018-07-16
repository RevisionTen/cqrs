<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Services;

use RevisionTen\CQRS\Exception\AggregateException;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;
use RevisionTen\CQRS\Message\Message;
use RevisionTen\CQRS\Model\Aggregate;
use RevisionTen\CQRS\Model\EventStreamObject;
use RevisionTen\CQRS\Model\Snapshot;

class AggregateFactory
{
    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var MessageBus
     */
    private $messageBus;

    /**
     * @var SnapshotStore
     */
    private $snapshotStore;

    /**
     * AggregateFactory constructor.
     *
     * @param MessageBus    $messageBus
     * @param EventStore    $eventStore
     * @param SnapshotStore $snapshotStore
     */
    public function __construct(MessageBus $messageBus, EventStore $eventStore, SnapshotStore $snapshotStore)
    {
        $this->messageBus = $messageBus;
        $this->eventStore = $eventStore;
        $this->snapshotStore = $snapshotStore;
    }

    public function findAggregates(string $aggregateClass = null): array
    {
        $aggregates = [];

        $eventStreamObjects = $this->eventStore->findAggregates($aggregateClass);

        if ($eventStreamObjects) {
            /**
             * @var EventStreamObject $eventStreamObject
             */
            foreach ($eventStreamObjects as $eventStreamObject) {
                $aggregates[] = $this->build($eventStreamObject->getUuid(), $eventStreamObject->getAggregateClass());
            }
        }

        return $aggregates;
    }

    /**
     * Builds an aggregate from a provided Uuid and Aggregate class.
     *
     * @param string   $uuid
     * @param string   $aggregateClass
     * @param int|null $max_version
     * @param int|null $user
     *
     * @return AggregateInterface
     */
    public function build(string $uuid, string $aggregateClass, int $max_version = null, int $user = null): AggregateInterface
    {
        try {
            /**
             * @var AggregateInterface $aggregate
             */
            $aggregate = new $aggregateClass($uuid);

            if ($aggregate instanceof AggregateInterface) {
                /**
                 * Get latest matching Snapshot.
                 *
                 * @var Snapshot $snapshot
                 */
                $snapshot = $this->snapshotStore->find($uuid, $max_version);
                $min_version = $snapshot ? ($snapshot->getVersion() + 1) : null;
                if ($snapshot) {
                    $aggregate = $this->loadFromSnapshot($aggregate, $snapshot);
                }

                /**
                 * Get Event Stream Objects.
                 *
                 * @var EventStreamObject[] $eventStreamObjects
                 */
                $eventStreamObjects = $this->eventStore->find($uuid, $max_version, $min_version);
                if ($eventStreamObjects) {
                    $aggregate = $this->loadFromHistory($aggregate, $eventStreamObjects);
                }

                // Set the current version that is recorded in the event stream.
                $aggregate->setStreamVersion($aggregate->getVersion());

                if (null !== $user) {
                    /**
                     * Get qeued Event Stream Objects.
                     *
                     * @var EventStreamObject[] $eventStreamObjects
                     */
                    $eventStreamObjects = $this->eventStore->findQeued($uuid, $max_version, $aggregate->getVersion() + 1, $user);
                    if ($eventStreamObjects) {
                        $aggregate = $this->loadFromHistory($aggregate, $eventStreamObjects);
                    }
                }
            } else {
                // Instantiate generic Aggregate to prevent Handler from failing.
                $aggregate = new Aggregate($uuid);
                throw new AggregateException($aggregateClass.' must implement '.AggregateInterface::class);
            }
        } catch (AggregateException $e) {
            $this->messageBus->dispatch(new Message(
                $e->getMessage(),
                $e->getCode(),
                null,
                $uuid,
                $e
            ));
        }

        return $aggregate;
    }

    /**
     * Optimizes an Aggregate by taking a Snapshot.
     *
     * @param AggregateInterface $aggregate
     */
    public function optimize(AggregateInterface $aggregate): void
    {
        //if ($aggregate->shouldTakeSnapshot()) {
        //}
    }

    /**
     * Load an Aggregate from provided Event Stream Objects.
     *
     * @param AggregateInterface $aggregate
     * @param array              $eventStreamObjects
     *
     * @return AggregateInterface
     */
    public function loadFromHistory(AggregateInterface $aggregate, array $eventStreamObjects): AggregateInterface
    {
        // Arrays start at zero ;)
        $count = (int) count($eventStreamObjects) - 1;

        /**
         * Get events and replay them.
         *
         * @var EventStreamObject $eventStreamObject
         */
        foreach ($eventStreamObjects as $key => $eventStreamObject) {
            $uuid = $eventStreamObject->getUuid();

            // Check if the uuid of the provided Event Stream Object matches.
            if ($uuid !== $aggregate->getUuid()) {
                continue;
            }

            /** @var EventInterface $eventClass */
            $eventClass = $eventStreamObject->getEvent();
            $payload = $eventStreamObject->getPayload();

            if (0 === $key && null === $aggregate->getCreated()) {
                // Set Created from first EventStreamObject.
                $aggregate->setCreated($eventStreamObject->getCreated());
            }

            if ($key === $count) {
                // Set Modified from last EventStreamObject.
                $aggregate->setModified($eventStreamObject->getCreated());
            }

            /**
             * Recreate command.
             *
             * @var CommandInterface $commandClass
             */
            $commandClass = $eventClass::getCommandClass();
            // Its safe to assume the command was created on the previous version, it would have failed otherwise.
            $onVersion = $eventStreamObject->getVersion() - 1;
            $commandUuid = $eventStreamObject->getCommandUuid();
            $user = $eventStreamObject->getUser();
            /** @var CommandInterface $command */
            $command = new $commandClass($user, $commandUuid, $uuid, $onVersion, $payload);

            /** @var EventInterface $event */
            $event = new $eventClass($command);
            $aggregate = $this->apply($aggregate, $event);

            // Update the Aggregate history.
            $aggregate->addToHistory([
                'version' => $eventStreamObject->getVersion(),
                'message' => $eventStreamObject->getMessage(),
                'created' => $eventStreamObject->getCreated(),
                'payload' => $eventStreamObject->getPayload(),
            ]);
        }

        return $this->applyChanges($aggregate);
    }

    /**
     * Loads the Aggregate from a Snapshot.
     *
     * @param AggregateInterface $aggregate
     * @param Snapshot           $snapshot
     *
     * @return AggregateInterface
     */
    public function loadFromSnapshot(AggregateInterface $aggregate, Snapshot $snapshot): AggregateInterface
    {
        $snapshotData = $snapshot->getPayload();

        $aggregate->setVersion($snapshot->getVersion());
        $aggregate->setSnapshotVersion($snapshot->getVersion());
        $aggregate->setCreated($snapshot->getAggregateCreated());
        $aggregate->setModified($snapshot->getAggregateModified());
        $aggregate->setUuid($snapshot->getUuid());
        $aggregate->setHistory($snapshot->getHistory());

        // Load data from other public properties.
        $properties = get_object_vars($aggregate);

        foreach ($properties as $property => $existingValue) {
            // Skip Modified and Created public properties.
            if ('created' === $property || 'modified' === $property) {
                continue;
            }
            // Todo: Convert json date to php DateTime.

            if (isset($snapshotData[$property])) {
                $aggregate->{$property} = $snapshotData[$property];
            }
        }

        return $aggregate;
    }

    /**
     * Execute the Handlers of pending Events on Aggregate.
     *
     * This changes the state of the Aggregate.
     *
     * @param AggregateInterface $aggregate
     *
     * @return AggregateInterface
     */
    public function applyChanges(AggregateInterface $aggregate): AggregateInterface
    {
        // Execute the handlers of pending Events.
        /**
         * @var EventInterface $event
         */
        foreach ($aggregate->getPendingEvents() as $event) {
            /**
             * Get Handler for Command.
             *
             * @var HandlerInterface $handler
             */
            $handlerClass = $event->getCommand()->getHandlerClass();
            $handler = new $handlerClass($this->messageBus, $this);
            $aggregate = $handler->executeHandler($event->getCommand(), $aggregate);
        }

        // Clear pending events.
        $aggregate->setPendingEvents([]);

        return $aggregate;
    }

    /**
     * Adds an Event to the Aggregates pending Events.
     *
     * Events added here will be change the state of the Aggregate
     * when applyChanges() is called on the Aggregate.
     *
     * @param AggregateInterface $aggregate
     * @param EventInterface     $event
     *
     * @return AggregateInterface
     */
    public function apply(AggregateInterface $aggregate, EventInterface $event): AggregateInterface
    {
        // Increase version on each apply call.
        $aggregate->setVersion($event->getCommand()->getOnVersion() + 1);

        // Add Event to pending Events.
        return $aggregate->addPendingEvent($event);
    }
}
