<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Message;

class Message
{
    /**
     * @var string
     */
    public $message;

    /**
     * @var int
     */
    public $code = CODE_DEFAULT;

    /**
     * @var string|null
     */
    public $commandUuid;

    /**
     * @var string|null
     */
    public $aggregateUuid;

    /**
     * @var \DateTimeImmutable
     */
    public $created;

    /**
     * @var object|null
     */
    public $exception;

    /**
     * Message constructor.
     *
     * @param string $message
     * @param int    $code
     * @param null   $commandUuid
     * @param null   $aggregateUuid
     */
    public function __construct(string $message, int $code, $commandUuid = null, $aggregateUuid = null, $exception = null)
    {
        $this->message = $message;
        $this->code = $code;
        $this->commandUuid = $commandUuid;
        $this->aggregateUuid = $aggregateUuid;
        $this->created = new \DateTimeImmutable();
        $this->exception = $exception;
    }
}
