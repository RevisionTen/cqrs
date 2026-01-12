<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Services;

use Exception;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Message\Message;
use RevisionTen\CQRS\Model\EventQueueObject;
use RevisionTen\CQRS\Model\EventStreamObject;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use function array_map;
use function is_array;

class EventStore
{
    private EntityManagerInterface $em;

    private MessageBus $messageBus;

    public function __construct(EntityManagerInterface $em, MessageBus $messageBus)
    {
        $this->em = $em;
        $this->messageBus = $messageBus;
    }

    public static function buildEventFromEventStreamObject(EventStreamObject $eventStreamObject): EventInterface
    {
        $eventClass = $eventStreamObject->getEvent();
        $commandUuid = $eventStreamObject->getCommandUuid();
        $aggregateUuid = $eventStreamObject->getUuid();
        $version = $eventStreamObject->getVersion();
        $user = $eventStreamObject->getUser();
        $payload = $eventStreamObject->getPayload();

        return new $eventClass($aggregateUuid, $commandUuid, $version, $user, $payload);
    }

    /**
     * @param string|null $aggregateClass
     *
     * @return EventStreamObject[]
     */
    public function findAggregates(?string $aggregateClass = null): array
    {
        $criteria = [
            'version' => 1,
        ];

        if (null !== $aggregateClass) {
            $criteria['aggregateClass'] = $aggregateClass;
        }

        /**
         * @var EventStreamObject[] $eventStreamObjects
         */
        $eventStreamObjects = $this->em->getRepository(EventStreamObject::class)->findBy($criteria);

        return is_array($eventStreamObjects) ? $eventStreamObjects : [];
    }

    /**
     * Returns Event Stream Objects for a given Uuid.
     *
     * @param string   $uuid
     * @param int|null $max_version
     * @param int|null $min_version
     *
     * @return EventStreamObject[]
     */
    public function find(string $uuid, ?int $max_version = null, ?int $min_version = null): array
    {
        return $this->findEventObjects(EventStreamObject::class, $uuid, $max_version, $min_version);
    }

    /**
     * Returns queued Event Stream Objects for a given Uuid and User.
     *
     * @param string   $uuid
     * @param int      $user
     * @param int|null $max_version
     * @param int|null $min_version
     *
     * @return EventStreamObject[]
     */
    public function findQueued(string $uuid, int $user, ?int $max_version = null, ?int $min_version = null): array
    {
        /**
         * @var EventQueueObject[] $eventQueueObjects
         */
        $eventQueueObjects = $this->findEventObjects(EventQueueObject::class, $uuid, $max_version, $min_version, $user);

        return array_map(static function ($eventQueueObject) {
            return $eventQueueObject->getEventStreamObject();
        }, $eventQueueObjects);
    }

    /**
     * Returns Event Objects for a given Uuid.
     *
     * @param string   $objectClass
     * @param string   $uuid
     * @param int|null $max_version
     * @param int|null $min_version
     * @param int|null $user
     *
     * @return array
     */
    public function findEventObjects(string $objectClass, string $uuid, ?int $max_version = null, ?int $min_version = null, ?int $user = null): array
    {
        $criteria = new Criteria();

        $criteria->where(Criteria::expr()->eq('uuid', $uuid));

        if (null !== $max_version) {
            $criteria->andWhere(Criteria::expr()->lte('version', $max_version));
        }

        if (null !== $min_version) {
            $criteria->andWhere(Criteria::expr()->gte('version', $min_version));
        }

        if (null !== $user) {
            $criteria->andWhere(Criteria::expr()->eq('user', $user));
        }

        $criteria->orderBy(['version' => Criteria::ASC]);

        /**
         * @var \Doctrine\ORM\EntityRepository $entityRepository
         */
        $entityRepository = $this->em->getRepository($objectClass);

        $eventObjectsResults = $entityRepository->matching($criteria);

        return $eventObjectsResults ? $eventObjectsResults->toArray() : [];
    }

    /**
     * Adds an Event to the Event Store.
     * Events are not persisted until save() is called.
     *
     * @param EventStreamObject $eventStreamObject
     */
    public function add(EventStreamObject $eventStreamObject): void
    {
        $this->em->persist($eventStreamObject);
    }

    /**
     * Adds an Event to the Event Queue.
     * Events are not persisted until save() is called.
     *
     * @param EventQueueObject $eventQueueObject
     */
    public function queue(EventQueueObject $eventQueueObject): void
    {
        $this->em->persist($eventQueueObject);
    }

    /**
     * Removes an Event from the Event Queue.
     * Events are not removed until save() is called.
     *
     * @param EventQueueObject $eventQueueObject
     */
    public function remove(EventQueueObject $eventQueueObject): void
    {
        $this->em->remove($eventQueueObject);
    }

    /**
     * Discards queued Events for a specific Aggregate and User.
     *
     * @param string $uuid
     * @param int    $user
     */
    public function discardQueued(string $uuid, int $user): void
    {
        $EventQueueObjects = $this->findEventObjects(EventQueueObject::class, $uuid, null, null, $user);
        foreach ($EventQueueObjects as $EventQueueObject) {
            $this->remove($EventQueueObject);
        }
        $this->save();
    }

    /**
     * Discards the latest queued Event for a specific Aggregate and User.
     *
     * @param string $uuid
     * @param int    $user
     * @param int    $version
     */
    public function discardLatestQueued(string $uuid, int $user, int $version): void
    {
        $EventQueueObjects = $this->findEventObjects(EventQueueObject::class, $uuid, null, $version, $user);
        foreach ($EventQueueObjects as $EventQueueObject) {
            $this->remove($EventQueueObject);
        }
        $this->save();
    }

    /**
     * Saves the events to the Event Store.
     */
    public function save(): void
    {
        try {
            $this->em->flush();
        } catch (Exception $e) {
            $this->messageBus->dispatch(new Message(
                $e->getMessage(),
                $e->getCode(),
                null,
                null,
                $e
            ));
        }
    }
}
