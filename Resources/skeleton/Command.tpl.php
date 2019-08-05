<?= "<?php\n"; ?>

declare(strict_types=1);

namespace <?= $bundleNamespace; ?>\Command;

use <?= $bundleNamespace; ?>\Handler\<?= $commandName; ?>Handler;
use <?= $aggregateNamespace; ?>\<?= $aggregateClass; ?>;
use RevisionTen\CQRS\Command\Command;
use RevisionTen\CQRS\Interfaces\CommandInterface;

final class <?= $commandName; ?>Command extends Command implements CommandInterface
{
    public const HANDLER = <?= $commandName; ?>Handler::class;
    public const AGGREGATE = <?= $aggregateClass; ?>::class;

    /**
     * {@inheritdoc}
     */
    public function getHandlerClass(): string
    {
        return self::HANDLER;
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregateClass(): string
    {
        return self::AGGREGATE;
    }
}
