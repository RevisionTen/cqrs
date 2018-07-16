<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Interfaces;

interface CommandInterface
{
    /**
     * Returns the Handler class associated with this Command.
     *
     * @return string
     */
    public function getHandlerClass(): string;

    /**
     * Returns the Aggregate class associated with this Command.
     *
     * @return string
     */
    public function getAggregateClass(): string;

    /**
     * Implemented by abstract Command class.
     *
     * @return array
     */
    public function getPayload();

    /**
     * Implemented by abstract Command class.
     *
     * @return string
     */
    public function getAggregateUuid();

    /**
     * Implemented by abstract Command class.
     *
     * @return string
     */
    public function getUuid();

    /**
     * Implemented by abstract Command class.
     *
     * @return callable
     */
    public function getListener();

    /**
     * Implemented by abstract Command class.
     *
     * @return int
     */
    public function getOnVersion();

    /**
     * Implemented by abstract Command class.
     *
     * @return int
     */
    public function getUser();
}
