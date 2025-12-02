<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper;

readonly class FormExtractionResult
{
    public function __construct(private string $action, private array $fields)
    {
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getFields(): array
    {
        return $this->fields;
    }
}
