<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Interfaces;

use RevisionTen\CQRS\Services\AggregateFactory;
use RevisionTen\CQRS\Services\MessageBus;

interface HandlerInterface
{
    /**
     * Handler constructor.
     *
     * @param MessageBus       $messageBus
     * @param AggregateFactory $aggregateFactory
     */
    public function __construct(MessageBus $messageBus, AggregateFactory $aggregateFactory);

    /**
     * Returns the Command class associated with this Handler.
     *
     * @return string
     */
    public static function getCommandClass(): string;

    /**
     * When the Handler is invokes it performs the following actions:
     * Get the Aggregate.
     * Check if the Command is valid.
     * Create an Event for the Command and push it onto the Aggregates pending Events.
     *
     * @param CommandInterface $command
     * @param array            $aggregates
     */
    public function __invoke(CommandInterface $command, array &$aggregates): void;

    /**
     * Returns a new Event instance of the Event class associated with this Handler.
     *
     * @param CommandInterface $command
     *
     * @return EventInterface
     */
    public function createEvent(CommandInterface $command): EventInterface;

    /**
     * Returns an Aggregate based on the provided uuid.
     *
     * @param string $uuid
     * @param string $aggregateClass
     * @param int    $user
     *
     * @return AggregateInterface
     */
    public function getAggregate(string $uuid, string $aggregateClass, int $user): AggregateInterface;

    /**
     * Returns true if the Command is valid, false otherwise.
     *
     * @param CommandInterface   $command
     * @param AggregateInterface $aggregate
     *
     * @return bool
     */
    public function validateCommand(CommandInterface $command, AggregateInterface $aggregate): bool;

    /**
     * A wrapper for the execute function.
     *
     * @param CommandInterface   $command
     * @param AggregateInterface $aggregate
     *
     * @return AggregateInterface
     */
    public function executeHandler(CommandInterface $command, AggregateInterface $aggregate): AggregateInterface;

    /**
     * Executes the business logic this Handler implements.
     *
     * @param CommandInterface   $command
     * @param AggregateInterface $aggregate
     *
     * @return AggregateInterface
     */
    public function execute(CommandInterface $command, AggregateInterface $aggregate): AggregateInterface;
}
