<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Services;

use PhpCfdi\CsfSatScraper\Exceptions\InvalidCaptchaException;
use PhpCfdi\CsfSatScraper\Exceptions\InvalidCredentialsException;
use PhpCfdi\CsfSatScraper\Exceptions\LoginException;
use PhpCfdi\CsfSatScraper\Exceptions\LoginPageNotLoadedException;
use PhpCfdi\CsfSatScraper\Exceptions\NetworkException;
use PhpCfdi\CsfSatScraper\URL;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

readonly class AuthenticationService
{
    public function __construct(
        private ClientInterface $client,
        private string $rfc,
        private string $password
    ) {
    }

    public function initializeApp(): void
    {
        try {
            $this->client->request('GET', '/nidp/app', [
                'query' => [
                    'sid' => 1,
                ],
                'headers' => [
                    RequestOptions::ALLOW_REDIRECTS => true,
                ]
            ]);
        } catch (GuzzleException $e) {
            throw new NetworkException('Failed to initialize login session', 0, $e);
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
                    'Origin' => 'https://login.siat.sat.gob.mx',
                    'Referer' => 'https://login.siat.sat.gob.mx/nidp/app/login',
                ],
                'body' => '',
            ]);

            $html = (string)$response->getBody();
            if (! str_contains($html, 'divCaptcha')) {
                $exception = new LoginPageNotLoadedException('Unable to retrieve login form with captcha');
                $exception->setHtml($html);
                throw $exception;
            }

            return $html;
        } catch (GuzzleException $e) {
            throw new NetworkException('Failed to get login form', 0, $e);
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
                    'Origin' => 'https://login.siat.sat.gob.mx',
                    'Referer' => 'https://login.siat.sat.gob.mx/nidp/app/login',
                ],
                'form_params' => [
                    'Ecom_User_ID' => $this->rfc,
                    'Ecom_Password' => $this->password,
                    'userCaptcha' => $captchaValue,
                    'submit' => 'Enviar',
                ],
            ]);

            return (string)$response->getBody();
        } catch (GuzzleException $e) {
            throw new NetworkException('Failed to send login form', 0, $e);
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
                ]
            ]);

            $html = (string)$response->getBody();

            if (str_contains($html, 'captcha')) {
                throw new InvalidCaptchaException('Invalid captcha');
            }

            if (! str_contains($html, $this->rfc)) {
                throw new InvalidCredentialsException('Invalid credentials');
            }
        } catch (GuzzleException $e) {
            throw new NetworkException('Failed to check login', 0, $e);
        }
    }

    public function logout(): void
    {
        try {
            $this->client->request('GET', URL::$logoutSatellite);
            $this->client->request('GET', URL::$closeSession);
            $this->client->request('GET', URL::$logout);
        } catch (GuzzleException $e) {
            throw new NetworkException('Failed to logout', 0, $e);
        }
    }
}
