<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Command;

use Ramsey\Uuid\Uuid;

/**
 * Class Command
 * All commands must be immutable (no setters).
 */
abstract class Command
{
    private string $uuid;

    private string $aggregateUuid;

    public array $payload;

    private int $onVersion;

    private int $user;

    public function __construct(int $user, ?string $commandUuid, string $aggregateUuid, int $onVersion, array $payload)
    {
        if (null === $commandUuid) {
            $commandUuid = Uuid::uuid1()->toString();
        }

        $this->user = $user;
        $this->uuid = $commandUuid;
        $this->aggregateUuid = $aggregateUuid;
        $this->onVersion = $onVersion;
        $this->payload = $payload;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getAggregateUuid(): string
    {
        return $this->aggregateUuid;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getOnVersion(): int
    {
        return $this->onVersion;
    }

    public function getUser(): int
    {
        return $this->user;
    }
}
