<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Tests\Unit\Services;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use PhpCfdi\CsfSatScraper\Exceptions\NetworkException;
use PhpCfdi\CsfSatScraper\Services\DocumentService;
use PhpCfdi\CsfSatScraper\URL;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class DocumentServiceTest extends TestCase
{
    private ClientInterface&MockObject $mockClient;

    private DocumentService $service;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(ClientInterface::class);
        $this->service = new DocumentService($this->mockClient);
    }

    public function testSendFinalFormWithSATData(): void
    {
        $url = 'https://rfcampc.siat.sat.gob.mx/PTSC/IdcSiat/IdcGeneraConstancia.jsf';

        $formParams = [
            'formReimpAcuse' => 'formReimpAcuse',
            'formReimpAcuse:j_idt50' => 'formReimpAcuse:j_idt50',
            'javax.faces.partial.ajax' => 'true',
            'javax.faces.source' => 'formReimpAcuse:j_idt50',
            'javax.faces.partial.execute' => '@all',
            'javax.faces.ViewState' => 'j_id1:javax.faces.ViewState:0',
        ];

        $expectedResponse = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<partial-response id="j_id1">' .
            '<changes>' .
            '<update id="javax.faces.ViewState"><![CDATA[j_id1:javax.faces.ViewState:1]]></update>' .
            '<update id="formReimpAcuse"><![CDATA[<form>Constancia generada</form>]]></update>' .
            '</changes>' .
            '</partial-response>';

        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('__toString')->willReturn($expectedResponse);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $url,
                $this->callback(
                    static fn (array $options) => isset($options['form_params']) && $options['form_params'] === $formParams,
                ),
            )
            ->willReturn($mockResponse);

        $result = $this->service->sendFinalForm($url, $formParams);

        $this->assertSame($expectedResponse, $result);
        $this->assertStringContainsString('partial-response', $result);
        $this->assertStringContainsString('Constancia generada', $result);
    }

    public function testGetFileContentReturnsPDFDocument(): void
    {
        $expectedPdfContent = "%PDF-1.4\n" .
            "%âãÏÓ\n" .
            "1 0 obj\n" .
            "<<\n" .
            "/Type /Catalog\n" .
            "/Pages 2 0 R\n" .
            "/Metadata 3 0 R\n" .
            ">>\n" .
            "endobj\n" .
            "2 0 obj\n" .
            "<<\n" .
            "/Type /Pages\n" .
            "/Kids [4 0 R]\n" .
            "/Count 1\n" .
            ">>\n" .
            "endobj\n" .
            "% Constancia de Situación Fiscal - SAT\n" .
            "% RFC: XXXX000000XXX\n" .
            '% Fecha: ' . date('Y-m-d') . "\n";

        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('__toString')->willReturn($expectedPdfContent);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', URL::$file)
            ->willReturn($mockResponse);

        $result = $this->service->getFileContent();

        $this->assertSame($expectedPdfContent, $result);
        $this->assertStringStartsWith('%PDF-1.4', $result);
        $this->assertStringContainsString('Constancia de Situación Fiscal', $result);
        $this->assertStringContainsString('/Type /Catalog', $result);
    }

    public function testDownloadDocumentIntegration(): void
    {
        $lastHtml = <<<HTML
            <!DOCTYPE html>
            <html xmlns="http://www.w3.org/1999/xhtml">
            <head><title>Constancia de Situación Fiscal</title></head>
            <body>
                <form id="formReimpAcuse" name="formReimpAcuse" method="post"
                      action="https://rfcampc.siat.sat.gob.mx/PTSC/IdcSiat/IdcGeneraConstancia.jsf">
                    <input type="hidden" name="javax.faces.ViewState" value="j_id1:javax.faces.ViewState:0" />
                    <input type="hidden" name="formReimpAcuse" value="formReimpAcuse" />
                    <button id="formReimpAcuse:j_idt50" name="formReimpAcuse:j_idt50"
                            type="submit">Generar Constancia</button>
                </form>
            </body>
            </html>
            HTML;
        $ajaxResponse = '<?xml version="1.0"?><partial-response><changes><update>OK</update></changes></partial-response>';

        $pdfContent = "%PDF-1.4\n% Constancia de Situación Fiscal";

        $mockStreamAjax = $this->createMock(StreamInterface::class);
        $mockStreamAjax->method('__toString')->willReturn($ajaxResponse);
        $mockResponseAjax = $this->createMock(ResponseInterface::class);
        $mockResponseAjax->method('getBody')->willReturn($mockStreamAjax);

        $mockStreamPdf = $this->createMock(StreamInterface::class);
        $mockStreamPdf->method('__toString')->willReturn($pdfContent);
        $mockResponsePdf = $this->createMock(ResponseInterface::class);
        $mockResponsePdf->method('getBody')->willReturn($mockStreamPdf);

        $this->mockClient
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($mockResponseAjax, $mockResponsePdf);

        $result = $this->service->downloadDocument($lastHtml);

        $this->assertSame($pdfContent, $result);
        $this->assertStringStartsWith('%PDF-1.4', $result);
    }

    public function testSendFinalFormHandlesEmptyResponse(): void
    {
        $url = 'https://rfcampc.siat.sat.gob.mx/PTSC/IdcSiat/IdcGeneraConstancia.jsf';
        $values = ['formReimpAcuse' => 'formReimpAcuse'];

        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('__toString')->willReturn('');

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient
            ->method('request')
            ->willReturn($mockResponse);

        $result = $this->service->sendFinalForm($url, $values);

        $this->assertSame('', $result);
    }

    public function testSendFinalFormThrowsNetworkException(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $exception = new ConnectException('Connection failed', $mockRequest);

        $this->mockClient
            ->method('request')
            ->willThrowException($exception);

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Failed to send final form');

        $this->service->sendFinalForm('https://test.com', ['key' => 'value']);
    }

    public function testGetFileContentThrowsNetworkException(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $exception = new ConnectException('Connection timeout', $mockRequest);

        $this->mockClient
            ->method('request')
            ->willThrowException($exception);

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Failed to get file content');

        $this->service->getFileContent();
    }
}
