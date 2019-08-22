<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Tests\Examples\Event;

use RevisionTen\CQRS\Event\AggregateEvent;
use RevisionTen\CQRS\Tests\Examples\Handler\PageCreateHandler;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Tests\Examples\Model\Page;

class PageCreateEvent extends AggregateEvent implements EventInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getAggregateClass(): string
    {
        return Page::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function getHandlerClass(): string
    {
        return PageCreateHandler::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return 'Page Created';
    }
}
