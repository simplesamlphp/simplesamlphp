<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Error;

use SimpleSAML\Error\ErrorCodes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SimpleSAML\Error\ErrorCodes
 */
class ErrorCodesTest extends TestCase
{
    public function testCanGetFallbackValuesForNonExistentErrorCode(): void
    {
        $nonExistentCode = 'nonexistent';
        $this->assertStringContainsString($nonExistentCode, ErrorCodes::getErrorCodeTitle($nonExistentCode));
        $this->assertStringContainsString($nonExistentCode, ErrorCodes::getErrorCodeDescription($nonExistentCode));
    }

    public function testCanGetDefaultErrorCodes(): void
    {
        $this->assertSameSize(
            ErrorCodes::defaultGetAllErrorCodeTitles(),
            ErrorCodes::defaultGetAllErrorCodeDescriptions(),
            'Not all error codes have their title / description pair.'
        );

        $this->assertArrayHasKey(ErrorCodes::WRONGUSERPASS, ErrorCodes::defaultGetAllErrorCodeTitles());
        $this->assertArrayHasKey(ErrorCodes::WRONGUSERPASS, ErrorCodes::defaultGetAllErrorCodeDescriptions());
        $this->assertArrayHasKey(ErrorCodes::WRONGUSERPASS, ErrorCodes::getAllErrorCodeDescriptions());
        $this->assertArrayHasKey(ErrorCodes::WRONGUSERPASS, ErrorCodes::getAllErrorCodeTitles());

        $this->assertSame(
            ErrorCodes::defaultGetAllErrorCodeTitles()[ErrorCodes::WRONGUSERPASS],
            ErrorCodes::getErrorCodeTitle(ErrorCodes::WRONGUSERPASS)
        );
        $this->assertSame(
            ErrorCodes::defaultGetAllErrorCodeDescriptions()[ErrorCodes::WRONGUSERPASS],
            ErrorCodes::getErrorCodeDescription(ErrorCodes::WRONGUSERPASS)
        );

        $this->assertSame(
            ErrorCodes::getAllErrorCodeTitles(),
            ErrorCodes::getAllErrorCodeMessages()[ErrorCodes::KEY_TITLE]
        );
        $this->assertSame(
            ErrorCodes::getAllErrorCodeDescriptions(),
            ErrorCodes::getAllErrorCodeMessages()[ErrorCodes::KEY_DESCRIPTION]
        );

        $this->assertSame(
            ErrorCodes::getErrorCodeTitle(ErrorCodes::WRONGUSERPASS),
            ErrorCodes::getErrorCodeMessage(ErrorCodes::WRONGUSERPASS)[ErrorCodes::KEY_TITLE]
        );
        $this->assertSame(
            ErrorCodes::getErrorCodeDescription(ErrorCodes::WRONGUSERPASS),
            ErrorCodes::getErrorCodeMessage(ErrorCodes::WRONGUSERPASS)[ErrorCodes::KEY_DESCRIPTION]
        );
    }

    public function testCanExtendWithCustomErrorCodes(): void
    {
        $customErrorCodes = new class extends ErrorCodes
        {
            public const CUSTOMCODE = 'CUSTOMCODE';
            public static string $customTitle = 'customTitle';
            public static string $customDescription = 'customDescription';

            public static function getCustomErrorCodeTitles(): array
            {
                return [self::CUSTOMCODE => self::$customTitle];
            }

            public static function getCustomErrorCodeDescriptions(): array
            {
                return [self::CUSTOMCODE => self::$customDescription];
            }
        };

        $this->assertSameSize(
            ErrorCodes::getCustomErrorCodeTitles(),
            ErrorCodes::getCustomErrorCodeDescriptions(),
            'Not all custom error codes have their title / description pair.'
        );

        $this->assertArrayHasKey($customErrorCodes::CUSTOMCODE, $customErrorCodes::getCustomErrorCodeTitles());
        $this->assertArrayHasKey($customErrorCodes::CUSTOMCODE, $customErrorCodes::getCustomErrorCodeDescriptions());
        $this->assertArrayHasKey($customErrorCodes::CUSTOMCODE, $customErrorCodes::getAllErrorCodeDescriptions());
        $this->assertArrayHasKey($customErrorCodes::CUSTOMCODE, $customErrorCodes::getAllErrorCodeTitles());

        $this->assertSame(
            $customErrorCodes::getCustomErrorCodeTitles()[$customErrorCodes::CUSTOMCODE],
            $customErrorCodes::getErrorCodeTitle($customErrorCodes::CUSTOMCODE)
        );
        $this->assertSame(
            $customErrorCodes::getCustomErrorCodeDescriptions()[$customErrorCodes::CUSTOMCODE],
            $customErrorCodes::getErrorCodeDescription($customErrorCodes::CUSTOMCODE)
        );

        $this->assertSame(
            $customErrorCodes::getAllErrorCodeTitles(),
            $customErrorCodes::getAllErrorCodeMessages()[ErrorCodes::KEY_TITLE]
        );
        $this->assertSame(
            $customErrorCodes::getAllErrorCodeDescriptions(),
            $customErrorCodes::getAllErrorCodeMessages()[ErrorCodes::KEY_DESCRIPTION]
        );

        $this->assertSame(
            $customErrorCodes::getErrorCodeTitle($customErrorCodes::CUSTOMCODE),
            $customErrorCodes::getErrorCodeMessage($customErrorCodes::CUSTOMCODE)[ErrorCodes::KEY_TITLE]
        );
        $this->assertSame(
            $customErrorCodes::getErrorCodeDescription($customErrorCodes::CUSTOMCODE),
            $customErrorCodes::getErrorCodeMessage($customErrorCodes::CUSTOMCODE)[ErrorCodes::KEY_DESCRIPTION]
        );
    }
}
