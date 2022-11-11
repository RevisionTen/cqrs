<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Services;

use RevisionTen\CQRS\Message\Message;
use Psr\Log\LoggerInterface;
use function is_string;

class MessageBus
{
    protected LoggerInterface $logger;

    /**
     * @var Message[]
     */
    private array $messages = [];

    /**
     * @var object|string
     */
    private $env;

    public function __construct(LoggerInterface $logger, $env)
    {
        $this->logger = $logger;
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

        // Add message to debug log.
        $context = $message->context ?? [];
        $this->logger->debug($message->message, $context);
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
     * @return Message[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getMessagesJson(): array
    {
        $messages = [];
        $debug = is_string($this->env) ? 'dev' === $this->env : $this->env->isDebug();

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
     * @return Message[]
     */
    public function getMessagesByCommand($commandUuid): array
    {
        $messagesByUuid = [];

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
     * @return Message[]
     */
    public function getMessagesByAggregate($aggregateUuid): array
    {
        $messagesByUuid = [];

        foreach ($this->messages as $message) {
            if ($message->aggregateUuid === $aggregateUuid) {
                $messagesByUuid[] = $message;
            }
        }

        return $messagesByUuid;
    }
}
