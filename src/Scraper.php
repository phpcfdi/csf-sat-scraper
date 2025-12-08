<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper;

use GuzzleHttp\ClientInterface;
use LogicException;
use PhpCfdi\CsfSatScraper\Services\AuthenticationService;
use PhpCfdi\CsfSatScraper\Services\CaptchaService;
use PhpCfdi\CsfSatScraper\Services\DocumentService;
use PhpCfdi\CsfSatScraper\Services\SSOHandler;
use PhpCfdi\ImageCaptchaResolver\CaptchaResolverInterface;

readonly class Scraper implements ScraperInterface
{
    public function __construct(
        public ClientInterface $client,
        public AuthenticationService $authService,
        public CaptchaService $captchaService,
        public SSOHandler $ssoHandler,
        public DocumentService $documentService,
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
    }

    public static function create(
        ClientInterface $client,
        CaptchaResolverInterface $captchaSolver,
        string $rfc,
        string $password,
    ): self {
        return new self(
            $client,
            new AuthenticationService($client, $rfc, $password),
            new CaptchaService($captchaSolver),
            new SSOHandler($client),
            new DocumentService($client),
        );
    }

    public function download(): string
    {
        $this->authService->initializeApp();
        $loginHtmlForm = $this->authService->getLoginForm();
        $captchaValue = $this->captchaService->resolveCaptchaFromHtml($loginHtmlForm);
        $this->authService->sendLoginForm($captchaValue);
        $this->authService->checkLogin();

        $html = $this->ssoHandler->handleSSOWorkflow();
        $document = $this->documentService->downloadDocument($html);

        $this->authService->logout();

        return $document;
    }
}
