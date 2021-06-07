<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Event;

use \Symfony\Contracts\EventDispatcher\Event;

abstract class AggregateEvent extends Event
{
    protected string $aggregateUuid;

    protected string $commandUuid;

    protected int $version;

    protected int $user;

    protected array $payload;

    public function __construct(string $aggregateUuid, string $commandUuid, int $version, int $user, array $payload)
    {
        $this->aggregateUuid = $aggregateUuid;
        $this->commandUuid = $commandUuid;
        $this->version = $version;
        $this->user = $user;
        $this->payload = $payload;
    }

    public function getAggregateUuid(): string
    {
        return $this->aggregateUuid;
    }

    public function getCommandUuid(): string
    {
        return $this->commandUuid;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getUser(): int
    {
        return $this->user;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
