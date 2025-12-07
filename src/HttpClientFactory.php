<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\RequestOptions;

class HttpClientFactory
{
    public static function create(array $options = []): ClientInterface
    {
        $defaultOptions = self::getDefaultOptions();
        $mergedOptions = array_merge_recursive($defaultOptions, $options);
        return new Client($mergedOptions);
    }

    public static function createWithCookies(?CookieJarInterface $cookieJar = null): ClientInterface
    {
        return self::create([
            RequestOptions::COOKIES => $cookieJar ?? new CookieJar(),
        ]);
    }

    public static function createWithTimeout(int $timeout = 30, int $connectTimeout = 10): ClientInterface
    {
        return self::create([
            RequestOptions::TIMEOUT => $timeout,
            RequestOptions::CONNECT_TIMEOUT => $connectTimeout,
        ]);
    }

    public static function getDefaultOptions(): array
    {
        return [
            'base_uri' => URL::$base,
            RequestOptions::COOKIES => new CookieJar(),

            RequestOptions::TIMEOUT => 30,
            RequestOptions::CONNECT_TIMEOUT => 10,

            RequestOptions::ALLOW_REDIRECTS => [
                'max' => 10,
            ],

            RequestOptions::HEADERS => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'es-MX,es;q=0.9,en;q=0.7',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
            ],
        ];
    }

    public static function getCriticalOptions(): array
    {
        return [
            'base_uri' => URL::$base,
        ];
    }

    public static function validateOptions(array $options): bool
    {
        $critical = self::getCriticalOptions();

        if (isset($options['base_uri']) && $options['base_uri'] !== $critical['base_uri']) {
            return false;
        }

        if (isset($options[RequestOptions::VERIFY]) && false !== $options[RequestOptions::VERIFY]) {
            return false;
        }

        return true;
    }
}
