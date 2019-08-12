<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Tests\Examples\Handler;

use RevisionTen\CQRS\Exception\CommandValidationException;
use RevisionTen\CQRS\Tests\Examples\Event\PageCreateEvent;
use RevisionTen\CQRS\Tests\Examples\Model\Page;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;

final class PageCreateHandler implements HandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @var Page $aggregate
     */
    public function execute(EventInterface $event, AggregateInterface $aggregate): AggregateInterface
    {
        $payload = $event->getPayload();

        $aggregate->title = $payload['title'];

        return $aggregate;
    }

    /**
     * {@inheritdoc}
     */
    public function createEvent(CommandInterface $command): EventInterface
    {
        return new PageCreateEvent(
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

        if (empty($payload['title'])) {
            throw new CommandValidationException(
                'You must enter a title',
                CODE_BAD_REQUEST,
                NULL,
                $command
            );
        }

        if (0 !== $aggregate->getVersion()) {
            throw new CommandValidationException(
                'Aggregate already exists',
                CODE_CONFLICT,
                NULL,
                $command
            );
        }

        return true;
    }
}
