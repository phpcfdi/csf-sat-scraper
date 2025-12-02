<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Services;

use PhpCfdi\ImageCaptchaResolver\CaptchaImage;
use PhpCfdi\ImageCaptchaResolver\CaptchaResolverInterface;
use Symfony\Component\DomCrawler\Crawler;

readonly class CaptchaService
{
    public function __construct(
        private CaptchaResolverInterface $captchaSolver
    ) {
    }

    public function resolveCaptchaFromHtml(string $html): string
    {
        $crawler = new Crawler($html);
        $captchaImageSrc = $crawler->filter('#divCaptcha img')->attr('src');
        $image = CaptchaImage::newFromInlineHtml($captchaImageSrc);
        $solution = $this->captchaSolver->resolve($image);
        return $solution->getValue();
    }
}
