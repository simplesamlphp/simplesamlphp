<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\core\Auth\Process;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SAML2\Constants;
use SAML2\XML\saml\NameID;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
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
    protected $config;

    /** @var \SimpleSAML\Utils\Config */
    protected static $configUtils;

    /** @var \SimpleSAML\Logger */
    protected static $logger;


    /**
     * Set up for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::$configUtils = new class () extends Utils\Config {
            public static function getSecretSalt()
            {
                // stub
                return 'secretsalt';
            }
        };

        self::$logger = new class () extends Logger {
            public static function warning($string)
            {
                // stub
                throw new RuntimeException($string);
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
        $filter->setLogger(self::$logger);
        $filter->process($request);
        return $request;
    }


    /**
     * Test the most basic functionality
     */
    public function testBasic(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scope' => 'ex-ample.org'];
        $request = [
            'Attributes' => ['uid' => ['u=se-r2']],
            'core:SP' => 'urn:sp',
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_PAIRWISE_ID, $attributes);
        $this->assertRegExp(
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
    public function testBasicProxiedRequest(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scope' => 'ex-ample.org'];
        $request = [
            'Attributes' => ['uid' => ['u=se-r2']],
            'saml:RequesterID' => [0 => 'urn:sp'],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_PAIRWISE_ID, $attributes);
        $this->assertRegExp(
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
    public function testProxiedRequestMultipleHops(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scope' => 'ex-ample.org'];
        $request = [
            'Attributes' => ['uid' => ['u=se-r2']],
            'saml:RequesterID' => [0 => 'urn:sp', 1 => 'urn:some:sp', 2 => 'urn:some:other:sp'],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_PAIRWISE_ID, $attributes);
        $this->assertRegExp(
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
    public function testScopeIllegalCharacterThrowsException(): void
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
    public function testUniqueIdentifierPerSPSameUser(): void
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
    public function testUniqueIdentifierPerUserSameSP(): void
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
    public function testUniqueIdentifierDifferentSalts(): void
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
            public static function getSecretSalt()
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
    public function testUniqueIdentifierDifferentScopes(): void
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

        $this->assertRegExp(
            '/@example.org$/i',
            $value1
        );
        $this->assertRegExp(
            '/@example.edu$/i',
            $value2
        );
    }


    /**
     * Test that weak identifiers log a warning
     */
    public function testWeakIdentifierLogsWarning(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scope' => 'b'];
        $request = [
            'Attributes' => ['uid' => ['a']],
            'core:SP' => 'urn:sp',
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'core:PairwiseID: Generated ID \'c5b54935db5e291a6b94688921fa77ced8ce425ce8c61a448bd4997f494dbebe@b\' can hardly be considered globally unique.'
        );

        self::processFilter($config, $request);
    }
}
