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

    public function testCanGetFallbackValuesForNonExistentErrorCode(): void
    {
        $nonExistentCode = 'nonexistent';
        $this->assertStringContainsString($nonExistentCode, $this->instance()->getTitle($nonExistentCode));
        $this->assertStringContainsString($nonExistentCode, $this->instance()->getDescription($nonExistentCode));
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
}
