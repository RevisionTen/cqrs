<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Interfaces;

interface CommandInterface
{
    /**
     * CommandInterface constructor.
     *
     * @param int         $user
     * @param string|NULL $commandUuid
     * @param string      $aggregateUuid
     * @param int         $onVersion
     * @param array       $payload
     */
    public function __construct(int $user, string $commandUuid = null, string $aggregateUuid, int $onVersion, array $payload);

    /**
     * Returns the Handler class associated with this Command.
     *
     * @return string
     */
    public static function getHandlerClass(): string;

    /**
     * Returns the Aggregate class associated with this Command.
     *
     * @return string
     */
    public static function getAggregateClass(): string;

    /**
     * Implemented by abstract Command class.
     *
     * @return array
     */
    public function getPayload(): array;

    /**
     * Implemented by abstract Command class.
     *
     * @return string
     */
    public function getAggregateUuid(): string;

    /**
     * Implemented by abstract Command class.
     *
     * @return string
     */
    public function getUuid(): string;

    /**
     * Implemented by abstract Command class.
     *
     * @return int
     */
    public function getOnVersion(): int;

    /**
     * Implemented by abstract Command class.
     *
     * @return int
     */
    public function getUser(): int;
}
