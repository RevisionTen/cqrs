<?php

declare(strict_types=1);

namespace RevisionTen\CQRS\Exception;

use Exception;
use Throwable;

class InterfaceException extends Exception implements ExceptionInterface
{
    public function __construct($message = '', $code = CODE_ERROR, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
