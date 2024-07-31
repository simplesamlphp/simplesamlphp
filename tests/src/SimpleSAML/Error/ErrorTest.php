<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Error;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Error\Error;
use SimpleSAML\Error\ErrorCodes;
use Throwable;

/**
 */
#[CoversClass(Error::class)]
class ErrorTest extends TestCase
{
    private ErrorCodes $errorCodes;
    private array|string $errorCodeSample;
    private ?int $httpCodeSample;
    private MockObject|Throwable|null $causeMock;
    private MockObject|ErrorCodes|null $errorCodesMock;

    protected function setUp(): void
    {
        $this->errorCodes = new ErrorCodes();

        $this->errorCodeSample = ErrorCodes::WRONGUSERPASS;
        $this->causeMock = $this->createMock(Throwable::class);
        $this->httpCodeSample = 500;
        $this->errorCodesMock = $this->createMock(ErrorCodes::class);
    }

    protected function mocked(): Error
    {
        return new Error(
            $this->errorCodeSample,
            $this->causeMock,
            $this->httpCodeSample,
            $this->errorCodesMock,
        );
    }

    public function testCanInstantiateWithErrorCodeString(): void
    {
        $error = new Error(ErrorCodes::WRONGUSERPASS);

        $this->assertInstanceOf(Error::class, $error);

        $this->assertSame(ErrorCodes::WRONGUSERPASS, $error->getErrorCode());
        $this->assertIsArray($error->getParameters());
        $this->assertArrayNotHasKey('paramKey', $error->getParameters());
        $this->assertSame(
            $this->errorCodes->getTitle(ErrorCodes::WRONGUSERPASS),
            $error->getDictTitle(),
        );
        $this->assertSame(
            $this->errorCodes->getDescription(ErrorCodes::WRONGUSERPASS),
            $error->getDictDescr(),
        );
    }

    public function testCanInstantiateWithErrorCodeParamsArray(): void
    {
        $errorCodeParams = [
            ErrorCodes::WRONGUSERPASS,
            'paramKey' => 'paramValue',
        ];

        $error = new Error($errorCodeParams);

        $this->assertInstanceOf(Error::class, $error);

        $this->assertSame(ErrorCodes::WRONGUSERPASS, $error->getErrorCode());
        $this->assertIsArray($error->getParameters());
        $this->assertArrayHasKey('paramKey', $error->getParameters());
        $this->assertSame(
            $this->errorCodes->getTitle(ErrorCodes::WRONGUSERPASS),
            $error->getDictTitle(),
        );
        $this->assertSame(
            $this->errorCodes->getDescription(ErrorCodes::WRONGUSERPASS),
            $error->getDictDescr(),
        );
    }

    public function testCanUseInjectedMockedErrorCodes(): void
    {
        $testTitle = 'testTitle';
        $testDescription = 'testDescription';

        $this->errorCodesMock->expects($this->once())
            ->method('getTitle')
            ->with($this->errorCodeSample)
            ->willReturn($testTitle);

        $this->errorCodesMock->expects($this->once())
            ->method('getDescription')
            ->with($this->errorCodeSample)
            ->willReturn($testDescription);

        $error = $this->mocked();

        $this->assertSame($testTitle, $error->getDictTitle());
        $this->assertSame($testDescription, $error->getDictDescr());
    }

    public function testCanExtendWithCustomErrorCodes(): void
    {
        $customErrorCode = 'CUSTOMCODE';

        $customError = new class ($customErrorCode) extends Error
        {
            public function getErrorCodes(): ErrorCodes
            {
                return new class extends ErrorCodes
                {
                    public const CUSTOMCODE = 'CUSTOMCODE';
                    public function getCustomTitles(): array
                    {
                        return [self::CUSTOMCODE => 'customCodeTitle'];
                    }
                    public function getCustomDescriptions(): array
                    {
                        return [self::CUSTOMCODE => 'customCodeDescription'];
                    }
                };
            }
        };

        $this->assertSame('customCodeTitle', $customError->getDictTitle());
        $this->assertSame('customCodeDescription', $customError->getDictDescr());
    }
}
