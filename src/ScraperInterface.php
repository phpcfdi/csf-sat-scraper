<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper;

use Stringable;

interface ScraperInterface
{
    public function download(): Stringable;
}
