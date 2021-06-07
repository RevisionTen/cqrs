<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Interfaces;

use DateTimeImmutable;

interface AggregateInterface
{
    public function getUuid(): string;

    public function setUuid(string $uuid): self;

    public function __construct(string $uuid);

    public function getVersion(): ?int;

    public function setVersion(int $version): self;

    public function getSnapshotVersion(): ?int;

    public function setSnapshotVersion(?int $snapshotVersion = null): self;

    public function getStreamVersion(): ?int;

    public function setStreamVersion(int $streamVersion): self;

    public function getCreated(): ?DateTimeImmutable;

    public function setCreated(DateTimeImmutable $created): self;

    public function getModified(): ?DateTimeImmutable;

    public function setModified(DateTimeImmutable $modified): self;

    /**
     * @return EventInterface[]
     */
    public function getPendingEvents(): array;

    /**
     * @param EventInterface[] $pendingEvents
     *
     * @return AggregateInterface
     */
    public function setPendingEvents(array $pendingEvents): self;

    /**
     * @param EventInterface $pendingEvent
     *
     * @return AggregateInterface
     */
    public function addPendingEvent(EventInterface $pendingEvent): self;

    public function getHistory(): array;

    public function setHistory(array $history): self;

    public function addToHistory(array $historyEntry): self;

    /**
     * Decides if a Snapshot should be taken.
     *
     * @return bool
     */
    public function shouldTakeSnapshot(): bool;
}
