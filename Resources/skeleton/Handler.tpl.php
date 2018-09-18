<?= "<?php\n"; ?>

declare(strict_types=1);

namespace <?= $bundleNamespace; ?>\Handler;

use <?= $bundleNamespace; ?>\Command\<?= $commandName; ?>Command;
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
    public function execute(CommandInterface $command, AggregateInterface $aggregate): AggregateInterface
    {
        $payload = $command->getPayload();

        // Change Aggregate state here.

        return $aggregate;
    }

    /**
     * {@inheritdoc}
     */
    public static function getCommandClass(): string
    {
        return <?= $commandName; ?>Command::class;
    }

    /**
     * {@inheritdoc}
     */
    public function createEvent(CommandInterface $command): EventInterface
    {
        return new <?= $commandName; ?>Event($command);
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
