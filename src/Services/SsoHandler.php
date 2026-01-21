<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Services;

use GuzzleHttp\ClientInterface;
use PhpCfdi\CsfSatScraper\Exceptions\SatException;
use PhpCfdi\CsfSatScraper\FormUtils;
use PhpCfdi\CsfSatScraper\Url;

readonly class SsoHandler
{
    public function __construct(public ClientInterface $client)
    {
    }

    public function handleSsoForms(string $html): string
    {
        $form = FormUtils::extractForm($html);

        $samlResponse = $this->client->request('POST', $form->action, [
            'form_params' => $form->fields,
        ]);

        $html = (string)$samlResponse->getBody();

        $form = FormUtils::extractForm($html);

        $response = $this->client->request('POST', $form->action, [
            'form_params' => $form->fields,
        ]);

        return (string)$response->getBody();
    }

    public function handleSsoWorkflow(): string
    {
        $response = $this->client->request('GET', Url::$thrower, [
            'headers' => [
                'Host' => 'wwwmat.sat.gob.mx',
            ],
        ]);

        $html = (string)$response->getBody();

        $htmlWithIframe = $this->handleSsoForms($html);

        if (! preg_match('/<iframe[^>]+id="iframetoload"[^>]+src="([^"]+)"/i', $htmlWithIframe, $m)) {
            throw new SatException('iframetoload not found in SSO workflow');
        }

        $iframeUrl = html_entity_decode($m[1]);

        $iframeResponse = $this->client->request('GET', $iframeUrl);

        $html = (string)$iframeResponse->getBody();

        return $this->handleSsoForms($html);
    }
}
