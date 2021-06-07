<?= "<?php\n"; ?>

declare(strict_types=1);

namespace <?= $bundleNamespace; ?>\Event;

use <?= $aggregateNamespace; ?>\<?= $aggregateClass; ?>;
use <?= $bundleNamespace; ?>\Handler\<?= $commandName; ?>Handler;
use RevisionTen\CQRS\Event\AggregateEvent;
use RevisionTen\CQRS\Interfaces\EventInterface;

final class <?= $commandName; ?>Event extends AggregateEvent implements EventInterface
{
    public static function getAggregateClass(): string
    {
        return <?= $aggregateClass; ?>::class;
    }

    public static function getHandlerClass(): string
    {
        return <?= $commandName; ?>Handler::class;
    }

    public function getMessage(): string
    {
        return '<?= $eventText; ?>';
    }
}
