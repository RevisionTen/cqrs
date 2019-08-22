<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Message;

use DateTimeImmutable;

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
     * @var array|null
     */
    public $context;

    /**
     * Message constructor.
     *
     * @param string      $message
     * @param int         $code
     * @param string|NULL $commandUuid
     * @param string|NULL $aggregateUuid
     * @param null        $exception
     * @param array|NULL  $context
     */
    public function __construct(string $message, int $code, string $commandUuid = null, string $aggregateUuid = null, $exception = null, array $context = null)
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
