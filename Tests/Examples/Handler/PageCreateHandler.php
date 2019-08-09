<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Tests\Examples\Handler;

use RevisionTen\CQRS\Tests\Examples\Event\PageCreateEvent;
use RevisionTen\CQRS\Tests\Examples\Model\Page;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;
use RevisionTen\CQRS\Message\Message;
use RevisionTen\CQRS\Handler\Handler;

final class PageCreateHandler extends Handler implements HandlerInterface
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
            $this->messageBus->dispatch(new Message(
                'You must enter a title',
                CODE_BAD_REQUEST,
                $command->getUuid(),
                $command->getAggregateUuid()
            ));

            return false;
        }

        if (0 !== $aggregate->getVersion()) {
            $this->messageBus->dispatch(new Message(
                'Aggregate already exists',
                CODE_CONFLICT,
                $command->getUuid(),
                $command->getAggregateUuid()
            ));

            return false;
        }

        return true;
    }
}
