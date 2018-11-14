<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Services;

use RevisionTen\CQRS\Message\Message;

class MessageBus
{
    /**
     * @var array
     */
    private $messages = [];

    /**
     * @var object|string
     */
    private $env;

    public function __construct($env)
    {
        $this->env = $env;
    }

    /**
     * Dispatches a Message to the Message Bus.
     *
     * @param Message $message
     */
    public function dispatch(Message $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * Clears the Message Bus.
     */
    public function clear(): void
    {
        $this->messages = [];
    }

    /**
     * Returns an array of messages.
     *
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getMessagesJson(): array
    {
        $messages = [];
        $debug = \is_string($this->env) && 'dev' === $this->env ?: $this->env->isDebug();

        foreach ($this->messages as $message) {
            if ($debug && $message->exception) {
                // Throw the exception in debug mode.
                throw $message->exception;
            }

            $messages[] = [
                'message' => $message->message,
                'code' => $message->code,
                'commandUuid' => $message->commandUuid,
                'aggregateUuid' => $message->aggregateUuid,
                'created' => $message->created,
                'exception' => $message->exception ? $message->exception->getTraceAsString() : null,
            ];
        }

        return $messages;
    }

    /**
     * Returns an array of messages.
     *
     * @param $commandUuid
     *
     * @return array
     */
    public function getMessagesByCommand($commandUuid): array
    {
        $messagesByUuid = [];
        /** @var Message $message */
        foreach ($this->messages as $message) {
            if ($message->commandUuid === $commandUuid) {
                $messagesByUuid[] = $message;
            }
        }

        return $messagesByUuid;
    }

    /**
     * Returns an array of messages.
     *
     * @param $aggregateUuid
     *
     * @return array
     */
    public function getMessagesByAggregate($aggregateUuid): array
    {
        $messagesByUuid = [];
        /** @var Message $message */
        foreach ($this->messages as $message) {
            if ($message->aggregateUuid === $aggregateUuid) {
                $messagesByUuid[] = $message;
            }
        }

        return $messagesByUuid;
    }
}
