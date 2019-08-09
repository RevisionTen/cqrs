<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Handler;

use RevisionTen\CQRS\Exception\InterfaceException;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Message\Message;
use RevisionTen\CQRS\Services\AggregateFactory;
use RevisionTen\CQRS\Services\MessageBus;

abstract class Handler
{
    /** @var MessageBus|null */
    public $messageBus;

    /** @var AggregateFactory|null */
    public $aggregateFactory;

    /**
     * Handler constructor.
     *
     * @param MessageBus       $messageBus
     * @param AggregateFactory $aggregateFactory
     */
    public function __construct(MessageBus $messageBus, AggregateFactory $aggregateFactory)
    {
        $this->messageBus = $messageBus;
        $this->aggregateFactory = $aggregateFactory;
    }

    /**
     * Returns a new Event instance of the Event class associated with this Handler.
     *
     * @param CommandInterface $command
     *
     * @return EventInterface
     */
    abstract public function createEvent(CommandInterface $command): EventInterface;

    /**
     * Returns true if the Command is valid, false otherwise.
     *
     * @param CommandInterface   $command
     * @param AggregateInterface $aggregate
     *
     * @return bool
     */
    abstract public function validateCommand(CommandInterface $command, AggregateInterface $aggregate): bool;

    /**
     * Executes the business logic this Handler implements.
     *
     * @param EventInterface     $event
     * @param AggregateInterface $aggregate
     *
     * @return AggregateInterface
     */
    abstract public function execute(EventInterface $event, AggregateInterface $aggregate): AggregateInterface;

    /**
     * A wrapper for the execute function.
     *
     * @param EventInterface     $event
     * @param AggregateInterface $aggregate
     *
     * @return AggregateInterface
     */
    public function executeHandler(EventInterface $event, AggregateInterface $aggregate): AggregateInterface
    {
        /* Execute method is implemented in final class */
        return $this->execute($event, $aggregate);
    }

    /**
     * Returns an Aggregate based on the provided uuid.
     *
     * @param string $uuid
     * @param string $aggregateClass
     * @param int    $user
     *
     * @return AggregateInterface
     *
     * @throws \Exception
     */
    public function getAggregate(string $uuid, string $aggregateClass, int $user): AggregateInterface
    {
        return $this->aggregateFactory->build($uuid, $aggregateClass, null, $user);
    }

    /**
     * When the Handler is invokes it performs the following actions:
     * Get the Aggregate.
     * Check if the Command is valid.
     * Create an Event for the Command and push it onto the Aggregates pending Events.
     *
     * @param CommandInterface $command
     * @param array            $aggregates
     *
     * @throws \Exception
     */
    public function __invoke(CommandInterface $command, array &$aggregates): void
    {
        $aggregateClass = $command::getAggregateClass();
        $aggregateUuid = $command->getAggregateUuid();
        $user = $command->getUser();

        // Get Aggregate.
        $aggregate = $this->getAggregate($aggregateUuid, $aggregateClass, $user);

        // Check if commands input is valid.
        $validCommand = $this->validateCommand($command, $aggregate);

        // Check if the Aggregate and target Version matches.
        $versionMatches = $aggregate->getVersion() === $command->getOnVersion();

        if (!$versionMatches) {
            // Version does not match.
            $this->messageBus->dispatch(new Message(
                'Aggregate target version is outdated or does not exist',
                CODE_CONFLICT,
                $command->getUuid(),
                $aggregateUuid,
                null
            ));
        } elseif ($validCommand) {
            try {
                /**
                 * Create Event for Command.
                 *
                 * @var EventInterface $event
                 */
                $event = $this->createEvent($command);

                if ($event instanceof EventInterface) {
                    // Apply Event to Aggregate.
                    $aggregate = $this->aggregateFactory->apply($aggregate, $event);

                    $aggregates[] = $aggregate;
                } else {
                    throw new InterfaceException(\get_class($event).' must implement '.EventInterface::class);
                }
            } catch (InterfaceException $e) {
                $this->messageBus->dispatch(new Message(
                    $e->getMessage(),
                    $e->getCode(),
                    $command->getUuid(),
                    $aggregateUuid,
                    $e
                ));
            }
        } else {
            // Handle invalid command.
            $this->messageBus->dispatch(new Message(
                'Invalid Command',
                CODE_BAD_REQUEST,
                $command->getUuid(),
                $aggregateUuid,
                null
            ));
        }
    }
}
