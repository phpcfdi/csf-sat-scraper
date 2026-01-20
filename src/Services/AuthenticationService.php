<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Services;

use GuzzleHttp\ClientInterface;
use PhpCfdi\CsfSatScraper\Exceptions\InvalidCaptchaException;
use PhpCfdi\CsfSatScraper\Exceptions\InvalidCredentialsException;
use PhpCfdi\CsfSatScraper\Exceptions\LoginPageNotLoadedException;
use PhpCfdi\CsfSatScraper\Exceptions\NetworkException;
use PhpCfdi\CsfSatScraper\URL;
use Psr\Http\Client\ClientExceptionInterface;

readonly class AuthenticationService
{
    public function __construct(
        public ClientInterface $client,
        public string $rfc,
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
                    'Origin' => URL::$base,
                    'Referer' => URL::$login,
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
                    'Origin' => URL::$base,
                    'Referer' => URL::$login,
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

            $html = (string)$response->getBody();

            if (str_contains($html, 'captcha')) {
                throw new InvalidCaptchaException('Invalid captcha');
            }

            if (! str_contains($html, $this->rfc)) {
                throw new InvalidCredentialsException('Invalid credentials');
            }
        } catch (ClientExceptionInterface $e) {
            throw new NetworkException('Failed to check login', $e);
        }
    }

    public function logout(): void
    {
        try {
            $this->client->request('GET', URL::$logoutSatellite);
            $this->client->request('GET', URL::$closeSession);
            $this->client->request('GET', URL::$logout);
        } catch (ClientExceptionInterface $e) {
            throw new NetworkException('Failed to logout', $e);
        }
    }
}
