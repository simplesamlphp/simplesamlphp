<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml\Auth\Process;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SAML2\Exception\ProtocolViolationException;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\Module\saml\Auth\Process\ScopedIssuer;

/**
 * Test for the saml:ScopedIssuer filter.
 */
#[CoversClass(ScopedIssuer::class)]
class ScopedIssuerTest extends TestCase
{
    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param array $config  The filter configuration.
     * @param array $request  The request state.
     * @return array  The state array after processing.
     */
    private static function processFilter(array $config, array $request): array
    {
        $filter = new ScopedIssuer($config, null);
        $filter->process($request);

        return $request;
    }


    /**
     * Test the most basic functionality
     */
    public function testBasic(): void
    {
        $config = ['scopedAttribute' => 'userPrincipalName', 'pattern' => 'https://%1$s/issuer'];
        $request = [
            'Attributes' => ['userPrincipalName' => ['phpunit@example.org']],
        ];
        $result = self::processFilter($config, $request);
        $this->assertEquals('https://example.org/issuer', $result['IdPMetadata']['entityid']);
    }


    /**
     * Test the most basic functionality, but with an unscoped scope-attribute
     */
    public function testUnscopedScope(): void
    {
        $config = ['scopedAttribute' => 'userPrincipalName', 'pattern' => 'https://%1$s/issuer'];
        $request = [
            'Attributes' => ['userPrincipalName' => ['example.org']],
        ];
        $result = self::processFilter($config, $request);
        $this->assertEquals('https://example.org/issuer', $result['IdPMetadata']['entityid']);
    }


    /**
     * Test that illegal characters in scope throws an exception.
     */
    public function testScopeIllegalCharacterThrowsException(): void
    {
        $config = ['scopedAttribute' => 'userPrincipalName', 'pattern' => 'https://%1$s/issuer'];
        $request = [
            'Attributes' => ['userPrincipalName' => ['user2@ex%ample.org']],
        ];

        $this->expectException(AssertionFailedException::class);
        self::processFilter($config, $request);
    }


    /**
     * Test that a pattern that doesn't resolve into a valid SAML entityID throws an exception.
     */
    public function testScopeIllegalPatternThrowsException(): void
    {
        $config = ['scopedAttribute' => 'userPrincipalName', 'pattern' => 'this(is*not~an!entityid-%1$s'];
        $request = [
            'Attributes' => ['userPrincipalName' => ['user2@example.org']],
        ];

        $this->expectException(ProtocolViolationException::class);
        self::processFilter($config, $request);
    }
}
