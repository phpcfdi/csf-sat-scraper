<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Services;

use GuzzleHttp\ClientInterface;
use PhpCfdi\CsfSatScraper\Exceptions\SATException;
use PhpCfdi\CsfSatScraper\FormUtils;
use PhpCfdi\CsfSatScraper\URL;

readonly class SSOHandler
{
    public function __construct(public ClientInterface $client)
    {
    }

    public function handleSSOForms(string $html): string
    {
        $form = FormUtils::extractForm($html);

        $samlResponse = $this->client->request('POST', $form->getAction(), [
            'form_params' => $form->getFields(),
        ]);

        $html = (string)$samlResponse->getBody();

        $form = FormUtils::extractForm($html);

        $response = $this->client->request('POST', $form->getAction(), [
            'form_params' => $form->getFields(),
        ]);

        return (string)$response->getBody();
    }

    public function handleSSOWorkflow(): string
    {
        $response = $this->client->request('GET', URL::$thrower, [
            'headers' => [
                'Host' => 'wwwmat.sat.gob.mx',
            ],
        ]);

        $html = (string)$response->getBody();

        $htmlWithIframe = $this->handleSSOForms($html);

        if (! preg_match('/<iframe[^>]+id="iframetoload"[^>]+src="([^"]+)"/i', $htmlWithIframe, $m)) {
            throw new SATException('iframetoload not found in SSO workflow');
        }

        $iframeUrl = html_entity_decode($m[1]);

        $iframeResponse = $this->client->request('GET', $iframeUrl);

        $html = (string)$iframeResponse->getBody();

        return $this->handleSSOForms($html);
    }
}
