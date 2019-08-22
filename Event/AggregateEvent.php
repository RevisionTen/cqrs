<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Event;

use \Symfony\Contracts\EventDispatcher\Event;

abstract class AggregateEvent extends Event
{
    /** @var string */
    protected $aggregateUuid;

    /** @var string */
    protected $commandUuid;

    /** @var int */
    protected $version;

    /** @var int */
    protected $user;

    /** @var array */
    protected $payload;

    public function __construct(string $aggregateUuid, string $commandUuid, int $version, int $user, array $payload)
    {
        $this->aggregateUuid = $aggregateUuid;
        $this->commandUuid = $commandUuid;
        $this->version = $version;
        $this->user = $user;
        $this->payload = $payload;
    }

    /**
     * @return string
     */
    public function getAggregateUuid(): string
    {
        return $this->aggregateUuid;
    }

    /**
     * @return string
     */
    public function getCommandUuid(): string
    {
        return $this->commandUuid;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @return int
     */
    public function getUser(): int
    {
        return $this->user;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
