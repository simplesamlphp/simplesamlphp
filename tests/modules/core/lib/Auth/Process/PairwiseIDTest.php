<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\core\Auth\Process;

use Exception;
use PHPUnit\Framework\TestCase;
use SAML2\Constants;
use SAML2\XML\saml\NameID;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\Configuration;
use SimpleSAML\Module\core\Auth\Process\PairwiseID;
use SimpleSAML\Utils;

/**
 * Test for the core:PairwiseID filter.
 *
 * @covers \SimpleSAML\Module\core\Auth\Process\PairwiseID
 */
class PairwiseIDTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Utils\Config */
    protected static Utils\Config $configUtils;


    /**
     * Set up for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::$configUtils = new class () extends Utils\Config {
            public static function getSecretSalt(): string
            {
                // stub
                return 'secretsalt';
            }
        };
    }


    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param array $config  The filter configuration.
     * @param array $request  The request state.
     * @return array  The state array after processing.
     */
    private static function processFilter(array $config, array $request): array
    {
        $filter = new PairwiseID($config, null);
        $filter->setConfigUtils(self::$configUtils);
        $filter->process($request);
        return $request;
    }


    /**
     * Test the most basic functionality
     */
    public function testBasic()
    {
        $config = ['identifyingAttribute' => 'uid', 'scope' => 'ex-ample.org'];
        $request = [
            'Attributes' => ['uid' => ['u=se-r2']],
            'IdPMetadata' => ['entityid' => 'urn:idp'],
            'core:SP' => 'urn:sp',
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_PAIRWISE_ID, $attributes);
        $this->assertEquals(
            'b9c0de5a9852b51761797af957c4b383f8e2c7586b881444af760387272b0a6b@ex-ample.org',
            $attributes[Constants::ATTR_PAIRWISE_ID][0]
        );
    }


    /**
     * Test the most basic functionality on proxied request
     */
    public function testBasicProxiedRequest()
    {
        $config = ['identifyingAttribute' => 'uid', 'scope' => 'ex-ample.org'];
        $request = [
            'Attributes' => ['uid' => ['u=se-r2']],
            'IdPMetadata' => ['entityid' => 'urn:idp'],
            'saml:RequesterID' => [0 => 'urn:sp'],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_PAIRWISE_ID, $attributes);
        $this->assertEquals(
            'b9c0de5a9852b51761797af957c4b383f8e2c7586b881444af760387272b0a6b@ex-ample.org',
            $attributes[Constants::ATTR_PAIRWISE_ID][0]
        );
    }


    /**
     * Test that illegal characters in userID throws an exception.
     */
    public function testUserIDIllegalCharacterThrowsException()
    {
        $config = ['identifyingAttribute' => 'uid', 'scope' => 'example.org'];
        $request = [
            'Attributes' => ['uid' => ['u=se+r2']],
            'IdPMetadata' => ['entityid' => 'urn:idp'],
            'core:SP' => 'urn:sp',
        ];

        $this->expectException(AssertionFailedException::class);
        self::processFilter($config, $request);
    }


    /**
     * Test that illegal characters in scope throws an exception.
     */
    public function testScopeIllegalCharacterThrowsException()
    {
        $config = ['identifyingAttribute' => 'uid', 'scope' => 'ex%ample.org'];
        $request = [
            'Attributes' => ['uid' => ['user2']],
            'IdPMetadata' => ['entityid' => 'urn:idp'],
            'core:SP' => 'urn:sp',
        ];

        $this->expectException(AssertionFailedException::class);
        self::processFilter($config, $request);
    }
}
