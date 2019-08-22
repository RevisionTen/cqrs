<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Interfaces;

interface EventInterface
{
    /**
     * The type to aggregate this event is for.
     *
     * @return string
     */
    public static function getAggregateClass(): string;

    /**
     * The handler for this event.
     *
     * @return string
     */
    public static function getHandlerClass(): string;

    /**
     * Returns the human readable message that describes this Event.
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     * EventInterface constructor.
     *
     * @param string $aggregateUuid
     * @param string $commandUuid
     * @param int    $version
     * @param int    $user
     * @param array  $payload
     */
    public function __construct(string $aggregateUuid, string $commandUuid, int $version, int $user, array $payload);

    /**
     * @return string
     */
    public function getAggregateUuid(): string;

    /**
     * @return string
     */
    public function getCommandUuid(): string;

    /**
     * The version of the aggregate after this event happened to it.
     *
     * @return int
     */
    public function getVersion(): int;

    /**
     * @return int
     */
    public function getUser(): int;

    /**
     * @return array
     */
    public function getPayload(): array;
}
