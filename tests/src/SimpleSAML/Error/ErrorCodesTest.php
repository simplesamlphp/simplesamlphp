<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Error;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Error\ErrorCodes;

/**
 */
#[CoversClass(ErrorCodes::class)]
class ErrorCodesTest extends TestCase
{
    protected function instance(): ErrorCodes
    {
        return new ErrorCodes();
    }

    /**
     * @deprecated
     */
    public function testCanStaticallyGetFallbackValuesForNonExistentErrorCode(): void
    {
        $nonExistentCode = 'nonexistent';
        $this->assertStringContainsString($nonExistentCode, ErrorCodes::getErrorCodeTitle($nonExistentCode));
        $this->assertStringContainsString($nonExistentCode, ErrorCodes::getErrorCodeDescription($nonExistentCode));
    }

    public function testCanGetFallbackValuesForNonExistentErrorCode(): void
    {
        $nonExistentCode = 'nonexistent';
        $this->assertStringContainsString($nonExistentCode, $this->instance()->getTitle($nonExistentCode));
        $this->assertStringContainsString($nonExistentCode, $this->instance()->getDescription($nonExistentCode));
    }

    /**
     * @deprecated
     */
    public function testCanStaticallyGetDefaultErrorCodes(): void
    {
        $this->assertSameSize(
            ErrorCodes::defaultGetAllErrorCodeTitles(),
            ErrorCodes::defaultGetAllErrorCodeDescriptions(),
            'Not all error codes have their title / description pair.',
        );

        $this->assertArrayHasKey(ErrorCodes::WRONGUSERPASS, ErrorCodes::defaultGetAllErrorCodeTitles());
        $this->assertArrayHasKey(ErrorCodes::WRONGUSERPASS, ErrorCodes::defaultGetAllErrorCodeDescriptions());
        $this->assertArrayHasKey(ErrorCodes::WRONGUSERPASS, ErrorCodes::getAllErrorCodeDescriptions());
        $this->assertArrayHasKey(ErrorCodes::WRONGUSERPASS, ErrorCodes::getAllErrorCodeTitles());

        $this->assertSame(
            ErrorCodes::defaultGetAllErrorCodeTitles()[ErrorCodes::WRONGUSERPASS],
            ErrorCodes::getErrorCodeTitle(ErrorCodes::WRONGUSERPASS),
        );
        $this->assertSame(
            ErrorCodes::defaultGetAllErrorCodeDescriptions()[ErrorCodes::WRONGUSERPASS],
            ErrorCodes::getErrorCodeDescription(ErrorCodes::WRONGUSERPASS),
        );

        $this->assertSame(
            ErrorCodes::getAllErrorCodeTitles(),
            ErrorCodes::getAllErrorCodeMessages()[ErrorCodes::KEY_TITLE],
        );
        $this->assertSame(
            ErrorCodes::getAllErrorCodeDescriptions(),
            ErrorCodes::getAllErrorCodeMessages()[ErrorCodes::KEY_DESCRIPTION],
        );

        $this->assertSame(
            ErrorCodes::getErrorCodeTitle(ErrorCodes::WRONGUSERPASS),
            ErrorCodes::getErrorCodeMessage(ErrorCodes::WRONGUSERPASS)[ErrorCodes::KEY_TITLE],
        );
        $this->assertSame(
            ErrorCodes::getErrorCodeDescription(ErrorCodes::WRONGUSERPASS),
            ErrorCodes::getErrorCodeMessage(ErrorCodes::WRONGUSERPASS)[ErrorCodes::KEY_DESCRIPTION],
        );
    }

    public function testCanGetDefaultErrorCodes(): void
    {
        $this->assertSameSize(
            $this->instance()->getDefaultTitles(),
            $this->instance()->getDefaultDescriptions(),
            'Not all error codes have their title / description pair.',
        );

        $this->assertArrayHasKey(ErrorCodes::WRONGUSERPASS, $this->instance()->getDefaultTitles());
        $this->assertArrayHasKey(ErrorCodes::WRONGUSERPASS, $this->instance()->getDefaultDescriptions());
        $this->assertArrayHasKey(ErrorCodes::WRONGUSERPASS, $this->instance()->getAllDescriptions());
        $this->assertArrayHasKey(ErrorCodes::WRONGUSERPASS, $this->instance()->getAllTitles());

        $this->assertSame(
            $this->instance()->getDefaultTitles()[ErrorCodes::WRONGUSERPASS],
            $this->instance()->getTitle(ErrorCodes::WRONGUSERPASS),
        );
        $this->assertSame(
            $this->instance()->getDefaultDescriptions()[ErrorCodes::WRONGUSERPASS],
            $this->instance()->getDescription(ErrorCodes::WRONGUSERPASS),
        );

        $this->assertSame(
            $this->instance()->getAllTitles(),
            $this->instance()->getAllMessages()[ErrorCodes::KEY_TITLE],
        );
        $this->assertSame(
            $this->instance()->getAllDescriptions(),
            $this->instance()->getAllMessages()[ErrorCodes::KEY_DESCRIPTION],
        );

        $this->assertSame(
            $this->instance()->getTitle(ErrorCodes::WRONGUSERPASS),
            $this->instance()->getMessage(ErrorCodes::WRONGUSERPASS)[ErrorCodes::KEY_TITLE],
        );
        $this->assertSame(
            $this->instance()->getDescription(ErrorCodes::WRONGUSERPASS),
            $this->instance()->getMessage(ErrorCodes::WRONGUSERPASS)[ErrorCodes::KEY_DESCRIPTION],
        );
    }

