<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Interfaces;

use RevisionTen\CQRS\Exception\CommandValidationException;

interface HandlerInterface
{
    /**
     * Returns a new Event instance of the Event class associated with this Handler.
     *
     * @param CommandInterface $command
     *
     * @return EventInterface
     */
    public function createEvent(CommandInterface $command): EventInterface;

    /**
     * Returns true if the Command is valid, throws exception otherwise.
     *
     * @param CommandInterface   $command
     * @param AggregateInterface $aggregate
     *
     * @throws CommandValidationException
     *
     * @return bool
     */
    public function validateCommand(CommandInterface $command, AggregateInterface $aggregate): bool;

    /**
     * Executes the business logic this Handler implements.
     *
     * @param EventInterface     $event
     * @param AggregateInterface $aggregate
     *
     * @return AggregateInterface
     */
    public function execute(EventInterface $event, AggregateInterface $aggregate): AggregateInterface;
}
