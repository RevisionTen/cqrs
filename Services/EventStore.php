<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Services;

use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Message\Message;
use RevisionTen\CQRS\Model\EventQueueObject;
use RevisionTen\CQRS\Model\EventStreamObject;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use function is_array;

class EventStore
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var MessageBus
     */
    private $messageBus;

    /**
     * EventStore constructor.
     *
     * @param EntityManagerInterface $em
     * @param MessageBus             $messageBus
     */
    public function __construct(EntityManagerInterface $em, MessageBus $messageBus)
    {
        $this->em = $em;
        $this->messageBus = $messageBus;
    }

    /**
     * @param EventStreamObject $eventStreamObject
     *
     * @return EventInterface
     */
    public static function buildEventFromEventStreamObject(EventStreamObject $eventStreamObject): EventInterface
    {
        /** @var EventInterface $eventClass */
        $eventClass = $eventStreamObject->getEvent();

        $commandUuid = $eventStreamObject->getCommandUuid();
        $aggregateUuid = $eventStreamObject->getUuid();
        $version = $eventStreamObject->getVersion();
        $user = $eventStreamObject->getUser();
        $payload = $eventStreamObject->getPayload();

        return new $eventClass($aggregateUuid, $commandUuid, $version, $user, $payload);
    }

    public function findAggregates(string $aggregateClass = null): array
    {
        $criteria = [
            'version' => 1,
        ];

        if (null !== $aggregateClass) {
            $criteria['aggregateClass'] = $aggregateClass;
        }

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
     * @return array
     */
    public function find(string $uuid, int $max_version = null, int $min_version = null): array
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
     * @return array
     */
    public function findQueued(string $uuid, int $user, int $max_version = null, int $min_version = null): array
    {
        $eventQueueObjects = $this->findEventObjects(EventQueueObject::class, $uuid, $max_version, $min_version, $user);

        return array_map(static function ($eventQueueObject) {
            /* @var EventQueueObject $eventQueueObject */
            return $eventQueueObject->getEventStreamObject();
        }, $eventQueueObjects);
    }

    /**
     * @deprecated Use findQueued instead.
     */
    public function findQeued(string $uuid, int $max_version = null, int $min_version = null, int $user): array
    {
        return $this->findQueued($uuid, $user, $max_version, $min_version);
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
    public function findEventObjects(string $objectClass, string $uuid, int $max_version = null, int $min_version = null, int $user = null): array
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

        /** @var \Doctrine\ORM\EntityRepository $entityRepository */
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
     * @deprecated Use queue instead.
     */
    public function qeue(EventQueueObject $eventQueueObject): void
    {
        $this->queue($eventQueueObject);
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
     * @deprecated Use discardQueued instead.
     */
    public function discardQeued(string $uuid, int $user): void
    {
        $this->discardQueued($uuid, $user);
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
     * @deprecated Use discardLatestQueued instead.
     */
    public function discardLatestQeued(string $uuid, int $user, int $version): void
    {
        $this->discardLatestQueued($uuid, $user, $version);
    }

    /**
     * Saves the events to the Event Store.
     */
    public function save(): void
    {
        try {
            $this->em->flush();
        } catch (OptimisticLockException $e) {
            $this->messageBus->dispatch(new Message(
                $e->getMessage(),
                $e->getCode(),
                null,
                null,
                $e
            ));
        } catch (UniqueConstraintViolationException $e) {
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
