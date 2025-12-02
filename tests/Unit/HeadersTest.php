<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Tests\Unit;

use PhpCfdi\CsfSatScraper\Headers;
use PHPUnit\Framework\TestCase;

class HeadersTest extends TestCase
{
    public function testGetHeadersReturnsArrayWithAllRequiredHeaders(): void
    {
        $headers = Headers::getHeaders();

        $this->assertIsArray($headers);
        $this->assertCount(15, $headers);

        $this->assertArrayHasKey('Accept', $headers);
        $this->assertArrayHasKey('User-Agent', $headers);
        $this->assertArrayHasKey('Sec-Fetch-Site', $headers);
        $this->assertArrayHasKey('Connection', $headers);

        foreach ($headers as $value) {
            $this->assertIsString($value);
        }
    }

    public function testGetHeadersWithDefaultSecFetchSite(): void
    {
        $headers = Headers::getHeaders();

        $this->assertEquals('none', $headers['Sec-Fetch-Site']);
    }

    public function testGetHeadersWithCustomSecFetchSite(): void
    {
        $headers = Headers::getHeaders('same-origin');

        $this->assertEquals('same-origin', $headers['Sec-Fetch-Site']);
    }
}
