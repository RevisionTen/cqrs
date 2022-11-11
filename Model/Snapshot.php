<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use function is_string;
use function serialize;
use function unserialize;
use function json_decode;
use function json_encode;

/**
 * Class Snapshot.
 *
 * @ORM\Entity
 * @ORM\Table(name="snapshots", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="unique_version",
 *          columns={"version", "uuid"})
 * })
 */
class Snapshot
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", options={"collation": "utf8_unicode_ci"})
     */
    private string $uuid;

    /**
     * @ORM\Column(type="integer")
     */
    private int $version;

    /**
     * @ORM\Column(type="string", options={"collation": "utf8_unicode_ci"})
     */
    private string $aggregateClass;

    /**
     * @ORM\Column(type="text")
     */
    private string $aggregateData;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private DateTimeImmutable $aggregateCreated;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private DateTimeImmutable $aggregateModified;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private DateTimeImmutable $created;

    /**
     * @ORM\Column(type="text")
     */
    private string $history;

    public function __construct()
    {
        $this->created = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getAggregateClass(): string
    {
        return $this->aggregateClass;
    }

    public function setAggregateClass(string $aggregateClass): self
    {
        $this->aggregateClass = $aggregateClass;

        return $this;
    }

    public function getAggregate(): AggregateInterface
    {
        return unserialize($this->aggregateData, ['allowed_classes' => true]);
    }

    public function setAggregate(AggregateInterface $aggregate): self
    {
        $this->aggregateData = serialize($aggregate);

        return $this;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(DateTimeImmutable $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getAggregateCreated(): DateTimeImmutable
    {
        return $this->aggregateCreated;
    }

    public function setAggregateCreated(DateTimeImmutable $aggregateCreated): self
    {
        $this->aggregateCreated = $aggregateCreated;

        return $this;
    }

    public function getAggregateModified(): DateTimeImmutable
    {
        return $this->aggregateModified;
    }

    public function setAggregateModified(DateTimeImmutable $aggregateModified): self
    {
        $this->aggregateModified = $aggregateModified;

        return $this;
    }

    public function getHistory(): array
    {
        return json_decode($this->history, true);
    }

    public function setHistory(array $history): self
    {
        $this->history = json_encode($history);

        return $this;
    }
}
