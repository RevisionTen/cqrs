<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use function is_string;
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
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", options={"collation": "utf8_unicode_ci"})
     */
    private $uuid;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $version;

    /**
     * @var string
     * @ORM\Column(type="string", options={"collation": "utf8_unicode_ci"})
     */
    private $aggregateClass;

    /**
     * @var array
     *
     * @ORM\Column(type="text")
     */
    private $payload;

    /**
     * This replaces the payload field.
     *
     * @var AggregateInterface|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $aggregateData;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $aggregateCreated;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $aggregateModified;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $created;

    /**
     * @var array
     *
     * @ORM\Column(type="text")
     */
    private $history;

    public function __construct()
    {
        $this->created = new DateTimeImmutable();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     *
     * @return Snapshot
     */
    public function setUuid($uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param int $version
     *
     * @return Snapshot
     */
    public function setVersion($version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return string
     */
    public function getAggregateClass(): string
    {
        return $this->aggregateClass;
    }

    /**
     * @param string $aggregateClass
     *
     * @return Snapshot
     */
    public function setAggregateClass($aggregateClass): self
    {
        $this->aggregateClass = $aggregateClass;

        return $this;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return is_string($this->payload) ? json_decode($this->payload, true) : $this->payload;
    }

    /**
     * @param array $payload
     *
     * @return Snapshot
     */
    public function setPayload($payload): self
    {
        $this->payload = json_encode($payload);

        return $this;
    }

    /**
     * @return AggregateInterface|null
     */
    public function getAggregateData(): ?AggregateInterface
    {
        return is_string($this->aggregateData) ? unserialize($this->aggregateData, ['allowed_classes' => true]) : $this->aggregateData;
    }

    /**
     * @param AggregateInterface|null $aggregate
     *
     * @return Snapshot
     */
    public function setAggregateData(?AggregateInterface $aggregate): self
    {
        $this->aggregateData = serialize($aggregate);

        return $this;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    /**
     * @param \DateTimeImmutable $created
     *
     * @return Snapshot
     */
    public function setCreated($created): self
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getAggregateCreated(): DateTimeImmutable
    {
        return $this->aggregateCreated;
    }

    /**
     * @param \DateTimeImmutable $aggregateCreated
     *
     * @return Snapshot
     */
    public function setAggregateCreated($aggregateCreated): self
    {
        $this->aggregateCreated = $aggregateCreated;

        return $this;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getAggregateModified(): DateTimeImmutable
    {
        return $this->aggregateModified;
    }

    /**
     * @param \DateTimeImmutable $aggregateModified
     *
     * @return Snapshot
     */
    public function setAggregateModified($aggregateModified): self
    {
        $this->aggregateModified = $aggregateModified;

        return $this;
    }

    /**
     * @return array
     */
    public function getHistory(): array
    {
        return is_string($this->history) ? json_decode($this->history, true) : $this->history;
    }

    /**
     * @param array $history
     *
     * @return Snapshot
     */
    public function setHistory($history): self
    {
        $this->history = json_encode($history);

        return $this;
    }
}
