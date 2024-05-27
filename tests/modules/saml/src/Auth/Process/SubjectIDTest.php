<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml\Auth\Process;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SAML2\Constants as C;
use SAML2\Exception\ProtocolViolationException;
use SimpleSAML\{Configuration, Logger, Utils};
use SimpleSAML\Module\saml\Auth\Process\SubjectID;

/**
 * Test for the saml:SubjectID filter.
 */
#[CoversClass(SubjectID::class)]
class SubjectIDTest extends TestCase
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
        $filter = new SubjectID($config, null);
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
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_SUBJECT_ID, $attributes);
        $this->assertMatchesRegularExpression(
            SubjectID::SPEC_PATTERN,
            $attributes[C::ATTR_SUBJECT_ID][0],
        );
        $this->assertEquals('u=se-r2@ex-ample.org', $attributes[C::ATTR_SUBJECT_ID][0]);
    }


    /**
     * Test the most basic functionality with hash
     */
    public function testBasicWithHash(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scopeAttribute' => 'scope', 'hashed' => true];
        $request = [
            'Attributes' => ['uid' => ['u=se-r2'], 'scope' => ['ex-ample.org']],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_SUBJECT_ID, $attributes);
        $this->assertMatchesRegularExpression(
            SubjectID::SPEC_PATTERN,
            $attributes[C::ATTR_SUBJECT_ID][0],
        );
        $this->assertEquals(
            '42738d01c2a66c449d010962e79da27c608c5244fd9ec311ed7c013517abf7ee@ex-ample.org',
            $attributes[C::ATTR_SUBJECT_ID][0],
        );
    }


    /**
     * Test the most basic functionality, but with a scoped scope-attribute
     */
    public function testScopedScope(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scopeAttribute' => 'scope'];
        $request = [
            'Attributes' => ['uid' => ['u=se-r2'], 'scope' => ['u=se-r2@ex-ample.org']],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_SUBJECT_ID, $attributes);
        $this->assertMatchesRegularExpression(
            SubjectID::SPEC_PATTERN,
            $attributes[C::ATTR_SUBJECT_ID][0],
        );
        $this->assertEquals('u=se-r2@ex-ample.org', $attributes[C::ATTR_SUBJECT_ID][0]);
    }


    /**
     * Test that illegal characters in userID throws an exception.
     */
    public function testUserIDIllegalCharacterThrowsException(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scopeAttribute' => 'scope'];
        $request = [
            'Attributes' => ['uid' => ['u=se+r2'], 'scope' => ['example.org']],
        ];

        $this->expectException(ProtocolViolationException::class);
        self::processFilter($config, $request);
    }


    /**
     * Test that illegal characters in scope throws an exception.
     */
    public function testScopeIllegalCharacterThrowsException(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scopeAttribute' => 'scope'];
        $request = [
            'Attributes' => ['uid' => ['user2'], 'scope' => ['ex%ample.org']],
        ];

        $this->expectException(ProtocolViolationException::class);
        self::processFilter($config, $request);
    }


    /**
     * Test that generated ID's for different users, but the same SP's are NOT equal
     */
    public function testUniqueIdentifierPerUserSameSP(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scopeAttribute' => 'scope'];
        $request = [
            'Attributes' => ['uid' => ['user1'], 'scope' => ['example.org']],
        ];

        // Generate first ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_SUBJECT_ID, $attributes);
        $value1 = $attributes[C::ATTR_SUBJECT_ID][0];

        // Switch user
        $request['Attributes']['uid'] = ['user2'];

        // Generate second ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_SUBJECT_ID, $attributes);
        $value2 = $attributes[C::ATTR_SUBJECT_ID][0];

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
        ];

        // Generate first ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_SUBJECT_ID, $attributes);
        $value1 = $attributes[C::ATTR_SUBJECT_ID][0];

        // Change the scope
        $request['Attributes']['scope'] = ['example.edu'];

        // Generate second ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(C::ATTR_SUBJECT_ID, $attributes);
        $value2 = $attributes[C::ATTR_SUBJECT_ID][0];

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
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('saml:SubjectID: Generated ID \'a@b\' can hardly be considered globally unique.');

        self::processFilter($config, $request);
    }

    /**
     * Test that weak identifiers log a warning: not an actual domain name
     */
    public function testScopeNotADomainLogsWarning(): void
    {
        $config = ['identifyingAttribute' => 'uid', 'scopeAttribute' => 'scope'];
        $request = [
            'Attributes' => ['uid' => ['a1398u9u25'], 'scope' => ['example']],
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'saml:SubjectID: Generated ID \'a1398u9u25@example\' can hardly be considered globally unique.',
        );

        self::processFilter($config, $request);
    }
}
