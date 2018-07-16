<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Tests\Examples\Event;

use RevisionTen\CQRS\Tests\Examples\Command\PageCreateCommand;
use RevisionTen\CQRS\Tests\Examples\Listener\PageCreateListener;
use RevisionTen\CQRS\Event\Event;
use RevisionTen\CQRS\Interfaces\EventInterface;

class PageCreateEvent extends Event implements EventInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getCommandClass(): string
    {
        return PageCreateCommand::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function getListenerClass(): string
    {
        return PageCreateListener::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return 'Page Created';
    }

    /**
     * {@inheritdoc}
     */
    public static function getCode(): int
    {
        return CODE_CREATED;
    }
}
