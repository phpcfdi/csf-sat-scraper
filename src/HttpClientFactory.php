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
    /** @phpstan-param mixed[] $customOptions */
    public function __construct(private array $customOptions = [])
    {
    }

    /** @phpstan-param mixed[] $options */
    public static function create(array $options = []): ClientInterface
    {
        $factory = new self($options);
        return $factory->build();
    }

    public function build(): ClientInterface
    {
        $options = $this->buildOptions();
        $this->checkOptions($options);
        return new Client($options);
    }

    /** @phpstan-return mixed[] $options */
    public function buildOptions(): array
    {
        $defaultOptions = $this->getDefaultOptions();
        return $this->mergeOrReplace($defaultOptions, $this->customOptions);
    }

    /** @return $this */
    public function setCookieJar(CookieJarInterface $cookieJar): self
    {
        $this->customOptions[RequestOptions::COOKIES] = $cookieJar;
        return $this;
    }

    /** @return $this */
    public function setConnectTimeout(int $timeout): self
    {
        $this->customOptions[RequestOptions::CONNECT_TIMEOUT] = $timeout;
        return $this;
    }

    /** @return $this */
    public function setTimeout(int $timeout): self
    {
        $this->customOptions[RequestOptions::TIMEOUT] = $timeout;
        return $this;
    }

    /** @return $this */
    public function setVerify(bool $verify): self
    {
        $this->customOptions[RequestOptions::VERIFY] = $verify;
        return $this;
    }

    /** @phpstan-return mixed[] */
    public static function getDefaultOptions(): array
    {
        return self::mergeOrReplace([
            RequestOptions::COOKIES => new CookieJar(),
            RequestOptions::TIMEOUT => 30,
            RequestOptions::CONNECT_TIMEOUT => 10,
            RequestOptions::ALLOW_REDIRECTS => ['max' => 10],
            RequestOptions::HEADERS => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'es-MX,es;q=0.9,en;q=0.7',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
            ],
            RequestOptions::VERIFY => true,
        ], self::getCriticalOptions());
    }

    /** @phpstan-return mixed[] */
    public static function getCriticalOptions(): array
    {
        return [
            'base_uri' => Url::$base,
        ];
    }

    public function validateOptions(): bool
    {
        try {
            $options = $this->buildOptions();
            $this->checkOptions($options);
            return true;
        } catch (Exceptions\HttpClientInvalidOption) {
            return false;
        }
    }

    /**
     * @phpstan-param mixed[] $options
     * @throws Exceptions\HttpClientInvalidOption when an invalid option is detected
     */
    public function checkOptions(array $options): void
    {
        $critical = $this->getCriticalOptions();
        foreach ($critical as $name => $value) {
            if (isset($options[$name]) && $options[$name] !== $value) {
                throw new Exceptions\HttpClientInvalidOption($name, $value);
            }
        }
    }

    /**
     * @phpstan-param mixed[] $base
     * @phpstan-param mixed[] $replacements
     * @phpstan-return mixed[]
     */
    private static function mergeOrReplace(array $base, array $replacements): array
    {
        foreach ($replacements as $key => $value) {
            if (isset($base[$key]) && is_array($base[$key]) && is_array($value)) {
                $base[$key] = self::mergeOrReplace($base[$key], $value);
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }
}
