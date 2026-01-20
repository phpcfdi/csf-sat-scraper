<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;
use PhpCfdi\CsfSatScraper\HttpClientFactory;
use PHPUnit\Framework\TestCase;

class HttpClientFactoryTest extends TestCase
{
    public function testCreateReturnsClientInstance(): void
    {
        $client = HttpClientFactory::create();

        $this->assertInstanceOf(Client::class, $client);
    }

    public function testGetDefaultOptionsReturnsCorrectBaseUri(): void
    {
        $options = HttpClientFactory::getDefaultOptions();

        $this->assertEquals('https://login.siat.sat.gob.mx', $options['base_uri']);
    }

    public function testGetDefaultOptionsIncludesTimeouts(): void
    {
        $options = HttpClientFactory::getDefaultOptions();

        $this->assertEquals(30, $options[RequestOptions::TIMEOUT]);
        $this->assertEquals(10, $options[RequestOptions::CONNECT_TIMEOUT]);
    }

    public function testGetDefaultOptionsIncludesRedirectSettings(): void
    {
        /** @phpstan-var mixed[]&array{allow_redirects: array{max: int}} $options */
        $options = HttpClientFactory::getDefaultOptions();

        $this->assertArrayHasKey(RequestOptions::ALLOW_REDIRECTS, $options);
        $this->assertEquals(10, $options[RequestOptions::ALLOW_REDIRECTS]['max']);
    }

    public function testGetDefaultOptionsIncludesRequiredHeaders(): void
    {
        $options = HttpClientFactory::getDefaultOptions();

        $this->assertArrayHasKey(RequestOptions::HEADERS, $options);

        /** @phpstan-var array<string, string> $headers */
        $headers = $options[RequestOptions::HEADERS];
        $this->assertArrayHasKey('User-Agent', $headers);
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertArrayHasKey('Accept-Language', $headers);
        $this->assertArrayHasKey('Cache-Control', $headers);
        $this->assertArrayHasKey('Pragma', $headers);
    }

    public function testGetCriticalOptionsReturnsOnlyCriticalSettings(): void
    {
        $critical = HttpClientFactory::getCriticalOptions();

        $this->assertArrayHasKey('base_uri', $critical);

        // Should not include non-critical options
        $this->assertArrayNotHasKey(RequestOptions::TIMEOUT, $critical);
        $this->assertArrayNotHasKey(RequestOptions::HEADERS, $critical);
    }

    public function testValidateOptionsReturnsTrueForValidOptions(): void
    {
        $validOptions = [
            'base_uri' => 'https://login.siat.sat.gob.mx',
            RequestOptions::VERIFY => false,
            RequestOptions::TIMEOUT => 60, // Can be different
        ];

        $this->assertTrue(HttpClientFactory::validateOptions($validOptions));
    }

    public function testValidateOptionsReturnsFalseForInvalidBaseUri(): void
    {
        $invalidOptions = [
            'base_uri' => 'https://wrong-url.com',
        ];

        $this->assertFalse(HttpClientFactory::validateOptions($invalidOptions));
    }

    public function testValidateOptionsReturnsFalseWhenSSLVerificationEnabled(): void
    {
        $invalidOptions = [
            RequestOptions::VERIFY => true,
        ];

        $this->assertFalse(HttpClientFactory::validateOptions($invalidOptions));
    }

    public function testCreateMergesCustomOptionsWithDefaults(): void
    {
        $customOptions = [
            RequestOptions::TIMEOUT => 60,
        ];

        $client = HttpClientFactory::create($customOptions);

        $this->assertInstanceOf(Client::class, $client);
    }

    public function testCreatePreservesDefaultHeadersWhenCustomHeadersProvided(): void
    {
        $customOptions = [
            RequestOptions::HEADERS => [
                'Custom-Header' => 'custom-value',
            ],
        ];

        $client = HttpClientFactory::create($customOptions);

        $this->assertInstanceOf(Client::class, $client);
    }

    public function testGetDefaultOptionsIncludesCookieJar(): void
    {
        $options = HttpClientFactory::getDefaultOptions();

        $this->assertArrayHasKey(RequestOptions::COOKIES, $options);
        $this->assertInstanceOf(CookieJar::class, $options[RequestOptions::COOKIES]);
    }
}
