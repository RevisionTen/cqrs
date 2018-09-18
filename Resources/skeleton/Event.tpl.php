<?= "<?php\n"; ?>

declare(strict_types=1);

namespace <?= $bundleNamespace; ?>\Event;

use <?= $bundleNamespace; ?>\Command\<?= $commandName; ?>Command;
use <?= $bundleNamespace; ?>\Listener\<?= $commandName; ?>Listener;
use RevisionTen\CQRS\Event\Event;
use RevisionTen\CQRS\Interfaces\EventInterface;

final class <?= $commandName; ?>Event extends Event implements EventInterface
{
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
    public static function getListenerClass(): string
    {
        return <?= $commandName; ?>Listener::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return '<?= $eventText; ?>';
    }

    /**
     * {@inheritdoc}
     */
    public static function getCode(): int
    {
        return CODE_OK;
    }
}
