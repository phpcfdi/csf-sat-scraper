<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Services;

use GuzzleHttp\ClientInterface;
use PhpCfdi\CsfSatScraper\Exceptions\InvalidCaptchaException;
use PhpCfdi\CsfSatScraper\Exceptions\InvalidCredentialsException;
use PhpCfdi\CsfSatScraper\Exceptions\LoginException;
use PhpCfdi\CsfSatScraper\Exceptions\LoginPageNotLoadedException;
use PhpCfdi\CsfSatScraper\Exceptions\NetworkException;
use PhpCfdi\CsfSatScraper\Url;
use Psr\Http\Client\ClientExceptionInterface;
use SensitiveParameter;

readonly class AuthenticationService
{
    public function __construct(
        public ClientInterface $client,
        public string $rfc,
        #[SensitiveParameter]
        public string $password,
    ) {
    }

    public function initializeApp(): void
    {
        try {
            $this->client->request('GET', '/nidp/app', [
                'query' => [
                    'sid' => 1,
                ],
            ]);
        } catch (ClientExceptionInterface $e) {
            throw new NetworkException('Failed to initialize login session', $e);
        }
    }

    public function getLoginForm(): string
    {
        try {
            $response = $this->client->request('POST', '/nidp/app/login', [
                'allow_redirects' => true,
                'query' => [
                    'id' => 'ptsc-ciec',
                    'sid' => 1,
                    'option' => 'credential',
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Origin' => Url::$base,
                    'Referer' => Url::$login,
                ],
                'body' => '',
            ]);

            $html = (string)$response->getBody();
            if (! str_contains($html, 'divCaptcha')) {
                throw new LoginPageNotLoadedException('Unable to retrieve login form with captcha', $html);
            }

            return $html;
        } catch (ClientExceptionInterface $e) {
            throw new NetworkException('Failed to get login form', $e);
        }
    }

    public function sendLoginForm(string $captchaValue): string
    {
        try {
            $response = $this->client->request('POST', '/nidp/app/login', [
                'allow_redirects' => true,
                'query' => [
                    'id' => 'ptsc-ciec',
                    'sid' => 1,
                    'option' => 'credential',
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Origin' => Url::$base,
                    'Referer' => Url::$login,
                ],
                'form_params' => [
                    'Ecom_User_ID' => $this->rfc,
                    'Ecom_Password' => $this->password,
                    'userCaptcha' => $captchaValue,
                    'submit' => 'Enviar',
                ],
            ]);

            return (string)$response->getBody();
        } catch (ClientExceptionInterface $e) {
            throw new NetworkException('Failed to send login form', $e);
        }
    }

    public function checkLogin(): void
    {
        try {
            $response = $this->client->request('POST', '/nidp/app', [
                'allow_redirects' => true,
                'query' => [
                    'sid' => 1,
                ],
                'headers' => [
                    'Host' => 'login.siat.sat.gob.mx',
                    'Referer' => 'https://login.siat.sat.gob.mx/nidp/app/login?id=ptsc-ciec&sid=1&option=credential&sid=1',
                ],
            ]);
        } catch (ClientExceptionInterface $e) {
            throw new NetworkException('Failed to check login', $e);
        }

        $html = (string)$response->getBody();
        $this->checkLoginResponse($html);

        if (! str_contains($html, $this->rfc)) {
            // The login page always mentions "captcha", so its absence or presence tells nothing:
            // an unconfirmed login is reported as such and must not be retried as a captcha error.
            throw new LoginException('Login was not confirmed');
        }
    }

    /**
     * Throws when the page the SAT returns after submitting the form carries a rejection message.
     *
     * @throws InvalidCaptchaException when the SAT says the captcha answer was wrong
     * @throws InvalidCredentialsException when the SAT rejects the RFC or the password
     */
    public function checkLoginResponse(string $html): void
    {
        $message = self::extractLoginError($html);
        if (null === $message) {
            return;
        }
        if (self::isCaptchaError($message)) {
            throw new InvalidCaptchaException($message);
        }
        throw new InvalidCredentialsException($message);
    }

    /**
     * The login page renders the rejection reason in `var error = '...';` (empty when there is none).
     */
    public static function extractLoginError(string $html): ?string
    {
        if (! preg_match('/var\s+error\s*=\s*\'((?:[^\'\\\\]|\\\\.)*)\'/', $html, $matches)) {
            return null;
        }
        $message = trim(html_entity_decode(stripcslashes($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return '' === $message ? null : $message;
    }

    public static function isCaptchaError(string $message): bool
    {
        return 1 === preg_match('/captcha|imagen/iu', $message);
    }

    public function logout(): void
    {
        try {
            $this->client->request('GET', Url::$logoutSatellite);
            $this->client->request('GET', Url::$closeSession);
            $this->client->request('GET', Url::$logout);
        } catch (ClientExceptionInterface $e) {
            throw new NetworkException('Failed to logout', $e);
        }
    }
}
