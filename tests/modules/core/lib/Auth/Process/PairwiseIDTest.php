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
            'core:SP' => 'urn:sp',
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_PAIRWISE_ID, $attributes);
        $this->assertMatchesRegularExpression(
            PairwiseID::SPEC_PATTERN,
            $attributes[Constants::ATTR_PAIRWISE_ID][0]
        );
        $this->assertEquals(
            '53d4f7fe57fb597ada481e81e0f15048bc610774cbb5614ea38f08ea918ba199@ex-ample.org',
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
            'saml:RequesterID' => [0 => 'urn:sp'],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_PAIRWISE_ID, $attributes);
        $this->assertMatchesRegularExpression(
            PairwiseID::SPEC_PATTERN,
            $attributes[Constants::ATTR_PAIRWISE_ID][0]
        );
        $this->assertEquals(
            '53d4f7fe57fb597ada481e81e0f15048bc610774cbb5614ea38f08ea918ba199@ex-ample.org',
            $attributes[Constants::ATTR_PAIRWISE_ID][0]
        );
    }


    /**
     * Test the proxied request with multiple hops
     */
    public function testProxiedRequestMultipleHops()
    {
        $config = ['identifyingAttribute' => 'uid', 'scope' => 'ex-ample.org'];
        $request = [
            'Attributes' => ['uid' => ['u=se-r2']],
            'saml:RequesterID' => [0 => 'urn:sp', 1 => 'urn:some:sp', 2 => 'urn:some:other:sp'],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_PAIRWISE_ID, $attributes);
        $this->assertMatchesRegularExpression(
            PairwiseID::SPEC_PATTERN,
            $attributes[Constants::ATTR_PAIRWISE_ID][0]
        );
        $this->assertEquals(
            '53d4f7fe57fb597ada481e81e0f15048bc610774cbb5614ea38f08ea918ba199@ex-ample.org',
            $attributes[Constants::ATTR_PAIRWISE_ID][0]
        );
    }


    /**
     * Test that illegal characters in scope throws an exception.
     */
    public function testScopeIllegalCharacterThrowsException()
    {
        $config = ['identifyingAttribute' => 'uid', 'scope' => 'ex%ample.org'];
        $request = [
            'Attributes' => ['uid' => ['user2']],
            'core:SP' => 'urn:sp',
        ];

        $this->expectException(AssertionFailedException::class);
        self::processFilter($config, $request);
    }


    /**
     * Test that generated ID's for the same user, but different SP's are NOT equal
     */
    public function testUniqueIdentifierPerSPSameUser()
    {
        $config = ['identifyingAttribute' => 'uid', 'scope' => 'example.org'];
        $request = [
            'Attributes' => ['uid' => ['user1']],
            'core:SP' => 'urn:sp',
        ];

        // Generate first ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_PAIRWISE_ID, $attributes);
        $value1 = $attributes[Constants::ATTR_PAIRWISE_ID][0];

        // Switch SP
        $request['core:SP'] = 'urn:some:other:sp';

        // Generate second ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_PAIRWISE_ID, $attributes);
        $value2 = $attributes[Constants::ATTR_PAIRWISE_ID][0];

        $this->assertNotSame($value1, $value2);
    }


    /**
     * Test that generated ID's for different users, but the same SP's are NOT equal
     */
    public function testUniqueIdentifierPerUserSameSP()
    {
        $config = ['identifyingAttribute' => 'uid', 'scope' => 'example.org'];
        $request = [
            'Attributes' => ['uid' => ['user1']],
            'core:SP' => 'urn:sp',
        ];

        // Generate first ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_PAIRWISE_ID, $attributes);
        $value1 = $attributes[Constants::ATTR_PAIRWISE_ID][0];

        // Switch user
        $request['Attributes'] = ['uid' => ['user2']];

        // Generate second ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_PAIRWISE_ID, $attributes);
        $value2 = $attributes[Constants::ATTR_PAIRWISE_ID][0];

        $this->assertNotSame($value1, $value2);
    }


    /**
     * Test that generated ID's for the same user and same SP, but with a different salt are NOT equal
     */
    public function testUniqueIdentifierDifferentSalts()
    {
        $config = ['identifyingAttribute' => 'uid', 'scope' => 'example.org'];
        $request = [
            'Attributes' => ['uid' => ['user1']],
            'core:SP' => 'urn:sp',
        ];

        // Generate first ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_PAIRWISE_ID, $attributes);
        $value1 = $attributes[Constants::ATTR_PAIRWISE_ID][0];

        // Change the salt
        self::$configUtils = new class () extends Utils\Config {
            public static function getSecretSalt(): string
            {
                // stub
                return 'pepper';
            }
        };

        // Generate second ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_PAIRWISE_ID, $attributes);
        $value2 = $attributes[Constants::ATTR_PAIRWISE_ID][0];

        $this->assertNotSame($value1, $value2);
    }


    /**
     * Test that generated ID's for the same user and same SP, but with a different scope are NOT equal
     */
    public function testUniqueIdentifierDifferentScopes()
    {
        $config = ['identifyingAttribute' => 'uid', 'scope' => 'example.org'];
        $request = [
            'Attributes' => ['uid' => ['user1']],
            'core:SP' => 'urn:sp',
        ];

        // Generate first ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_PAIRWISE_ID, $attributes);
        $value1 = $attributes[Constants::ATTR_PAIRWISE_ID][0];

        // Change the scope
        $config['scope'] = 'example.edu';

        // Generate second ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_PAIRWISE_ID, $attributes);
        $value2 = $attributes[Constants::ATTR_PAIRWISE_ID][0];

        $this->assertNotSame($value1, $value2);

        $this->assertMatchesRegularExpression(
            '/@example.org$/i',
            $value1
        );
        $this->assertMatchesRegularExpression(
            '/@example.edu$/i',
            $value2
        );
    }
}
