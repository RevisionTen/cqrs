<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Services;

use RevisionTen\CQRS\Event\AggregateUpdatedEvent;
use RevisionTen\CQRS\Exception\InterfaceException;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\ListenerInterface;
use RevisionTen\CQRS\Message\Message;
use RevisionTen\CQRS\Model\EventQeueObject;
use RevisionTen\CQRS\Model\EventStreamObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\LegacyEventDispatcherProxy;

class EventBus
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
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * EventBus constructor.
     *
     * @param EventStore               $eventStore
     * @param MessageBus               $messageBus
     * @param ContainerInterface       $container
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventStore $eventStore, MessageBus $messageBus, ContainerInterface $container, EventDispatcherInterface $eventDispatcher)
    {
        $this->eventStore = $eventStore;
        $this->messageBus = $messageBus;
        $this->container = $container;
        $this->eventDispatcher = LegacyEventDispatcherProxy::decorate($eventDispatcher);
    }

    /**
     * Dispatch all events to observing event handlers and save them to the Event Store.
     *
     * @param array      $events
     * @param CommandBus $commandBus
     * @param bool       $qeueEvents
     */
    public function publish(array $events, CommandBus $commandBus, bool $qeueEvents = false): void
    {
        $eventStreamObjects = [];
        /**
         * @var EventInterface $event
         */
        foreach ($events as $event) {
            $command = $event->getCommand();

            // Save the event to the event stream.
            $eventStreamObject = new EventStreamObject();
            $eventStreamObject->setUuid($command->getAggregateUuid());
            $eventStreamObject->setCommandUuid($command->getUuid());
            $eventStreamObject->setPayload($command->getPayload());
            $eventStreamObject->setAggregateClass($command->getAggregateClass());
            $eventStreamObject->setVersion($command->getOnVersion() + 1);
            $eventStreamObject->setUser($command->getUser());

            $eventStreamObject->setMessage($event->getMessage());
            $eventStreamObject->setEvent(\get_class($event));

            // Add to list of eventStreamObjects so we can later notify aggregateSubscribers.
            $eventStreamObjects[] = $eventStreamObject;

            // Add the events to the eventStore.
            if ($qeueEvents) {
                $eventQeueObject = new EventQeueObject($eventStreamObject);
                $this->eventStore->qeue($eventQeueObject);
                $message = 'Qeued event: '.$event->getMessage();
            } else {
                $this->eventStore->add($eventStreamObject);
                $message = 'Persisted event: '.$event->getMessage();
            }

            // Dispatch messages about the events.
            $this->messageBus->dispatch(new Message(
                $message,
                $event::getCode(),
                $command->getUuid(),
                $command->getAggregateUuid(),
                null,
                $command->getPayload()
            ));
        }

        try {
            $this->eventStore->save();

            // Listeners are called even if the events are just qeued!
            // They are not called again when the events are persisted to the event stream!
            $this->invokeListeners($events, $commandBus);

            // Notify subscribers.
            // Subscribers are notified AFTER the events listeners are processed.
            // Subscribers are NOT notified of qeued events.
            if (!$qeueEvents) {
                $this->sendAggregateUpdates($eventStreamObjects);
            }
        } catch (\Exception $e) {
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
     * Save qeued Events to the Event Stream.
     *
     * @param EventQeueObject[] $eventQeueObjects
     *
     * @return bool
     */
    public function publishQeued(array $eventQeueObjects): bool
    {
        $eventStreamObjects = array_map(static function ($eventQeueObject) {
            /* @var EventQeueObject $eventQeueObject */
            return $eventQeueObject->getEventStreamObject();
        }, $eventQeueObjects);

        /**
         * Add EventStreamObjects to list of EventStreamObjects that should be persisted.
         *
         * @var EventStreamObject $eventStreamObject
         */
        foreach ($eventStreamObjects as $eventStreamObject) {
            $this->eventStore->add($eventStreamObject);
        }

        try {
            $this->eventStore->save();

            // Notify subscribers.
            $this->sendAggregateUpdates($eventStreamObjects);
        } catch (\Exception $e) {
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

        /**
         * Remove EventQeueObjects from qeue.
         *
         * @var EventQeueObject $eventQeueObject
         */
        foreach ($eventQeueObjects as $eventQeueObject) {
            $this->eventStore->remove($eventQeueObject);
        }

        try {
            $this->eventStore->save();

            return true;
        } catch (\Exception $e) {
            // Removal from the Event Qeue failed.
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
            $this->eventDispatcher->dispatch(new AggregateUpdatedEvent($event), AggregateUpdatedEvent::NAME);
        }
    }

    private function invokeListeners(array $events, CommandBus $commandBus): void
    {
        /**
         * Execute Listener if Event was recorded.
         *
         * @var EventInterface $event
         */
        foreach ($events as $event) {
            // Execute the regular Event Listener.
            $eventListenerClass = $event::getListenerClass();

            // Get the Listener.
            try {
                // Get it as a service.
                $eventListener = $this->container->get($eventListenerClass);
            } catch (ServiceNotFoundException $e) {
                /**
                 * Construct Listener instance.
                 *
                 * @var object $eventListener
                 */
                $eventListener = new $eventListenerClass();
            }

            try {
                if ($eventListener instanceof ListenerInterface) {
                    // Invoke the Event Listener.
                    $eventListener($commandBus, $event);
                } else {
                    throw new InterfaceException($eventListenerClass.' must implement '.ListenerInterface::class);
                }
            } catch (InterfaceException $e) {
                $this->messageBus->dispatch(new Message(
                    $e->getMessage(),
                    $e->getCode(),
                    $event->getCommand()->getUuid(),
                    $event->getCommand()->getAggregateUuid(),
                    $e
                ));
            }

            // Execute the Listener that was passed to the Command.
            $callable = $event->getCommand()->getListener();
            if (null !== $callable) {
                try {
                    if (\is_callable($callable)) {
                        $callable($commandBus, $event);
                    } else {
                        throw new \Exception('Passed listener is not callable');
                    }
                } catch (\Exception $e) {
                    $this->messageBus->dispatch(new Message(
                        $e->getMessage(),
                        $e->getCode(),
                        $event->getCommand()->getUuid(),
                        $event->getCommand()->getAggregateUuid(),
                        $e
                    ));
                }
            }
        }
    }
}
