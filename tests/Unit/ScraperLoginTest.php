<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PhpCfdi\CsfSatScraper\Exceptions\InvalidCaptchaException;
use PhpCfdi\CsfSatScraper\Exceptions\InvalidCredentialsException;
use PhpCfdi\CsfSatScraper\Scraper;
use PhpCfdi\ImageCaptchaResolver\CaptchaAnswer;
use PhpCfdi\ImageCaptchaResolver\Resolvers\MockResolver;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class ScraperLoginTest extends TestCase
{
    private const RFC = 'XAXX010101000';

    // 1x1 transparent PNG
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private function loginForm(string $error = ''): Response
    {
        return new Response(200, [], '<html><div id="divCaptcha"><img src="data:image/png;base64,'
            . self::PNG . '"></div><script>var error = \'' . $error . '\';</script></html>');
    }

    /**
     * @param list<Response|Throwable> $responses
     * @return array{Scraper, MockHandler}
     */
    private function scraper(array $responses, MockResolver $resolver): array
    {
        $handler = new MockHandler($responses);
        $client = new Client(['handler' => HandlerStack::create($handler), 'base_uri' => 'https://login.siat.sat.gob.mx']);

        return [Scraper::create($client, $resolver, self::RFC, 'secreto', 2), $handler];
    }

    public function testWrongCaptchaIsRetriedWithANewCaptcha(): void
    {
        $resolver = new MockResolver(new CaptchaAnswer('mala'), new CaptchaAnswer('buena'));
        [$scraper] = $this->scraper([
            new Response(200),                                  // initializeApp
            $this->loginForm(),                                 // getLoginForm
            $this->loginForm('El captcha es incorrecto'),       // sendLoginForm: captcha rejected
            $this->loginForm(),                                 // getLoginForm again (new captcha)
            new Response(200, [], ''),                          // sendLoginForm: accepted
            new Response(200, [], 'Bienvenido ' . self::RFC),   // checkLogin
        ], $resolver);

        $scraper->login();

        $this->assertTrue($resolver->isEmpty(), 'Both captcha answers were used');
    }

    public function testWrongCaptchaStopsAfterMaxAttempts(): void
    {
        $resolver = new MockResolver(new CaptchaAnswer('mala'), new CaptchaAnswer('mala'));
        [$scraper] = $this->scraper([
            new Response(200),
            $this->loginForm(),
            $this->loginForm('El captcha es incorrecto'),
            $this->loginForm(),
            $this->loginForm('El captcha es incorrecto'),
        ], $resolver);

        $this->expectException(InvalidCaptchaException::class);
        $scraper->login();
    }

    public function testRejectedPasswordIsNeverRetried(): void
    {
        $resolver = new MockResolver(new CaptchaAnswer('buena'), new CaptchaAnswer('nunca'));
        [$scraper] = $this->scraper([
            new Response(200),
            $this->loginForm(),
            $this->loginForm('El RFC o la contraseña son incorrectos'),
        ], $resolver);

        try {
            $scraper->login();
            $this->fail('A rejected password must throw');
        } catch (InvalidCredentialsException $exception) {
            $this->assertSame('El RFC o la contraseña son incorrectos', $exception->getMessage());
        }
        $this->assertCount(1, $resolver, 'The second captcha answer was not requested');
    }

    public function testDownloadLogsOutEvenWhenLoginFails(): void
    {
        $resolver = new MockResolver(new CaptchaAnswer('buena'));
        [$scraper, $handler] = $this->scraper([
            new Response(200),
            $this->loginForm(),
            $this->loginForm('El RFC o la contraseña son incorrectos'),
            new Response(200), // logout: satellite
            new Response(200), // logout: close session
            new Response(200), // logout: nidp
        ], $resolver);

        try {
            $scraper->download();
            $this->fail('A rejected password must throw');
        } catch (InvalidCredentialsException) {
            $this->assertSame(0, $handler->count(), 'The three logout requests were sent');
        }
    }

    public function testAFailingLogoutDoesNotReplaceTheRealResult(): void
    {
        $resolver = new MockResolver(new CaptchaAnswer('buena'));
        [$scraper] = $this->scraper([
            new Response(200),
            $this->loginForm(),
            $this->loginForm('El RFC o la contraseña son incorrectos'),
            new RuntimeException('a client middleware refused the logout request'),
        ], $resolver);

        $this->expectException(InvalidCredentialsException::class);
        $scraper->download();
    }
}
