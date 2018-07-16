<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Interfaces;

interface EventInterface
{
    /**
     * EventInterface constructor.
     *
     * @param CommandInterface $command
     */
    public function __construct(CommandInterface $command);

    /**
     * Returns the Command class associated with this Event.
     *
     * @return string
     */
    public static function getCommandClass(): string;

    /**
     * Returns the Listener class associated with this Event.
     *
     * @return string
     */
    public static function getListenerClass(): string;

    /**
     * Returns the human readable message that describes this Event.
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     * Return the Event code.
     *
     * @return int
     */
    public static function getCode(): int;

    /**
     * Implemented by abstract Event class.
     *
     * @return CommandInterface
     */
    public function getCommand(): CommandInterface;
}
