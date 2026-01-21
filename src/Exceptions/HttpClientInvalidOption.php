<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Exceptions;

use RuntimeException;

class HttpClientInvalidOption extends RuntimeException
{
    public function __construct(
        private readonly string $optionName,
        private readonly mixed $optionValue,
    ) {
        parent::__construct(sprintf('Http Client option "%s" is not valid.', $this->optionName));
    }

    public function getOptionName(): string
    {
        return $this->optionName;
    }

    public function getOptionValue(): mixed
    {
        return $this->optionValue;
    }
}
