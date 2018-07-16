<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Services;

use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Message\Message;
use RevisionTen\CQRS\Model\Snapshot;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;

class SnapshotStore
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
     * Finds the latest Snapshot of an Aggregate.
     *
     * @param $uuid
     * @param null $max_version the maximum version for the provided uuid
     *
     * @return Snapshot|null
     */
    public function find($uuid, $max_version = null): ?Snapshot
    {
        if (null !== $max_version) {
            $criteria = new Criteria();
            $criteria
                ->where($criteria->expr()->eq('uuid', $uuid))
                ->andWhere($criteria->expr()->lte('version', $max_version))
                ->orderBy(['version' => Criteria::DESC])
            ;
            $snapshot = $this->em->getRepository(Snapshot::class)->matching($criteria)->first();
        } else {
            $snapshot = $this->em->getRepository(Snapshot::class)->findOneBy([
                'uuid' => $uuid,
            ], [
                'version' => Criteria::DESC,
            ]);
        }

        return $snapshot instanceof Snapshot ? $snapshot : null;
    }

    /**
     * Saves a Snapshot.
     *
     * @param AggregateInterface $aggregate
     */
    public function save(AggregateInterface $aggregate): void
    {
        $snapshot = new Snapshot();
        $snapshot->setUuid($aggregate->getUuid());
        $snapshot->setVersion($aggregate->getVersion());
        $snapshot->setAggregateCreated($aggregate->getCreated());
        $snapshot->setAggregateModified($aggregate->getModified());
        $snapshot->setAggregateClass(get_class($aggregate));
        $snapshot->setHistory($aggregate->getHistory());
        $aggregateData = json_decode(json_encode($aggregate), true);
        $snapshot->setPayload($aggregateData);

        try {
            $this->em->persist($snapshot);
            $this->em->flush();
        } catch (OptimisticLockException $e) {
            $this->messageBus->dispatch(new Message(
                $e->getMessage(),
                $e->getCode(),
                null,
                $aggregate->getUuid(),
                $e
            ));
        } catch (UniqueConstraintViolationException $e) {
            $this->messageBus->dispatch(new Message(
                $e->getMessage(),
                $e->getCode(),
                null,
                $aggregate->getUuid(),
                $e
            ));
        }
    }
}
