<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper;

interface ScraperInterface
{
    public function download(): string;
}
