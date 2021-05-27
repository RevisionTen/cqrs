<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Services;

use Exception;
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
use function count;
use function get_object_vars;

class AggregateFactory
{
    private EventStore $eventStore;

    private MessageBus $messageBus;

    private SnapshotStore $snapshotStore;

    private ContainerInterface $container;

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
     * @return AggregateInterface[]
     *
     * @throws Exception
     */
    public function findAggregates(?string $aggregateClass = null): array
    {
        $aggregates = [];

        $eventStreamObjects = $this->eventStore->findAggregates($aggregateClass);
        if ($eventStreamObjects) {
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
    public function build(string $uuid, string $aggregateClass, ?int $max_version = null, ?int $user = null): AggregateInterface
    {
        try {
            /**
             * @var AggregateInterface $aggregate
             */
            $aggregate = new $aggregateClass($uuid);

            if ($aggregate instanceof AggregateInterface) {
                // Get latest matching Snapshot.
                $snapshot = $this->snapshotStore->find($uuid, $max_version);
                $min_version = $snapshot ? ($snapshot->getVersion() + 1) : null;
                if ($snapshot) {
                    $aggregate = $this->loadFromSnapshot($aggregate, $snapshot);
                }

                // Get Event Stream Objects.
                $eventStreamObjects = $this->eventStore->find($uuid, $max_version, $min_version);
                if ($eventStreamObjects) {
                    $aggregate = $this->loadFromHistory($aggregate, $eventStreamObjects);
                }

                // Set the current version that is recorded in the event stream.
                $aggregate->setStreamVersion($aggregate->getVersion());

                if (null !== $user) {
                    // Get queued Event Stream Objects.
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
     * Load an Aggregate from provided Event Stream Objects.
     *
     * @param AggregateInterface  $aggregate
     * @param EventStreamObject[] $eventStreamObjects
     *
     * @return AggregateInterface
     */
    public function loadFromHistory(AggregateInterface $aggregate, array $eventStreamObjects): AggregateInterface
    {
        // Arrays start at zero ;)
        $count = count($eventStreamObjects) - 1;

        // Get events and replay them.
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
        $snapshotData = $snapshot->getAggregate();

        // Load data from other public properties.
        $properties = get_object_vars($aggregate);

        foreach ($properties as $property => $existingValue) {
            if (property_exists($snapshotData, $property)) {
                $aggregate->{$property} = $snapshotData->{$property};
            }
        }

        // Todo: Is this still needed?
        $aggregate->setVersion($snapshot->getVersion());
        $aggregate->setSnapshotVersion($snapshot->getVersion());
        $aggregate->setCreated($snapshot->getAggregateCreated());
        $aggregate->setModified($snapshot->getAggregateModified());
        $aggregate->setUuid($snapshot->getUuid());
        $aggregate->setHistory($snapshot->getHistory());

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
        foreach ($aggregate->getPendingEvents() as $event) {
            // Get Handler for Command.
            $handlerClass = $event::getHandlerClass();

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
