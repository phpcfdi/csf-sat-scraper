<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper;

readonly class FormExtractionResult
{
    /** @param array<string, string> $fields */
    public function __construct(
        public string $action,
        public array $fields,
    ) {
    }
}
