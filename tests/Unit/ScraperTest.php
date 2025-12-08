<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Tests\Unit;

use GuzzleHttp\ClientInterface;
use PhpCfdi\CsfSatScraper\Scraper;
use PhpCfdi\ImageCaptchaResolver\CaptchaResolverInterface;
use PHPUnit\Framework\TestCase;

class ScraperTest extends TestCase
{
    private ClientInterface $mockClient;

    private CaptchaResolverInterface $mockCaptchaResolver;

    private string $testRfc = 'XAXX010101000';

    private string $testPassword = 'testPassword123';

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(ClientInterface::class);
        $this->mockCaptchaResolver = $this->createMock(CaptchaResolverInterface::class);
    }

    public function testCanBeInstantiated(): void
    {
        $scraper = Scraper::create(
            $this->mockClient,
            $this->mockCaptchaResolver,
            $this->testRfc,
            $this->testPassword,
        );

        $this->assertSame($this->mockClient, $scraper->client);
        $this->assertSame($this->mockCaptchaResolver, $scraper->captchaService->captchaSolver);
        $this->assertSame($this->testRfc, $scraper->authService->rfc);
        $this->assertSame($this->testPassword, $scraper->authService->password);
    }
}
