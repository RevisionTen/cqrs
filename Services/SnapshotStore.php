<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Services;

use Exception;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Message\Message;
use RevisionTen\CQRS\Model\Snapshot;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use function get_class;

class SnapshotStore
{
    private EntityManagerInterface $em;

    private MessageBus $messageBus;

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
        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->eq('uuid', $uuid));

        if (null !== $max_version) {
            $criteria->andWhere(Criteria::expr()->lte('version', $max_version));
        }

        $criteria->orderBy(['version' => Criteria::DESC]);

        /**
         * @var \Doctrine\ORM\EntityRepository $snapshotRepository
         */
        $snapshotRepository = $this->em->getRepository(Snapshot::class);

        $snapshot = $snapshotRepository->matching($criteria)->first();

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
        $snapshot->setAggregate($aggregate);

        try {
            $this->em->persist($snapshot);
            $this->em->flush();
        } catch (Exception $e) {
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
