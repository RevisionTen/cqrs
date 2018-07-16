<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Tests\Examples\Command;

use RevisionTen\CQRS\Tests\Examples\Handler\PageCreateHandler;
use RevisionTen\CQRS\Tests\Examples\Model\Page;
use RevisionTen\CQRS\Command\Command;
use RevisionTen\CQRS\Interfaces\CommandInterface;

class PageCreateCommand extends Command implements CommandInterface
{
    /**
     * {@inheritdoc}
     */
    public function getHandlerClass(): string
    {
        return PageCreateHandler::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregateClass(): string
    {
        return Page::class;
    }
}
