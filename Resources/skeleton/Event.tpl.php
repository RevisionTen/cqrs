<?= "<?php\n"; ?>

declare(strict_types=1);

namespace <?= $bundleNamespace; ?>\Event;

use <?= $aggregateNamespace; ?>\<?= $aggregateClass; ?>;
use <?= $bundleNamespace; ?>\Handler\<?= $commandName; ?>Handler;
use RevisionTen\CQRS\Event\AggregateEvent;
use RevisionTen\CQRS\Interfaces\EventInterface;

final class <?= $commandName; ?>Event extends AggregateEvent implements EventInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getAggregateClass(): string
    {
        return <?= $aggregateClass; ?>::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function getHandlerClass(): string
    {
        return <?= $commandName; ?>Handler::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return '<?= $eventText; ?>';
    }
}
