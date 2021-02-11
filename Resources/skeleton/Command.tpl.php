<?= "<?php\n"; ?>

declare(strict_types=1);

namespace <?= $bundleNamespace; ?>\Command;

use <?= $bundleNamespace; ?>\Handler\<?= $commandName; ?>Handler;
use <?= $aggregateNamespace; ?>\<?= $aggregateClass; ?>;
use RevisionTen\CQRS\Command\Command;
use RevisionTen\CQRS\Interfaces\CommandInterface;

final class <?= $commandName; ?>Command extends Command implements CommandInterface
{
    public static function getHandlerClass(): string
    {
        return <?= $commandName; ?>Handler::class;
    }

    public static function getAggregateClass(): string
    {
        return <?= $aggregateClass; ?>::class;
    }
}
