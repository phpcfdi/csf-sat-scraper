<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper;

use GuzzleHttp\ClientInterface;
use LogicException;
use PhpCfdi\CsfSatScraper\Exceptions\InvalidCaptchaException;
use PhpCfdi\CsfSatScraper\Services\AuthenticationService;
use PhpCfdi\CsfSatScraper\Services\CaptchaService;
use PhpCfdi\CsfSatScraper\Services\DocumentService;
use PhpCfdi\CsfSatScraper\Services\SsoHandler;
use PhpCfdi\ImageCaptchaResolver\CaptchaResolverInterface;
use SensitiveParameter;
use Stringable;
use Throwable;

readonly class Scraper implements ScraperInterface
{
    public function __construct(
        public ClientInterface $client,
        public AuthenticationService $authService,
        public CaptchaService $captchaService,
        public SsoHandler $ssoHandler,
        public DocumentService $documentService,
        public int $maxCaptchaAttempts = 3,
    ) {
        if ($this->client !== $this->authService->client) {
            throw new LogicException('Authentication service HTTP client is not the same as Scraper client.');
        }
        if ($this->client !== $this->ssoHandler->client) {
            throw new LogicException('SSO handler HTTP client is not the same as Scraper client.');
        }
        if ($this->client !== $this->documentService->client) {
            throw new LogicException('Document service HTTP client is not the same as Scraper client.');
        }
        if ($this->maxCaptchaAttempts < 1) {
            throw new LogicException('Max captcha attempts must be at least 1.');
        }
    }

    public static function create(
        ClientInterface $client,
        CaptchaResolverInterface $captchaResolver,
        string $rfc,
        #[SensitiveParameter]
        string $password,
        int $maxCaptchaAttempts = 3,
    ): self {
        return new self(
            $client,
            new AuthenticationService($client, $rfc, $password),
            new CaptchaService($captchaResolver),
            new SsoHandler($client),
            new DocumentService($client),
            $maxCaptchaAttempts,
        );
    }

    public function download(): Stringable
    {
        try {
            $this->login();
            $html = $this->ssoHandler->handleSsoWorkflow();

            return $this->documentService->downloadDocument($html);
        } finally {
            $this->logoutQuietly();
        }
    }

    /**
     * Only a wrong captcha is retried, with a new captcha each time: retrying any other rejection
     * would spend the taxpayer's password attempts and the SAT may lock the password.
     *
     * @throws InvalidCaptchaException after the last captcha attempt fails
     * @throws Exceptions\InvalidCredentialsException when the SAT rejects the RFC or the password
     */
    public function login(): void
    {
        $this->authService->initializeApp();
        for ($attempt = 1; ; $attempt++) {
            $loginHtmlForm = $this->authService->getLoginForm();
            $captchaValue = $this->captchaService->resolveCaptchaFromHtml($loginHtmlForm);
            try {
                $this->authService->checkLoginResponse($this->authService->sendLoginForm($captchaValue));
                $this->authService->checkLogin();
                return;
            } catch (InvalidCaptchaException $exception) {
                if ($attempt >= $this->maxCaptchaAttempts) {
                    throw $exception;
                }
            }
        }
    }

    private function logoutQuietly(): void
    {
        try {
            $this->authService->logout();
        } catch (Throwable) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
            // the SAT session expires anyway; whatever the logout throws (network error, a client
            // middleware such as a deadline) must not replace the download result or its real failure
        }
    }
}
