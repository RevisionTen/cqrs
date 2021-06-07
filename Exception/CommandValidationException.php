<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Exception;

use Exception;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use Throwable;

class CommandValidationException extends Exception implements ExceptionInterface
{
    public ?CommandInterface $command;

    public function __construct($message = '', $code = CODE_ERROR, Throwable $previous = null, ?CommandInterface $command = null)
    {
        parent::__construct($message, $code, $previous);

        $this->command = $command;
    }
}
