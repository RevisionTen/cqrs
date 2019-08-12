<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Exception;

use RevisionTen\CQRS\Interfaces\CommandInterface;

class CommandValidationException extends \Exception implements ExceptionInterface
{
    public $command;

    public function __construct($message = '', $code = CODE_ERROR, \Throwable $previous = null, ?CommandInterface $command = null)
    {
        parent::__construct($message, $code, $previous);

        $this->command = $command;
    }
}
