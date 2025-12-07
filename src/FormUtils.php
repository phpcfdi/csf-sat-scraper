<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper;

use PhpCfdi\CsfSatScraper\Exceptions\SATException;
use Symfony\Component\DomCrawler\Crawler;

class FormUtils
{
    public static function extractForm(string $html): FormExtractionResult
    {
        if (! preg_match('/<form[^>]+action="([^"]+)"[^>]*>/i', $html, $m)) {
            throw new SATException('No encontré el form SAML en la respuesta');
        }

        $action = html_entity_decode($m[1]);

        $fields = [];
        if (preg_match_all('/<input[^>]+type="hidden"[^>]*>/i', $html, $inputs)) {
            foreach ($inputs[0] as $inputHtml) {
                if (
                    preg_match('/name="([^"]+)"/i', $inputHtml, $n)
                    && preg_match('/value="([^"]*)"/i', $inputHtml, $v)
                ) {
                    $fields[$n[1]] = html_entity_decode($v[1]);
                }
            }
        }

        return new FormExtractionResult($action, $fields);
    }

    public static function extractFinalForm(string $html): FormExtractionResult
    {
        $crawler = new Crawler($html, URL::$rfcampc);
        $form = $crawler->filter('form#formReimpAcuse')->form();

        $actionUrl = $form->getUri();
        /** @phpstan-var array<string, string> $params */
        $params = $form->getPhpValues();

        $buttonId = 'formReimpAcuse:j_idt50';

        $params['javax.faces.partial.ajax'] = 'true';
        $params['javax.faces.source'] = $buttonId;
        $params['javax.faces.partial.execute'] = '@all';
        $params[$buttonId] = $buttonId;

        return new FormExtractionResult($actionUrl, $params);
    }
}
