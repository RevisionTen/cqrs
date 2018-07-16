<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Interfaces;

use RevisionTen\CQRS\Services\CommandBus;

interface ListenerInterface
{
    /**
     * This function is called when the Listener is dispatched.
     * Implement business logic here.
     *
     * @param CommandBus     $commandBus
     * @param EventInterface $event
     */
    public function __invoke(CommandBus $commandBus, EventInterface $event): void;
}
