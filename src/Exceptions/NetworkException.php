<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Exceptions;

use RuntimeException;
use Throwable;

class NetworkException extends RuntimeException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
