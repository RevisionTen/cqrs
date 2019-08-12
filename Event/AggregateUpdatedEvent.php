<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Event;

use RevisionTen\CQRS\Interfaces\EventInterface;
use Symfony\Contracts\EventDispatcher\Event;

class AggregateUpdatedEvent extends Event
{
    public const NAME = 'aggregate.updated';

    /**
     * @var EventInterface
     */
    protected $event;

    public function __construct(EventInterface $event)
    {
        $this->event = $event;
    }

    public function getEvent(): EventInterface
    {
        return $this->event;
    }
}
