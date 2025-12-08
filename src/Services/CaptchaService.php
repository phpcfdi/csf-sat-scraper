<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Services;

use PhpCfdi\CsfSatScraper\Exceptions\CaptchaSourceNotFoundException;
use PhpCfdi\ImageCaptchaResolver\CaptchaImage;
use PhpCfdi\ImageCaptchaResolver\CaptchaResolverInterface;
use Symfony\Component\DomCrawler\Crawler;

readonly class CaptchaService
{
    public function __construct(public CaptchaResolverInterface $captchaSolver)
    {
    }

    public function resolveCaptchaFromHtml(string $html): string
    {
        $crawler = new Crawler($html);
        $captchaImageSrc = $crawler->filter('#divCaptcha img')->attr('src');
        if (null === $captchaImageSrc) {
            throw new CaptchaSourceNotFoundException('Captcha image not found in HTML');
        }
        $image = CaptchaImage::newFromInlineHtml($captchaImageSrc);
        $solution = $this->captchaSolver->resolve($image);

        return $solution->getValue();
    }
}
