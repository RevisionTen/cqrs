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
     * @param string|null   $commandUuid
     * @param string        $aggregateUuid
     * @param int           $onVersion
     * @param array         $payload
     *
     * @throws \Exception
     */
    public function __construct(int $user, string $commandUuid = null, string $aggregateUuid, int $onVersion, array $payload)
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
