<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml\Auth\Process;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SAML2\Constants as C;
use SAML2\Exception\ProtocolViolationException;
use SimpleSAML\{Configuration, Logger, Utils};
use SimpleSAML\Module\saml\Auth\Process\PairwiseID;

/**
 * Test for the saml:PairwiseID filter.
 */
#[CoversClass(PairwiseID::class)]
class PairwiseIDTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Utils\Config */
    protected static Utils\Config $configUtils;

    /** @var \SimpleSAML\Logger */
    protected static Logger $logger;


    /**
     * Set up for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::$configUtils = new class () extends Utils\Config {
            public function getSecretSalt(): string
            {
                // stub
                return 'secretsalt';
            }
        };

        self::$logger = new class () extends Logger {
            public static function warning(string $string): void
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
        $config = ['identifyingAttribute' => 'uid', 'scopeAttribute' => 'scope'];
        $request = [
            'Attributes' => ['uid' => ['u=se-r2'], 'scope' => ['ex-ample.org']],
            'core:SP' => 'urn:sp',
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_PAIRWISE_ID, $attributes);
        $this->assertMatchesRegularExpression(
            PairwiseID::SPEC_PATTERN,
            $attributes[C::ATTR_PAIRWISE_ID][0],
        );
        $this->assertEquals(
            'c22d58bebef42e50e203d0e932ae4a7f560a51d494266990a5b5c73f34b1854e@ex-ample.org',
            $attributes[C::ATTR_PAIRWISE_ID][0],
        );
    }


    /**
     * Test the most basic functionality, but with a scoped scope-attribute
     */
    public function testBasicScopedScope(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scopeAttribute' => 'scope'];
        $request = [
            'Attributes' => ['uid' => ['u=se-r2'], 'scope' => ['u=se-r2@ex-ample.org']],
            'core:SP' => 'urn:sp',
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_PAIRWISE_ID, $attributes);
        $this->assertMatchesRegularExpression(
            PairwiseID::SPEC_PATTERN,
            $attributes[C::ATTR_PAIRWISE_ID][0],
        );
        $this->assertEquals(
            'c22d58bebef42e50e203d0e932ae4a7f560a51d494266990a5b5c73f34b1854e@ex-ample.org',
            $attributes[C::ATTR_PAIRWISE_ID][0],
        );
    }


    /**
     * Test the most basic functionality on proxied request
     */
    public function testBasicProxiedRequest(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scopeAttribute' => 'scope'];
        $request = [
            'Attributes' => ['uid' => ['u=se-r2'], 'scope' => ['ex-ample.org']],
            'saml:RequesterID' => [0 => 'urn:sp'],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_PAIRWISE_ID, $attributes);
        $this->assertMatchesRegularExpression(
            PairwiseID::SPEC_PATTERN,
            $attributes[C::ATTR_PAIRWISE_ID][0],
        );
        $this->assertEquals(
            'c22d58bebef42e50e203d0e932ae4a7f560a51d494266990a5b5c73f34b1854e@ex-ample.org',
            $attributes[C::ATTR_PAIRWISE_ID][0],
        );
    }


    /**
     * Test the proxied request with multiple hops
     */
    public function testProxiedRequestMultipleHops(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scopeAttribute' => 'scope'];
        $request = [
            'Attributes' => ['uid' => ['u=se-r2'], 'scope' => ['ex-ample.org']],
            'saml:RequesterID' => [0 => 'urn:sp', 1 => 'urn:some:sp', 2 => 'urn:some:other:sp'],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_PAIRWISE_ID, $attributes);
        $this->assertMatchesRegularExpression(
            PairwiseID::SPEC_PATTERN,
            $attributes[C::ATTR_PAIRWISE_ID][0],
        );
        $this->assertEquals(
            'c22d58bebef42e50e203d0e932ae4a7f560a51d494266990a5b5c73f34b1854e@ex-ample.org',
            $attributes[C::ATTR_PAIRWISE_ID][0],
        );
    }


    /**
     * Test that illegal characters in scope throws an exception.
     */
    public function testScopeIllegalCharacterThrowsException(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scopeAttribute' => 'scope'];
        $request = [
            'Attributes' => ['uid' => ['user2'], 'scope' => ['ex%ample.org']],
            'core:SP' => 'urn:sp',
        ];

        $this->expectException(ProtocolViolationException::class);
        self::processFilter($config, $request);
    }


    /**
     * Test that generated ID's for the same user, but different SP's are NOT equal
     */
    public function testUniqueIdentifierPerSPSameUser(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scopeAttribute' => 'scope'];
        $request = [
            'Attributes' => ['uid' => ['user1'], 'scope' => ['example.org']],
            'core:SP' => 'urn:sp',
        ];

        // Generate first ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_PAIRWISE_ID, $attributes);
        $value1 = $attributes[C::ATTR_PAIRWISE_ID][0];

        // Switch SP
        $request['core:SP'] = 'urn:some:other:sp';

        // Generate second ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_PAIRWISE_ID, $attributes);
        $value2 = $attributes[C::ATTR_PAIRWISE_ID][0];

        $this->assertNotSame($value1, $value2);
    }


    /**
     * Test that generated ID's for different users, but the same SP's are NOT equal
     */
    public function testUniqueIdentifierPerUserSameSP(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scopeAttribute' => 'scope'];
        $request = [
            'Attributes' => ['uid' => ['user1'], 'scope' => ['example.org']],
            'core:SP' => 'urn:sp',
        ];

        // Generate first ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_PAIRWISE_ID, $attributes);
        $value1 = $attributes[C::ATTR_PAIRWISE_ID][0];

        // Switch user
        $request['Attributes']['uid'] = ['user2'];

        // Generate second ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_PAIRWISE_ID, $attributes);
        $value2 = $attributes[C::ATTR_PAIRWISE_ID][0];

        $this->assertNotSame($value1, $value2);
    }


    /**
     * Test that generated ID's for the same user and same SP, but with a different salt are NOT equal
     */
    public function testUniqueIdentifierDifferentSalts(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scopeAttribute' => 'scope'];
        $request = [
            'Attributes' => ['uid' => ['user1'], 'scope' => ['example.org']],
            'core:SP' => 'urn:sp',
        ];

        // Generate first ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_PAIRWISE_ID, $attributes);
        $value1 = $attributes[C::ATTR_PAIRWISE_ID][0];

        // Change the salt
        self::$configUtils = new class () extends Utils\Config {
            public function getSecretSalt(): string
            {
                // stub
                return 'pepper';
            }
        };

        // Generate second ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_PAIRWISE_ID, $attributes);
        $value2 = $attributes[C::ATTR_PAIRWISE_ID][0];

        $this->assertNotSame($value1, $value2);
    }


    /**
     * Test that generated ID's for the same user and same SP, but with a different scope are NOT equal
     */
    public function testUniqueIdentifierDifferentScopes(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scopeAttribute' => 'scope'];
        $request = [
            'Attributes' => ['uid' => ['user1'], 'scope' => ['example.org']],
            'core:SP' => 'urn:sp',
        ];

        // Generate first ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_PAIRWISE_ID, $attributes);
        $value1 = $attributes[C::ATTR_PAIRWISE_ID][0];

        // Change the scope
        $request['Attributes']['scope'] = ['example.edu'];

        // Generate second ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_PAIRWISE_ID, $attributes);
        $value2 = $attributes[C::ATTR_PAIRWISE_ID][0];

        $this->assertNotSame($value1, $value2);

        $this->assertMatchesRegularExpression(
            '/@example.org$/i',
            $value1,
        );
        $this->assertMatchesRegularExpression(
            '/@example.edu$/i',
            $value2,
        );
    }


    /**
     * Test that weak identifiers log a warning
     */
    public function testWeakIdentifierLogsWarning(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scopeAttribute' => 'scope'];
        $request = [
            'Attributes' => ['uid' => ['a'], 'scope' => ['b']],
            'core:SP' => 'urn:sp',
        ];

        $expected = 'be511fc7f95e22816dbac21e3b70546660963b6e9b85f5a41d80bfc6baadd547@b';
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'saml:PairwiseID: Generated ID \'' . $expected . '\' can hardly be considered globally unique.',
        );

        self::processFilter($config, $request);
    }
}
