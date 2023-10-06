<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Error;

use SimpleSAML\Error\Error;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Error\ErrorCodes;

/**
 * @covers \SimpleSAML\Error\Error
 */
class ErrorTest extends TestCase
{
    public function testCanInstantiateWithErrorCodeString(): void
    {
        $error = new Error(ErrorCodes::WRONGUSERPASS);

        $this->assertInstanceOf(Error::class, $error);

        $this->assertSame(ErrorCodes::WRONGUSERPASS, $error->getErrorCode());
        $this->assertIsArray($error->getParameters());
        $this->assertArrayNotHasKey('paramKey', $error->getParameters());
        $this->assertSame(
            ErrorCodes::getErrorCodeTitle(ErrorCodes::WRONGUSERPASS),
            $error->getDictTitle()
        );
        $this->assertSame(
            ErrorCodes::getErrorCodeDescription(ErrorCodes::WRONGUSERPASS),
            $error->getDictDescr()
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
            ErrorCodes::getErrorCodeTitle(ErrorCodes::WRONGUSERPASS),
            $error->getDictTitle()
        );
        $this->assertSame(
            ErrorCodes::getErrorCodeDescription(ErrorCodes::WRONGUSERPASS),
            $error->getDictDescr()
        );
    }

    public function testCanExtendWithCustomErrorCodes(): void
    {
        $customErrorCode = 'CUSTOMCODE';

        $customError = new class ($customErrorCode) extends Error
        {
            protected function getErrorCodes(): ErrorCodes
            {
                return new class extends ErrorCodes
                {
                    public const CUSTOMCODE = 'CUSTOMCODE';
                    public static function getCustomErrorCodeTitles(): array
                    {
                        return [self::CUSTOMCODE => 'customCodeTitle'];
                    }
                    public static function getCustomErrorCodeDescriptions(): array
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
