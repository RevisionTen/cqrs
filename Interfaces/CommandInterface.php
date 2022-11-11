<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Interfaces;

interface CommandInterface
{
    public function __construct(int $user, ?string $commandUuid, string $aggregateUuid, int $onVersion, array $payload);

    /**
     * Returns the Aggregate class associated with this Command.
     *
     * @return string
     */
    public static function getAggregateClass(): string;

    /**
     * Returns the Handler class associated with this Command.
     *
     * @return string
     */
    public static function getHandlerClass(): string;

    // The following methods are implemented by abstract Command class:

    public function getAggregateUuid(): string;

    public function getOnVersion(): int;

    public function getPayload(): array;

    public function getUser(): int;

    public function getUuid(): string;
}
