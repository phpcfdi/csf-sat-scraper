<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Services;

use GuzzleHttp\ClientInterface;
use PhpCfdi\CsfSatScraper\Exceptions\NetworkException;
use PhpCfdi\CsfSatScraper\FormUtils;
use PhpCfdi\CsfSatScraper\Url;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\StreamInterface;

readonly class DocumentService
{
    public function __construct(public ClientInterface $client)
    {
    }

    /** @phpstan-param mixed[] $values */
    public function sendFinalForm(string $url, array $values): string
    {
        try {
            $response = $this->client->request('POST', $url, [
                'form_params' => $values,
            ]);
            return (string)$response->getBody();
        } catch (ClientExceptionInterface $e) {
            throw new NetworkException('Failed to send final form', $e);
        }
    }

    public function getFileContent(): StreamInterface
    {
        try {
            $response = $this->client->request('GET', Url::$file);
            return $response->getBody();
        } catch (ClientExceptionInterface $e) {
            throw new NetworkException('Failed to get file content', $e);
        }
    }

    public function downloadDocument(string $lastHtml): StreamInterface
    {
        $finalForm = FormUtils::extractFinalForm($lastHtml);
        $this->sendFinalForm($finalForm->action, $finalForm->fields);
        return $this->getFileContent();
    }
}
