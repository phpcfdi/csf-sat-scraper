<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;
use PhpCfdi\CsfSatScraper\HttpClientFactory;
use PhpCfdi\CsfSatScraper\Url;
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
        $factory = new HttpClientFactory();
        $options = $factory->getDefaultOptions();

        $this->assertEquals(Url::$base, $options['base_uri']);
    }

    public function testGetDefaultOptionsIncludesTimeouts(): void
    {
        $factory = new HttpClientFactory();
        $options = $factory->getDefaultOptions();

        $this->assertEquals(30, $options[RequestOptions::TIMEOUT]);
        $this->assertEquals(10, $options[RequestOptions::CONNECT_TIMEOUT]);
    }

    public function testGetDefaultOptionsIncludesRedirectSettings(): void
    {
        $factory = new HttpClientFactory();
        /** @phpstan-var mixed[]&array{allow_redirects: array{max: int}} $options */
        $options = $factory->getDefaultOptions();

        $this->assertArrayHasKey(RequestOptions::ALLOW_REDIRECTS, $options);
        $this->assertEquals(10, $options[RequestOptions::ALLOW_REDIRECTS]['max']);
    }

    public function testGetDefaultOptionsIncludesRequiredHeaders(): void
    {
        $factory = new HttpClientFactory();
        $options = $factory->getDefaultOptions();

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
        $factory = new HttpClientFactory();
        $critical = $factory->getCriticalOptions();

        $this->assertArrayHasKey('base_uri', $critical);

        // Should not include non-critical options
        $this->assertArrayNotHasKey(RequestOptions::TIMEOUT, $critical);
        $this->assertArrayNotHasKey(RequestOptions::HEADERS, $critical);
    }

    public function testValidateOptionsReturnsTrueForValidOptions(): void
    {
        $validOptions = [
            'base_uri' => Url::$base,
            RequestOptions::VERIFY => false,
            RequestOptions::TIMEOUT => 60,
        ];

        $factory = new HttpClientFactory($validOptions);
        $this->assertTrue($factory->validateOptions());
    }

    public function testValidateOptionsReturnsFalseForInvalidBaseUri(): void
    {
        $invalidOptions = [
            'base_uri' => 'https://wrong-url.com',
        ];

        $factory = new HttpClientFactory($invalidOptions);
        $this->assertFalse($factory->validateOptions());
    }

    public function testCreateReplacesCustomOptionsWithDefaults(): void
    {
        $customOptions = [
            RequestOptions::TIMEOUT => 60,
        ];

        $factory = new HttpClientFactory($customOptions);
        $buildOptions = $factory->buildOptions();
        $this->assertSame($customOptions[RequestOptions::TIMEOUT], $buildOptions[RequestOptions::TIMEOUT]);
    }

    public function testCreatePreservesDefaultHeadersWhenCustomHeadersProvided(): void
    {
        $customOptions = [
            RequestOptions::HEADERS => [
                'Custom-Header' => 'custom-value',
            ],
        ];

        $factory = new HttpClientFactory($customOptions);
        $buildOptions = $factory->buildOptions();
        $this->assertIsArray($buildOptions[RequestOptions::HEADERS]);
        $this->assertArrayHasKey('Custom-Header', $buildOptions[RequestOptions::HEADERS]);
        $this->assertSame(
            $customOptions[RequestOptions::HEADERS]['Custom-Header'],
            $buildOptions[RequestOptions::HEADERS]['Custom-Header'],
        );
    }

    public function testGetDefaultOptionsIncludesCookieJar(): void
    {
        $factory = new HttpClientFactory();
        $options = $factory->getDefaultOptions();

        $this->assertArrayHasKey(RequestOptions::COOKIES, $options);
        $this->assertInstanceOf(CookieJar::class, $options[RequestOptions::COOKIES]);
    }

    public function testCheckSetterMethods(): void
    {
        $factory = new HttpClientFactory();
        $cookieJar = new CookieJar();
        $connectTimeout = 50;
        $timeout = 100;
        $verify = false;

        $options = $factory
            ->setConnectTimeout($connectTimeout)
            ->setTimeout($timeout)
            ->setVerify($verify)
            ->setCookieJar($cookieJar)
            ->buildOptions();

        $this->assertSame($cookieJar, $options[RequestOptions::COOKIES]);
        $this->assertSame($verify, $options[RequestOptions::VERIFY]);
        $this->assertSame($connectTimeout, $options[RequestOptions::CONNECT_TIMEOUT]);
        $this->assertSame($timeout, $options[RequestOptions::TIMEOUT]);
    }
}
