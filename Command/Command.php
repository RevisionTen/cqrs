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
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $aggregateUuid;

    /**
     * @var array
     */
    public $payload;

    /**
     * @var callable
     */
    private $listener;

    /**
     * @var int
     */
    private $onVersion;

    /**
     * @var int
     */
    private $user;

    /**
     * Command constructor.
     *
     * @param int|null      $user
     * @param string|null   $uuid
     * @param string        $aggregateUuid
     * @param int           $onVersion
     * @param array         $payload
     * @param callable|null $listener
     */
    public function __construct(int $user, string $uuid = null, string $aggregateUuid, int $onVersion, array $payload, callable $listener = null)
    {
        if (null === $uuid) {
            $uuid = Uuid::uuid1()->toString();
        }

        $this->user = $user;
        $this->uuid = $uuid;
        $this->aggregateUuid = $aggregateUuid;
        $this->onVersion = $onVersion;
        $this->payload = $payload;
        $this->listener = $listener;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
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
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return callable
     */
    public function getListener(): callable
    {
        return $this->listener;
    }

    /**
     * @return int
     */
    public function getOnVersion(): int
    {
        return $this->onVersion;
    }

    /**
     * @return int
     */
    public function getUser(): int
    {
        return $this->user;
    }
}
