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

    public function __construct(string $aggregateUuid, string $commandUuid, int $version, int $user, array $payload);

    public function getAggregateUuid(): string;

    public function getCommandUuid(): string;

    /**
     * The version of the aggregate after this event happened to it.
     *
     * @return int
     */
    public function getVersion(): int;

    public function getUser(): int;

    public function getPayload(): array;
}
