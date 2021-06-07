<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Message;

use DateTimeImmutable;

class Message
{
    public string $message;

    public int $code = CODE_DEFAULT;

    public ?string $commandUuid;

    public ?string $aggregateUuid;

    public DateTimeImmutable $created;

    /**
     * @var object|null
     */
    public $exception;

    public ?array $context;

    public function __construct(string $message, int $code, ?string $commandUuid = null, ?string $aggregateUuid = null, $exception = null, ?array $context = null)
    {
        $this->message = $message;
        $this->code = $code;
        $this->commandUuid = $commandUuid;
        $this->aggregateUuid = $aggregateUuid;
        $this->exception = $exception;
        $this->context = $context;
        $this->created = new DateTimeImmutable();
    }
}
