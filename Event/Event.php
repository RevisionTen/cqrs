<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Event;

use RevisionTen\CQRS\Interfaces\CommandInterface;

abstract class Event
{
    /**
     * @var CommandInterface
     */
    public $command;

    /**
     * EventInterface constructor.
     *
     * @param CommandInterface $command
     */
    public function __construct(CommandInterface $command)
    {
        $this->command = $command;
    }

    /**
     * @return CommandInterface
     */
    public function getCommand(): CommandInterface
    {
        return $this->command;
    }
}
