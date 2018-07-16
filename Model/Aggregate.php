<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Model;

use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;

class Aggregate implements AggregateInterface
{
    /**
     * @var string
     */
    public $uuid;

    /**
     * @var \DateTimeImmutable
     */
    public $created;

    /**
     * @var \DateTimeImmutable
     */
    public $modified;

    /**
     * @var int
     */
    private $version = 0;

    /**
     * @var int
     */
    private $snapshotVersion;

    /**
     * @var int
     */
    private $streamVersion;

    /**
     * @var array
     */
    private $pendingEvents = [];

    /**
     * @var array
     */
    private $history = [];

    /**
     * Aggregate constructor.
     *
     * @param string $uuid
     */
    public function __construct(string $uuid)
    {
        $this->uuid = $uuid;
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
     * @return AggregateInterface
     */
    public function setUuid(string $uuid): AggregateInterface
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getVersion(): ?int
    {
        return $this->version;
    }

    /**
     * @param $version
     *
     * @return AggregateInterface
     */
    public function setVersion($version): AggregateInterface
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSnapshotVersion(): ?int
    {
        return $this->snapshotVersion;
    }

    /**
     * @param int $snapshotVersion
     *
     * @return AggregateInterface
     */
    public function setSnapshotVersion($snapshotVersion = null): AggregateInterface
    {
        $this->snapshotVersion = $snapshotVersion;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getStreamVersion(): ?int
    {
        return $this->streamVersion;
    }

    /**
     * @param int $streamVersion
     *
     * @return AggregateInterface
     */
    public function setStreamVersion($streamVersion): AggregateInterface
    {
        $this->streamVersion = $streamVersion;

        return $this;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getCreated(): ?\DateTimeImmutable
    {
        return $this->created;
    }

    /**
     * @param \DateTimeImmutable $created
     *
     * @return AggregateInterface
     */
    public function setCreated(\DateTimeImmutable $created): AggregateInterface
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getModified(): ?\DateTimeImmutable
    {
        return $this->modified;
    }

    /**
     * @param \DateTimeImmutable $modified
     *
     * @return AggregateInterface
     */
    public function setModified(\DateTimeImmutable $modified): AggregateInterface
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * Returns an array of pending Events.
     *
     * @return array
     */
    public function getPendingEvents(): array
    {
        return $this->pendingEvents;
    }

    /**
     * @param array $pendingEvents
     *
     * @return AggregateInterface
     */
    public function setPendingEvents(array $pendingEvents): AggregateInterface
    {
        $this->pendingEvents = $pendingEvents;

        return $this;
    }

    /**
     * @param EventInterface $pendingEvent
     *
     * @return AggregateInterface
     */
    public function addPendingEvent(EventInterface $pendingEvent): AggregateInterface
    {
        $this->pendingEvents[] = $pendingEvent;

        return $this;
    }

    /**
     * @return array
     */
    public function getHistory(): array
    {
        return $this->history;
    }

    /**
     * @param array $history
     *
     * @return AggregateInterface
     */
    public function setHistory(array $history): AggregateInterface
    {
        $this->history = $history;

        return $this;
    }

    /**
     * @param array $historyEntry
     *
     * @return AggregateInterface
     */
    public function addToHistory(array $historyEntry): AggregateInterface
    {
        $this->history[] = $historyEntry;

        return $this;
    }

    /**
     * Decides if a Snapshot should be taken.
     *
     * @return bool
     */
    public function shouldTakeSnapshot(): bool
    {
        return $this->getStreamVersion() >= $this->getSnapshotVersion() + 10;
    }
}
