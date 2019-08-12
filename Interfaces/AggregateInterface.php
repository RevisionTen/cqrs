<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Interfaces;

use DateTimeImmutable;

interface AggregateInterface
{
    /**
     * Returns the aggregates uuid.
     *
     * @return string
     */
    public function getUuid(): string;

    /**
     * @param string $uuid
     *
     * @return AggregateInterface
     */
    public function setUuid(string $uuid): self;

    /**
     * Aggregate constructor.
     *
     * @param string $uuid
     */
    public function __construct(string $uuid);

    /**
     * @return int|null
     */
    public function getVersion(): ?int;

    /**
     * @param $version
     *
     * @return AggregateInterface
     */
    public function setVersion($version): self;

    /**
     * @return int|null
     */
    public function getSnapshotVersion(): ?int;

    /**
     * @param int|null $snapshotVersion
     *
     * @return AggregateInterface
     */
    public function setSnapshotVersion($snapshotVersion = null): self;

    /**
     * @return int|null
     */
    public function getStreamVersion(): ?int;

    /**
     * @param int $streamVersion
     *
     * @return AggregateInterface
     */
    public function setStreamVersion($streamVersion): self;

    /**
     * @return \DateTimeImmutable
     */
    public function getCreated(): ?DateTimeImmutable;

    /**
     * @param \DateTimeImmutable $created
     *
     * @return AggregateInterface
     */
    public function setCreated(DateTimeImmutable $created): self;

    /**
     * @return \DateTimeImmutable
     */
    public function getModified(): ?DateTimeImmutable;

    /**
     * @param \DateTimeImmutable $modified
     *
     * @return AggregateInterface
     */
    public function setModified(DateTimeImmutable $modified): self;

    /**
     * Returns an array of pending Events.
     *
     * @return array
     */
    public function getPendingEvents(): array;

    /**
     * @param array $pendingEvents
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

    /**
     * @return array
     */
    public function getHistory(): array;

    /**
     * @param array $history
     *
     * @return AggregateInterface
     */
    public function setHistory(array $history): self;

    /**
     * @param array $historyEntry
     *
     * @return AggregateInterface
     */
    public function addToHistory(array $historyEntry): self;

    /**
     * Decides if a Snapshot should be taken.
     *
     * @return bool
     */
    public function shouldTakeSnapshot(): bool;
}
