<?= "<?php\n"; ?>

declare(strict_types=1);

namespace <?= $bundleNamespace; ?>\Handler;

use <?= $bundleNamespace; ?>\Event\<?= $commandName; ?>Event;
use <?= $aggregateNamespace; ?>\<?= $aggregateClass; ?>;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;
use RevisionTen\CQRS\Message\Message;
use RevisionTen\CQRS\Handler\Handler;

final class <?= $commandName; ?>Handler extends Handler implements HandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @var <?= $aggregateClass; ?> $aggregate
     */
    public function execute(EventInterface $event, AggregateInterface $aggregate): AggregateInterface
    {
        $payload = $event->getPayload();

        // Change Aggregate state here.

        return $aggregate;
    }

    /**
     * {@inheritdoc}
     */
    public function createEvent(CommandInterface $command): EventInterface
    {
        return new <?= $commandName; ?>Event(
            $command->getAggregateUuid(),
            $command->getUuid(),
            $command->getOnVersion() + 1,
            $command->getUser(),
            $command->getPayload()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function validateCommand(CommandInterface $command, AggregateInterface $aggregate): bool
    {
        $payload = $command->getPayload();

        // Validate here.

        return true;
    }
}
