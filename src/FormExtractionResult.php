<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper;

readonly class FormExtractionResult
{
    /** @param array<string, string> $fields */
    public function __construct(private string $action, private array $fields)
    {
    }

    public function getAction(): string
    {
        return $this->action;
    }

    /** @return array<string, string> */
    public function getFields(): array
    {
        return $this->fields;
    }
}
