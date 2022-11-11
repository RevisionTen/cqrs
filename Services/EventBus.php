<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Services;

use Exception;
use RevisionTen\CQRS\Event\AggregateUpdatedEvent;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Message\Message;
use RevisionTen\CQRS\Model\EventQueueObject;
use RevisionTen\CQRS\Model\EventStreamObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_map;
use function get_class;

class EventBus
{
    private EventStore $eventStore;

    private MessageBus $messageBus;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventStore $eventStore, MessageBus $messageBus, EventDispatcherInterface $eventDispatcher)
    {
        $this->eventStore = $eventStore;
        $this->messageBus = $messageBus;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Dispatch all events to observing event handlers and save them to the Event Store.
     *
     * @param EventInterface[] $events
     * @param bool             $queueEvents
     *
     * @throws Exception
     */
    public function publish(array $events, bool $queueEvents = false): void
    {
        $eventStreamObjects = [];

        foreach ($events as $event) {
            // Save the event to the event stream.
            $eventStreamObject = new EventStreamObject();

            $eventStreamObject->setEvent(get_class($event));

            $eventStreamObject->setAggregateClass($event::getAggregateClass());
            $eventStreamObject->setMessage($event->getMessage());
            $eventStreamObject->setUuid($event->getAggregateUuid());
            $eventStreamObject->setCommandUuid($event->getCommandUuid());
            $eventStreamObject->setPayload($event->getPayload());
            $eventStreamObject->setVersion($event->getVersion());
            $eventStreamObject->setUser($event->getUser());

            // Add to list of eventStreamObjects, so we can later notify aggregateSubscribers.
            $eventStreamObjects[] = $eventStreamObject;

            // Add the events to the eventStore.
            if ($queueEvents) {
                $eventQueueObject = new EventQueueObject($eventStreamObject);
                $this->eventStore->queue($eventQueueObject);
                $message = 'Queued event: '.$event->getMessage();
            } else {
                $this->eventStore->add($eventStreamObject);
                $message = 'Persisted event: '.$event->getMessage();
            }

            // Dispatch messages about the events.
            $this->messageBus->dispatch(new Message(
                $message,
                CODE_OK,
                $event->getCommandUuid(),
                $event->getAggregateUuid(),
                null,
                $event->getPayload()
            ));
        }

        try {
            $this->eventStore->save();

            // Notify subscribers and event listeners.
            // Events are dispatched even if the events are just queued!
            // They are not dispatched again when the events are persisted to the event stream!
            $this->invokeListeners($events);

            // Send an AggregateUpdatedEvent after the events were persisted to the event stream!
            // An AggregateUpdatedEvent is NOT send after storing queued events.
            if (!$queueEvents) {
                $this->sendAggregateUpdates($eventStreamObjects);
            }
        } catch (Exception $e) {
            // Saving to the Event Store failed. This can happen for example when an aggregate version is already taken.
            $this->messageBus->dispatch(new Message(
                $e->getMessage(),
                $e->getCode(),
                null,
                null,
                $e
            ));
        }
    }

    /**
     * Save queued Events to the Event Stream.
     *
     * @param EventQueueObject[] $eventQueueObjects
     *
     * @return bool
     * @throws Exception
     */
    public function publishQueued(array $eventQueueObjects): bool
    {
        $eventStreamObjects = array_map(static function (EventQueueObject $eventQueueObject) {
            return $eventQueueObject->getEventStreamObject();
        }, $eventQueueObjects);

        // Add EventStreamObjects to list of EventStreamObjects that should be persisted.
        foreach ($eventStreamObjects as $eventStreamObject) {
            $this->eventStore->add($eventStreamObject);
        }

        try {
            $this->eventStore->save();

            // Send an AggregateUpdatedEvent after the formerly queued events were persisted to the event stream!
            $this->sendAggregateUpdates($eventStreamObjects);
        } catch (Exception $e) {
            // Saving to the Event Store failed. This can happen for example when an aggregate version is already taken.
            $this->messageBus->dispatch(new Message(
                $e->getMessage(),
                $e->getCode(),
                null,
                null,
                $e
            ));

            return false;
        }

        // Remove EventQueueObjects from queue.
        foreach ($eventQueueObjects as $eventQueueObject) {
            $this->eventStore->remove($eventQueueObject);
        }

        try {
            $this->eventStore->save();

            return true;
        } catch (Exception $e) {
            // Removal from the Event Queue failed.
            $this->messageBus->dispatch(new Message(
                $e->getMessage(),
                $e->getCode(),
                null,
                null,
                $e
            ));

            return false;
        }
    }

    /**
     * @param EventStreamObject[] $eventStreamObjects
     */
    private function sendAggregateUpdates(array $eventStreamObjects): void
    {
        // Get the last event of each aggregate.
        $aggregates = [];
        foreach ($eventStreamObjects as $eventStreamObject) {
            $aggregates[$eventStreamObject->getUuid()] = $eventStreamObject;
        }

        // Notify subscribers of last event.
        foreach ($aggregates as $lastEventStreamObject) {
            // Rebuild last event.
            $event = EventStore::buildEventFromEventStreamObject($lastEventStreamObject);
            $this->eventDispatcher->dispatch(new AggregateUpdatedEvent($event));
        }
    }

    /**
     * @param EventInterface[] $events
     */
    private function invokeListeners(array $events): void
    {
        // Execute Listener if Event was recorded.
        foreach ($events as $event) {
            // Dispatch the event.
            $this->eventDispatcher->dispatch($event);
        }
    }
}
