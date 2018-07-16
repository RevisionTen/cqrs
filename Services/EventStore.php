<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Services;

use RevisionTen\CQRS\Message\Message;
use RevisionTen\CQRS\Model\EventQeueObject;
use RevisionTen\CQRS\Model\EventStreamObject;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;

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
     * Returns qeued Event Stream Objects for a given Uuid and User.
     *
     * @param string   $uuid
     * @param int|null $max_version
     * @param int|null $min_version
     * @param int      $user
     *
     * @return array
     */
    public function findQeued(string $uuid, int $max_version = null, int $min_version = null, int $user): array
    {
        $eventQeueObjects = $this->findEventObjects(EventQeueObject::class, $uuid, $max_version, $min_version, $user);

        return array_map(function ($eventQeueObject) {
            /* @var EventQeueObject $eventQeueObject */
            return $eventQeueObject->getEventStreamObject();
        }, $eventQeueObjects);
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
        $criteria->where($criteria->expr()->eq('uuid', $uuid));

        if (null !== $max_version) {
            $criteria->andWhere($criteria->expr()->lte('version', $max_version));
        }

        if (null !== $min_version) {
            $criteria->andWhere($criteria->expr()->gte('version', $min_version));
        }

        if (null !== $user) {
            $criteria->andWhere($criteria->expr()->eq('user', $user));
        }

        $criteria->orderBy(['version' => Criteria::ASC]);

        $eventObjectsResults = $this->em->getRepository($objectClass)->matching($criteria);
        $eventObjects = $eventObjectsResults ? $eventObjectsResults->toArray() : [];

        return $eventObjects;
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
     * Adds an Event to the Event Qeue.
     * Events are not persisted until save() is called.
     *
     * @param EventQeueObject $eventQeueObject
     */
    public function qeue(EventQeueObject $eventQeueObject): void
    {
        $this->em->persist($eventQeueObject);
    }

    /**
     * Removes an Event from the Event Qeue.
     * Events are not removed until save() is called.
     *
     * @param EventQeueObject $eventQeueObject
     */
    public function remove(EventQeueObject $eventQeueObject): void
    {
        $this->em->remove($eventQeueObject);
    }

    /**
     * Discards qeued Events for a specific Aggregate and User.
     *
     * @param string $uuid
     * @param int    $user
     */
    public function discardQeued(string $uuid, int $user): void
    {
        $eventQeueObjects = $this->findEventObjects(EventQeueObject::class, $uuid, null, null, $user);
        foreach ($eventQeueObjects as $eventQeueObject) {
            $this->remove($eventQeueObject);
        }
        $this->save();
    }

    /**
     * Discards the latest qeued Event for a specific Aggregate and User.
     *
     * @param string $uuid
     * @param int    $user
     * @param int    $version
     */
    public function discardLatestQeued(string $uuid, int $user, int $version): void
    {
        $eventQeueObjects = $this->findEventObjects(EventQeueObject::class, $uuid, null, $version, $user);
        foreach ($eventQeueObjects as $eventQeueObject) {
            $this->remove($eventQeueObject);
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
