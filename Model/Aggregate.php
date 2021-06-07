<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Model;

use DateTimeImmutable;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;

class Aggregate implements AggregateInterface
{
    public string $uuid;

    public ?DateTimeImmutable $created = null;

    public ?DateTimeImmutable $modified = null;

    private int $version = 0;

    private ?int $snapshotVersion = null;

    private ?int $streamVersion = null;

    /**
     * @var EventInterface[]
     */
    private array $pendingEvents = [];

    private array $history = [];

    public function __construct(string $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): AggregateInterface
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(int $version): AggregateInterface
    {
        $this->version = $version;

        return $this;
    }

    public function getSnapshotVersion(): ?int
    {
        return $this->snapshotVersion;
    }

    public function setSnapshotVersion(?int $snapshotVersion = null): AggregateInterface
    {
        $this->snapshotVersion = $snapshotVersion;

        return $this;
    }

    public function getStreamVersion(): ?int
    {
        return $this->streamVersion;
    }

    public function setStreamVersion(?int $streamVersion = null): AggregateInterface
    {
        $this->streamVersion = $streamVersion;

        return $this;
    }

    public function getCreated(): ?DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(DateTimeImmutable $created): AggregateInterface
    {
        $this->created = $created;

        return $this;
    }

    public function getModified(): ?DateTimeImmutable
    {
        return $this->modified;
    }

    public function setModified(DateTimeImmutable $modified): AggregateInterface
    {
        $this->modified = $modified;

        return $this;
    }

    public function getPendingEvents(): array
    {
        return $this->pendingEvents;
    }

    public function setPendingEvents(array $pendingEvents): AggregateInterface
    {
        $this->pendingEvents = $pendingEvents;

        return $this;
    }

    public function addPendingEvent(EventInterface $pendingEvent): AggregateInterface
    {
        $this->pendingEvents[] = $pendingEvent;

        return $this;
    }

    public function getHistory(): array
    {
        return $this->history;
    }

    public function setHistory(array $history): AggregateInterface
    {
        $this->history = $history;

        return $this;
    }

    public function addToHistory(array $historyEntry): AggregateInterface
    {
        $this->history[] = $historyEntry;

        return $this;
    }

    public function shouldTakeSnapshot(): bool
    {
        return $this->getStreamVersion() >= $this->getSnapshotVersion() + 10;
    }
}
