<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Tests\Unit\Services;

use PhpCfdi\CsfSatScraper\Services\CaptchaService;
use PhpCfdi\ImageCaptchaResolver\CaptchaResolverInterface;
use PhpCfdi\ImageCaptchaResolver\CaptchaAnswerInterface;
use PHPUnit\Framework\TestCase;

class CaptchaServiceTest extends TestCase
{
    public function testResolveCaptchaFromHtml(): void
    {
        $expectedValue = 'ABC123';

        $mockAnswer = new class ($expectedValue) implements CaptchaAnswerInterface {
            public function __construct(private string $value)
            {
            }

            public function getValue(): string
            {
                return $this->value;
            }

            public function getImage()
            {
                return null;
            }

            public function __toString(): string
            {
                return $this->value;
            }

            public function jsonSerialize(): mixed
            {
                return ['value' => $this->value];
            }

            public function equalsTo(mixed $value): bool
            {
                if ($value instanceof CaptchaAnswerInterface) {
                    return $this->value === $value->getValue();
                }
                return $this->value === (string) $value;
            }
        };

        $mockResolver = $this->createMock(CaptchaResolverInterface::class);
        $mockResolver->method('resolve')->willReturn($mockAnswer);

        $service = new CaptchaService($mockResolver);

        $html = '<div id="divCaptcha"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==" /></div>';

        $result = $service->resolveCaptchaFromHtml($html);

        $this->assertSame($expectedValue, $result);
    }
}
