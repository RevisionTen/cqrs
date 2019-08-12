<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Services;

use RevisionTen\CQRS\Exception\AggregateException;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;
use RevisionTen\CQRS\Message\Message;
use RevisionTen\CQRS\Model\Aggregate;
use RevisionTen\CQRS\Model\EventStreamObject;
use RevisionTen\CQRS\Model\Snapshot;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

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
     * @var ContainerInterface
     */
    private $container;

    /**
     * AggregateFactory constructor.
     *
     * @param \RevisionTen\CQRS\Services\MessageBus                     $messageBus
     * @param \RevisionTen\CQRS\Services\EventStore                     $eventStore
     * @param \RevisionTen\CQRS\Services\SnapshotStore                  $snapshotStore
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(MessageBus $messageBus, EventStore $eventStore, SnapshotStore $snapshotStore, ContainerInterface $container)
    {
        $this->messageBus = $messageBus;
        $this->eventStore = $eventStore;
        $this->snapshotStore = $snapshotStore;
        $this->container = $container;
    }

    /**
     * @param string|null $aggregateClass
     *
     * @return array
     * @throws \Exception
     */
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
     *
     * @throws \Exception
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
                     * Get queued Event Stream Objects.
                     *
                     * @var EventStreamObject[] $eventStreamObjects
                     */
                    $eventStreamObjects = $this->eventStore->findQueued($uuid, $user, $max_version, $aggregate->getVersion() + 1);
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
        $count = \count($eventStreamObjects) - 1;

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

            if (0 === $key && null === $aggregate->getCreated()) {
                // Set Created from first EventStreamObject.
                $aggregate->setCreated($eventStreamObject->getCreated());
            }

            if ($key === $count) {
                // Set Modified from last EventStreamObject.
                $aggregate->setModified($eventStreamObject->getCreated());
            }

            $event = EventStore::buildEventFromEventStreamObject($eventStreamObject);

            $aggregate = $this->apply($aggregate, $event);

            // Update the Aggregate history.
            $aggregate->addToHistory([
                'user' => $eventStreamObject->getUser(),
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
            $handlerClass = $event::getHandlerClass();

            // Try to get the handler as a service or instantiate it.
            try {
                $handler = $this->container->get($handlerClass);
            } catch (ServiceNotFoundException $e) {
                $handler = new $handlerClass();
            }

            $aggregate = $handler->execute($event, $aggregate);
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
        $aggregate->setVersion($event->getVersion());

        // Add Event to pending Events.
        return $aggregate->addPendingEvent($event);
    }
}
