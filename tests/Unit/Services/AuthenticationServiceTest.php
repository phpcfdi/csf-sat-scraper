<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Tests\Unit\Services;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use PhpCfdi\CsfSatScraper\Exceptions\InvalidCaptchaException;
use PhpCfdi\CsfSatScraper\Exceptions\InvalidCredentialsException;
use PhpCfdi\CsfSatScraper\Exceptions\LoginPageNotLoadedException;
use PhpCfdi\CsfSatScraper\Exceptions\NetworkException;
use PhpCfdi\CsfSatScraper\Services\AuthenticationService;
use PhpCfdi\CsfSatScraper\URL;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class AuthenticationServiceTest extends TestCase
{
    private ClientInterface&MockObject $mockClient;

    private string $validRfc = 'XAXX010101000';

    private string $validPassword = 'testPassword123';

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(ClientInterface::class);
    }

    public function testInitializeAppSuccess(): void
    {
        $this->mockClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/nidp/app');

        $service = new AuthenticationService($this->mockClient, $this->validRfc, $this->validPassword);
        $service->initializeApp();
    }

    public function testGetLoginFormReturnsHtml(): void
    {
        $expectedHtml = '<form><div id="divCaptcha"><img src="captcha.jpg" /></div></form>';

        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('__toString')->willReturn($expectedHtml);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient
            ->method('request')
            ->willReturn($mockResponse);

        $service = new AuthenticationService($this->mockClient, $this->validRfc, $this->validPassword);
        $result = $service->getLoginForm();

        $this->assertSame($expectedHtml, $result);
    }

    public function testGetLoginFormThrowsLoginPageNotLoadedException(): void
    {
        $htmlWithoutCaptcha = '<html><body><form>No captcha here</form></body></html>';

        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('__toString')->willReturn($htmlWithoutCaptcha);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient
            ->method('request')
            ->willReturn($mockResponse);

        $service = new AuthenticationService($this->mockClient, $this->validRfc, $this->validPassword);

        $this->expectException(LoginPageNotLoadedException::class);
        $this->expectExceptionMessage('Unable to retrieve login form with captcha');

        $service->getLoginForm();
    }

    public function testSendLoginFormSuccess(): void
    {
        $expectedResponse = '<html>Login submitted</html>';

        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('__toString')->willReturn($expectedResponse);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient
            ->method('request')
            ->willReturn($mockResponse);

        $service = new AuthenticationService($this->mockClient, $this->validRfc, $this->validPassword);
        $result = $service->sendLoginForm('ABC123');

        $this->assertSame($expectedResponse, $result);
    }

    public function testCheckLoginSucceeds(): void
    {
        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('__toString')->willReturn('Welcome ' . $this->validRfc);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient
            ->method('request')
            ->willReturn($mockResponse);

        $service = new AuthenticationService($this->mockClient, $this->validRfc, $this->validPassword);
        $service->checkLogin();

        $this->assertTrue(true); /** @phpstan-ignore-line method.alreadyNarrowedType */
    }

    public function testInitializeLoginThrowsNetworkException(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $exception = new ConnectException('Connection timeout', $mockRequest);

        $this->mockClient
            ->method('request')
            ->willThrowException($exception);

        $service = new AuthenticationService($this->mockClient, $this->validRfc, $this->validPassword);

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Failed to initialize login session');

        $service->initializeApp();
    }

    public function testGetLoginFormThrowsNetworkException(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $exception = new ConnectException('Connection failed', $mockRequest);

        $this->mockClient
            ->method('request')
            ->willThrowException($exception);

        $service = new AuthenticationService($this->mockClient, $this->validRfc, $this->validPassword);

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Failed to get login form');

        $service->getLoginForm();
    }

    public function testSendLoginFormThrowsNetworkException(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $exception = new ConnectException('Network unreachable', $mockRequest);

        $this->mockClient
            ->method('request')
            ->willThrowException($exception);

        $service = new AuthenticationService($this->mockClient, $this->validRfc, $this->validPassword);

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Failed to send login form');

        $service->sendLoginForm('ABC123');
    }

    public function testCheckLoginThrowsNetworkException(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $exception = new ConnectException('Connection failed', $mockRequest);

        $this->mockClient
            ->method('request')
            ->willThrowException($exception);

        $service = new AuthenticationService($this->mockClient, $this->validRfc, $this->validPassword);

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Failed to check login');

        $service->checkLogin();
    }

    public function testCheckLoginThrowsInvalidCaptchaException(): void
    {
        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('__toString')->willReturn('<html>El captcha es incorrecto</html>');

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient
            ->method('request')
            ->willReturn($mockResponse);

        $service = new AuthenticationService($this->mockClient, $this->validRfc, $this->validPassword);

        $this->expectException(InvalidCaptchaException::class);
        $this->expectExceptionMessage('Invalid captcha');

        $service->checkLogin();
    }

    public function testCheckLoginThrowsInvalidCredentialsException(): void
    {
        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('__toString')->willReturn('<html>Login failed</html>');

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient
            ->method('request')
            ->willReturn($mockResponse);

        $service = new AuthenticationService($this->mockClient, $this->validRfc, $this->validPassword);

        $this->expectException(InvalidCredentialsException::class);
        $this->expectExceptionMessage('Invalid credentials');

        $service->checkLogin();
    }

    public function testLogoutSuccess(): void
    {
        $this->mockClient
            ->expects($this->exactly(3))
            ->method('request')
            ->willReturnCallback(function ($method, $url) {
                /** @phpstan-var int $callCount */
                static $callCount = 0;
                $this->assertEquals('GET', $method);
                $callCount++;

                $expectedUrls = [
                    URL::$logoutSatellite,
                    URL::$closeSession,
                    URL::$logout,
                ];

                $this->assertEquals($expectedUrls[$callCount - 1], $url);

                return $this->createMock(ResponseInterface::class);
            });

        $service = new AuthenticationService($this->mockClient, $this->validRfc, $this->validPassword);
        $service->logout();
    }

    public function testLogoutThrowsNetworkExceptionOnFailure(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $connectException = new ConnectException(
            'Connection failed',
            $mockRequest,
        );

        $this->mockClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', $this->anything())
            ->willThrowException($connectException);

        $service = new AuthenticationService($this->mockClient, $this->validRfc, $this->validPassword);

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Failed to logout');

        $service->logout();
    }
}
