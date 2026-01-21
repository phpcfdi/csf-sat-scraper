<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Tests\Unit\Services;

use GuzzleHttp\ClientInterface;
use PhpCfdi\CsfSatScraper\Exceptions\SatException;
use PhpCfdi\CsfSatScraper\Services\SsoHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class SsoHandlerTest extends TestCase
{
    private ClientInterface&MockObject $mockClient;

    private SsoHandler $service;

    protected function setUp(): void
    {

        $this->mockClient = $this->createMock(ClientInterface::class);
        $this->service = new SsoHandler($this->mockClient);
    }

    public function testHandleSsoFormsWithSamlResponse(): void
    {
        $inputHtml = <<<'HTML'
            <!DOCTYPE html>
            <html>
            <body>
                <form method="post" action="https://wwwmat.sat.gob.mx/Shibboleth.sso/SAML2/POST">
                    <input type="hidden" name="RelayState" value="ss:mem:6c7a8b9d0e1f2a3b4c5d6e7f8a9b0c1d" />
                    <input type="hidden" name="SAMLResponse" value="PHNhbWxwOlJlc3BvbnNlIHhtbG5zOnNhbWxwPSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6cHJvdG9jb2wiIHhtbG5zOnNhbWw9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphc3NlcnRpb24iIElEPSJfODZhZTVhZGYtZGMzOC00YTc4LTk2YWItNGIzYzJkMWU1ZjY3IiBWZXJzaW9uPSIyLjAiIElzc3VlSW5zdGFudD0iMjAyNS0xMS0zMFQxMjozNDo1NloiIERlc3RpbmF0aW9uPSJodHRwczovL3d3d21hdC5zYXQuZ29iLm14L1NoaWJib2xldGguc3NvL1NBTUwyL1BPU1QiPg==" />
                </form>
            </body>
            </html>
            HTML;

        $intermediateHtml = <<<'HTML'
            <!DOCTYPE html>
            <html>
            <body>
                <form method="post" action="https://rfcampc.siat.sat.gob.mx/Shibboleth.sso/SAML2/POST">
                    <input type="hidden" name="RelayState" value="https://rfcampc.siat.sat.gob.mx/PTSC/inicio" />
                    <input type="hidden" name="SAMLResponse" value="PHNhbWw6QXNzZXJ0aW9uIHhtbG5zOnNhbWw9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphc3NlcnRpb24iIElEPSJfYWJjZGVmMTIzNDU2Nzg5MCIgVmVyc2lvbj0iMi4wIiBJc3N1ZUluc3RhbnQ9IjIwMjUtMTEtMzBUMTI6MzQ6NTdaIj4=" />
                </form>
            </body>
            </html>
            HTML;

        $finalHtml = <<<'HTML'
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <title>Portal SAT - Constancia de Situación Fiscal</title>
            </head>
            <body>
                <div id="main-content">
                    <h1>Bienvenido al Portal del SAT</h1>
                    <p>Sesión SSO establecida correctamente</p>
                </div>
            </body>
            </html>
            HTML;

        $mockStream1 = $this->createMock(StreamInterface::class);
        $mockStream1->method('__toString')->willReturn($intermediateHtml);
        $mockResponse1 = $this->createMock(ResponseInterface::class);
        $mockResponse1->method('getBody')->willReturn($mockStream1);

        $mockStream2 = $this->createMock(StreamInterface::class);
        $mockStream2->method('__toString')->willReturn($finalHtml);
        $mockResponse2 = $this->createMock(ResponseInterface::class);
        $mockResponse2->method('getBody')->willReturn($mockStream2);

        $callCount = 0;
        $this->mockClient
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function ($method, $url) use (&$callCount, $mockResponse1, $mockResponse2) {
                $callCount++;
                if (1 === $callCount) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('https://wwwmat.sat.gob.mx/Shibboleth.sso/SAML2/POST', $url);
                    return $mockResponse1;
                } else {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('https://rfcampc.siat.sat.gob.mx/Shibboleth.sso/SAML2/POST', $url);
                    return $mockResponse2;
                }
            });

        $result = $this->service->handleSsoForms($inputHtml);

        $this->assertSame($finalHtml, $result);
        $this->assertStringContainsString('Portal del SAT', $result);
        $this->assertStringContainsString('Sesión SSO establecida correctamente', $result);
    }

    public function testHandleSsoWorkflowCompleteFlow(): void
    {
        $throwerResponse = <<<'HTML'
            <!DOCTYPE html>
            <html>
            <body>
                <form id="samlForm" method="post" action="https://wwwmat.sat.gob.mx/Shibboleth.sso/SAML2/POST">
                    <input type="hidden" name="RelayState" value="ss:mem:a1b2c3d4e5f6" />
                    <input type="hidden" name="SAMLResponse" value="PHNhbWxwOlJlc3BvbnNlPg==" />
                    <noscript>
                        <input type="submit" value="Continue" />
                    </noscript>
                </form>
                <script>document.getElementById('samlForm').submit();</script>
            </body>
            </html>
            HTML;

        $htmlWithIframe = <<<'HTML'
            <!DOCTYPE html>
            <html lang="es">
            <head><title>SAT - Autenticación</title></head>
            <body>
                <div id="container">
                    <iframe id="iframetoload"
                            src="https://rfcampc.siat.sat.gob.mx/PTSC/faces/pages/inicio.jsf?sessionid=xyz123"
                            style="width:100%;height:600px;border:none;">
                    </iframe>
                </div>
            </body>
            </html>
            HTML;

        $iframeHtml = <<<'HTML'
            <!DOCTYPE html>
            <html>
            <body>
                <form method="post" action="https://rfcampc.siat.sat.gob.mx/Shibboleth.sso/SAML2/POST">
                    <input type="hidden" name="RelayState" value="https://rfcampc.siat.sat.gob.mx/PTSC/inicio" />
                    <input type="hidden" name="SAMLResponse" value="PFJlc3BvbnNlPjwvUmVzcG9uc2U+" />
                </form>
            </body>
            </html>
            HTML;

        $finalHtml = <<<'HTML'
            <!DOCTYPE html>
            <html lang="es">
            <head><title>Portal PTSC - Constancia de Situación Fiscal</title></head>
            <body>
                <div id="main">
                    <h1>Constancia de Situación Fiscal</h1>
                    <form id="formReimpAcuse" action="/PTSC/IdcSiat/IdcGeneraConstancia.jsf">
                        <button type="submit">Generar Constancia</button>
                    </form>
                </div>
            </body>
            </html>
            HTML;

        $mockStreamThrower = $this->createMock(StreamInterface::class);
        $mockStreamThrower->method('__toString')->willReturn($throwerResponse);
        $mockResponseThrower = $this->createMock(ResponseInterface::class);
        $mockResponseThrower->method('getBody')->willReturn($mockStreamThrower);

        $mockStreamSaml1 = $this->createMock(StreamInterface::class);
        $mockStreamSaml1->method('__toString')->willReturn('<form action="https://rfcampc.siat.sat.gob.mx/Shibboleth.sso/SAML2/POST"><input type="hidden" name="SAMLResponse" value="test"/></form>');
        $mockResponseSaml1 = $this->createMock(ResponseInterface::class);
        $mockResponseSaml1->method('getBody')->willReturn($mockStreamSaml1);

        $mockStreamIframe = $this->createMock(StreamInterface::class);
        $mockStreamIframe->method('__toString')->willReturn($htmlWithIframe);
        $mockResponseIframe = $this->createMock(ResponseInterface::class);
        $mockResponseIframe->method('getBody')->willReturn($mockStreamIframe);

        $mockStreamIframeContent = $this->createMock(StreamInterface::class);
        $mockStreamIframeContent->method('__toString')->willReturn($iframeHtml);
        $mockResponseIframeContent = $this->createMock(ResponseInterface::class);
        $mockResponseIframeContent->method('getBody')->willReturn($mockStreamIframeContent);

        $mockStreamSaml2 = $this->createMock(StreamInterface::class);
        $mockStreamSaml2->method('__toString')->willReturn('<form action="https://rfcampc.siat.sat.gob.mx/final"><input type="hidden" name="data" value="test"/></form>');
        $mockResponseSaml2 = $this->createMock(ResponseInterface::class);
        $mockResponseSaml2->method('getBody')->willReturn($mockStreamSaml2);

        $mockStreamFinal = $this->createMock(StreamInterface::class);
        $mockStreamFinal->method('__toString')->willReturn($finalHtml);
        $mockResponseFinal = $this->createMock(ResponseInterface::class);
        $mockResponseFinal->method('getBody')->willReturn($mockStreamFinal);

        $this->mockClient
            ->expects($this->exactly(6))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $mockResponseThrower,
                $mockResponseSaml1,
                $mockResponseIframe,
                $mockResponseIframeContent,
                $mockResponseSaml2,
                $mockResponseFinal,
            );

        $result = $this->service->handleSsoWorkflow();

        $this->assertSame($finalHtml, $result);
        $this->assertStringContainsString('Constancia de Situación Fiscal', $result);
        $this->assertStringContainsString('formReimpAcuse', $result);
    }

    public function testHandleSsoWorkflowThrowsExceptionWhenIframeNotFound(): void
    {
        $throwerResponse = '<form action="https://test.com/saml"><input type="hidden" name="SAMLResponse" value="test"/></form>';

        $responseWithoutIframe = <<<'HTML'
            <!DOCTYPE html>
            <html lang="es">
            <head><title>Error - SAT</title></head>
            <body>
                <div id="error">
                    <p>Error en el proceso de autenticación</p>
                    <!-- NO HAY IFRAME AQUÍ -->
                </div>
            </body>
            </html>
            HTML;

        $mockStreamThrower = $this->createMock(StreamInterface::class);
        $mockStreamThrower->method('__toString')->willReturn($throwerResponse);
        $mockResponseThrower = $this->createMock(ResponseInterface::class);
        $mockResponseThrower->method('getBody')->willReturn($mockStreamThrower);

        $mockStreamSaml = $this->createMock(StreamInterface::class);
        $mockStreamSaml->method('__toString')->willReturn('<form action="https://test.com/saml2"><input type="hidden" name="data" value="test"/></form>');
        $mockResponseSaml = $this->createMock(ResponseInterface::class);
        $mockResponseSaml->method('getBody')->willReturn($mockStreamSaml);

        $mockStreamNoIframe = $this->createMock(StreamInterface::class);
        $mockStreamNoIframe->method('__toString')->willReturn($responseWithoutIframe);
        $mockResponseNoIframe = $this->createMock(ResponseInterface::class);
        $mockResponseNoIframe->method('getBody')->willReturn($mockStreamNoIframe);

        $this->mockClient
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $mockResponseThrower,
                $mockResponseSaml,
                $mockResponseNoIframe,
            );

        $this->expectException(SatException::class);
        $this->expectExceptionMessage('iframetoload not found in SSO workflow');

        $this->service->handleSsoWorkflow();
    }

    public function testHandleSsoFormsProcessesSamlResponseCorrectly(): void
    {
        $samlHtml = <<<'HTML'
            <!DOCTYPE html>
            <html>
            <body>
                <form method="post" action="https://wwwmat.sat.gob.mx/Shibboleth.sso/SAML2/POST">
                    <input type="hidden" name="RelayState" value="ss:mem:f1e2d3c4b5a6978" />
                    <input type="hidden" name="SAMLResponse"
                           value="PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHNhbWxwOlJlc3BvbnNlIHhtbG5zOnNhbWxwPSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6cHJvdG9jb2wiIElEPSJfOGFlNWFkZiIgVmVyc2lvbj0iMi4wIiBJc3N1ZUluc3RhbnQ9IjIwMjUtMTEtMzBUMTI6MzQ6NTZaIj4KICA8c2FtbDpJc3N1ZXI+aHR0cHM6Ly9sb2dpbi5zaWF0LnNhdC5nb2IubXg8L3NhbWw6SXNzdWVyPgogIDxzYW1scDpTdGF0dXM+CiAgICA8c2FtbHA6U3RhdHVzQ29kZSBWYWx1ZT0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOnN0YXR1czpTdWNjZXNzIi8+CiAgPC9zYW1scDpTdGF0dXM+CiAgPHNhbWw6QXNzZXJ0aW9uPgogICAgPHNhbWw6U3ViamVjdD4KICAgICAgPHNhbWw6TmFtZUlEPnVzZXJAc2F0LmdvYi5teDwvc2FtbDpOYW1lSUQ+CiAgICA8L3NhbWw6U3ViamVjdD4KICA8L3NhbWw6QXNzZXJ0aW9uPgo8L3NhbWxwOlJlc3BvbnNlPg==" />
                </form>
            </body>
            </html>
            HTML;

        $intermediateResponse = '<form action="https://rfcampc.siat.sat.gob.mx/next"><input type="hidden" name="token" value="xyz"/></form>';
        $finalResponse = '<html><body><h1>SSO Complete</h1></body></html>';

        $mockStream1 = $this->createMock(StreamInterface::class);
        $mockStream1->method('__toString')->willReturn($intermediateResponse);
        $mockResponse1 = $this->createMock(ResponseInterface::class);
        $mockResponse1->method('getBody')->willReturn($mockStream1);

        $mockStream2 = $this->createMock(StreamInterface::class);
        $mockStream2->method('__toString')->willReturn($finalResponse);
        $mockResponse2 = $this->createMock(ResponseInterface::class);
        $mockResponse2->method('getBody')->willReturn($mockStream2);

        $this->mockClient
            ->method('request')
            ->willReturnOnConsecutiveCalls($mockResponse1, $mockResponse2);

        $result = $this->service->handleSsoForms($samlHtml);

        $this->assertStringContainsString('SSO Complete', $result);
    }

    public function testHandleSsoFormsExtractsCorrectFormData(): void
    {
        $htmlWithMultipleFields = <<<'HTML'
            <!DOCTYPE html>
            <html>
            <body>
                <form method="post" action="https://wwwmat.sat.gob.mx/Shibboleth.sso/SAML2/POST">
                    <input type="hidden" name="RelayState" value="relay-state-value" />
                    <input type="hidden" name="SAMLResponse" value="saml-response-value" />
                    <input type="hidden" name="SigAlg" value="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256" />
                    <input type="hidden" name="Signature" value="base64-encoded-signature" />
                </form>
            </body>
            </html>
            HTML;

        $response1 = '<form action="https://next.com"><input type="hidden" name="data" value="test"/></form>';
        $response2 = '<html><body>Success</body></html>';

        $mockStream1 = $this->createMock(StreamInterface::class);
        $mockStream1->method('__toString')->willReturn($response1);
        $mockResponse1 = $this->createMock(ResponseInterface::class);
        $mockResponse1->method('getBody')->willReturn($mockStream1);

        $mockStream2 = $this->createMock(StreamInterface::class);
        $mockStream2->method('__toString')->willReturn($response2);
        $mockResponse2 = $this->createMock(ResponseInterface::class);
        $mockResponse2->method('getBody')->willReturn($mockStream2);

        $this->mockClient
            ->expects($this->exactly(2))
            ->method('request')
            ->with('POST', $this->anything(), $this->anything())
            ->willReturnOnConsecutiveCalls($mockResponse1, $mockResponse2);

        $result = $this->service->handleSsoForms($htmlWithMultipleFields);

        $this->assertStringContainsString('Success', $result);
    }
}
