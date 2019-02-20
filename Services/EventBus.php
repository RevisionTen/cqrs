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
        $this->eventDispatcher = $eventDispatcher;
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
        /**
         * @var EventInterface $event
         */
        foreach ($events as $event) {
            // Save the event to the event stream.
            $eventStreamObject = new EventStreamObject();
            $eventStreamObject->setUuid($event->getCommand()->getAggregateUuid());
            $eventStreamObject->setCommandUuid($event->getCommand()->getUuid());
            $eventStreamObject->setPayload($event->getCommand()->getPayload());
            $eventStreamObject->setMessage($event->getMessage());
            $eventStreamObject->setEvent(\get_class($event));
            $eventStreamObject->setAggregateClass($event->getCommand()->getAggregateClass());
            $eventStreamObject->setVersion($event->getCommand()->getOnVersion() + 1);
            $eventStreamObject->setUser($event->getCommand()->getUser());


            if ($qeueEvents) {
                $eventQeueObject = new EventQeueObject($eventStreamObject);
                $this->eventStore->qeue($eventQeueObject);
                $message = 'Qeued event: '.$event->getMessage();
            } else {
                $this->eventStore->add($eventStreamObject);
                $message = 'Persisted event: '.$event->getMessage();
            }

            $this->messageBus->dispatch(new Message(
                $message,
                $event::getCode(),
                $event->getCommand()->getUuid(),
                $event->getCommand()->getAggregateUuid(),
                null,
                $event->getCommand()->getPayload()
            ));
        }

        try {
            $this->eventStore->save();
            // Listeners are called even if the events are just qeued!
            // They are not called again when the events are persisted to the event stream!
            $this->invokeListeners($events, $commandBus);
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
        $eventStreamObjects = array_map(function ($eventQeueObject) {
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

            // Send update event to aggregate subscribers.
            // Subscribers are notified AFTER the events listeners are processed.
            $this->eventDispatcher->dispatch(AggregateUpdatedEvent::NAME, new AggregateUpdatedEvent($event));
        }
    }
}