    /**
     * @deprecated
     */
    public function testCanStaticallyExtendWithCustomErrorCodes(): void
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
            $customErrorCodes::getCustomErrorCodeTitles(),
            $customErrorCodes::getCustomErrorCodeDescriptions(),
            'Not all custom error codes have their title / description pair.',
        );

        $this->assertArrayHasKey($customErrorCodes::CUSTOMCODE, $customErrorCodes::getCustomErrorCodeTitles());
        $this->assertArrayHasKey($customErrorCodes::CUSTOMCODE, $customErrorCodes::getCustomErrorCodeDescriptions());
        $this->assertArrayHasKey($customErrorCodes::CUSTOMCODE, $customErrorCodes::getAllErrorCodeDescriptions());
        $this->assertArrayHasKey($customErrorCodes::CUSTOMCODE, $customErrorCodes::getAllErrorCodeTitles());

        $this->assertSame(
            $customErrorCodes::getCustomErrorCodeTitles()[$customErrorCodes::CUSTOMCODE],
            $customErrorCodes::getErrorCodeTitle($customErrorCodes::CUSTOMCODE),
        );
        $this->assertSame(
            $customErrorCodes::getCustomErrorCodeDescriptions()[$customErrorCodes::CUSTOMCODE],
            $customErrorCodes::getErrorCodeDescription($customErrorCodes::CUSTOMCODE),
        );

        $this->assertSame(
            $customErrorCodes::getAllErrorCodeTitles(),
            $customErrorCodes::getAllErrorCodeMessages()[ErrorCodes::KEY_TITLE],
        );
        $this->assertSame(
            $customErrorCodes::getAllErrorCodeDescriptions(),
            $customErrorCodes::getAllErrorCodeMessages()[ErrorCodes::KEY_DESCRIPTION],
        );

        $this->assertSame(
            $customErrorCodes::getErrorCodeTitle($customErrorCodes::CUSTOMCODE),
            $customErrorCodes::getErrorCodeMessage($customErrorCodes::CUSTOMCODE)[ErrorCodes::KEY_TITLE],
        );
        $this->assertSame(
            $customErrorCodes::getErrorCodeDescription($customErrorCodes::CUSTOMCODE),
            $customErrorCodes::getErrorCodeMessage($customErrorCodes::CUSTOMCODE)[ErrorCodes::KEY_DESCRIPTION],
        );
    }

    /**
     * @deprecated
     */
    public function testCanExtendWithCustomErrorCodes(): void
    {
        $customErrorCodes = new class extends ErrorCodes
        {
            public const CUSTOMCODE = 'CUSTOMCODE';
            public static string $customTitle = 'customTitle';
            public static string $customDescription = 'customDescription';

            public function getCustomTitles(): array
            {
                return [self::CUSTOMCODE => self::$customTitle];
            }

            public function getCustomDescriptions(): array
            {
                return [self::CUSTOMCODE => self::$customDescription];
            }
        };

        $this->assertSameSize(
            $customErrorCodes->getCustomTitles(),
            $customErrorCodes->getCustomDescriptions(),
            'Not all custom error codes have their title / description pair.',
        );

        $this->assertArrayHasKey($customErrorCodes::CUSTOMCODE, $customErrorCodes->getCustomTitles());
        $this->assertArrayHasKey($customErrorCodes::CUSTOMCODE, $customErrorCodes->getCustomDescriptions());
        $this->assertArrayHasKey($customErrorCodes::CUSTOMCODE, $customErrorCodes->getAllTitles());
        $this->assertArrayHasKey($customErrorCodes::CUSTOMCODE, $customErrorCodes->getAllDescriptions());

        $this->assertSame(
            $customErrorCodes->getCustomTitles()[$customErrorCodes::CUSTOMCODE],
            $customErrorCodes->getTitle($customErrorCodes::CUSTOMCODE),
        );
        $this->assertSame(
            $customErrorCodes->getCustomDescriptions()[$customErrorCodes::CUSTOMCODE],
            $customErrorCodes->getDescription($customErrorCodes::CUSTOMCODE),
        );

        $this->assertSame(
            $customErrorCodes->getAllTitles(),
            $customErrorCodes->getAllMessages()[ErrorCodes::KEY_TITLE],
        );
        $this->assertSame(
            $customErrorCodes->getAllDescriptions(),
            $customErrorCodes->getAllMessages()[ErrorCodes::KEY_DESCRIPTION],
        );

        $this->assertSame(
            $customErrorCodes->getTitle($customErrorCodes::CUSTOMCODE),
            $customErrorCodes->getMessage($customErrorCodes::CUSTOMCODE)[ErrorCodes::KEY_TITLE],
        );
        $this->assertSame(
            $customErrorCodes->getDescription($customErrorCodes::CUSTOMCODE),
            $customErrorCodes->getMessage($customErrorCodes::CUSTOMCODE)[ErrorCodes::KEY_DESCRIPTION],
        );
    }
}
