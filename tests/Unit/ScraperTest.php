<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Tests\Unit;

use PhpCfdi\CsfSatScraper\Scraper;
use GuzzleHttp\ClientInterface;
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
        $scraper = new Scraper(
            $this->mockClient,
            $this->mockCaptchaResolver,
            $this->testRfc,
            $this->testPassword
        );

        $this->assertSame($this->mockClient, $scraper->getClient());
    }
}
