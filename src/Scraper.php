<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper;

use PhpCfdi\CsfSatScraper\Services\AuthenticationService;
use PhpCfdi\CsfSatScraper\Services\CaptchaService;
use PhpCfdi\CsfSatScraper\Services\DocumentService;
use PhpCfdi\CsfSatScraper\Services\SSOHandler;
use GuzzleHttp\ClientInterface;
use PhpCfdi\ImageCaptchaResolver\CaptchaResolverInterface;

readonly class Scraper
{
    private AuthenticationService $authService;
    private CaptchaService $captchaService;
    private SSOHandler $ssoHandler;
    private DocumentService $documentService;

    public function __construct(
        private ClientInterface $client,
        CaptchaResolverInterface $captchaSolver,
        string $rfc,
        string $password,
    ) {
        $this->authService = new AuthenticationService($client, $rfc, $password);
        $this->captchaService = new CaptchaService($captchaSolver);
        $this->ssoHandler = new SSOHandler($client);
        $this->documentService = new DocumentService($client);
    }

    public function getClient(): ClientInterface
    {
        return $this->client;
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
