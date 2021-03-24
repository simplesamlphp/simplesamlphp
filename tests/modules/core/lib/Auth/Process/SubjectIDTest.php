<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\core\Auth\Process;

use Exception;
use PHPUnit\Framework\TestCase;
use SAML2\Constants;
use SAML2\XML\saml\NameID;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\Configuration;
use SimpleSAML\Module\core\Auth\Process\SubjectID;
use SimpleSAML\Utils;

/**
 * Test for the core:SubjectID filter.
 *
 * @covers \SimpleSAML\Module\core\Auth\Process\SubjectID
 */
class SubjectIDTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;


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
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_SUBJECT_ID, $attributes);
        $this->assertMatchesRegularExpression(
            SubjectID::SPEC_PATTERN,
            $attributes[Constants::ATTR_SUBJECT_ID][0]
        );
        $this->assertEquals('u=se-r2@ex-ample.org', $attributes[Constants::ATTR_SUBJECT_ID][0]);
    }


    /**
     * Test that illegal characters in userID throws an exception.
     */
    public function testUserIDIllegalCharacterThrowsException()
    {
        $config = ['identifyingAttribute' => 'uid', 'scope' => 'example.org'];
        $request = [
            'Attributes' => ['uid' => ['u=se+r2']],
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
        ];

        $this->expectException(AssertionFailedException::class);
        self::processFilter($config, $request);
    }


    /**
     * Test that generated ID's for different users, but the same SP's are NOT equal
     */
    public function testUniqueIdentifierPerUserSameSP()
    {
        $config = ['identifyingAttribute' => 'uid', 'scope' => 'example.org'];
        $request = [
            'Attributes' => ['uid' => ['user1']],
        ];

        // Generate first ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_SUBJECT_ID, $attributes);
        $value1 = $attributes[Constants::ATTR_SUBJECT_ID][0];

        // Switch user
        $request['Attributes'] = ['uid' => ['user2']];

        // Generate second ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_SUBJECT_ID, $attributes);
        $value2 = $attributes[Constants::ATTR_SUBJECT_ID][0];

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
        ];

        // Generate first ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_SUBJECT_ID, $attributes);
        $value1 = $attributes[Constants::ATTR_SUBJECT_ID][0];

        // Change the scope
        $config['scope'] = 'example.edu';

        // Generate second ID
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey(Constants::ATTR_SUBJECT_ID, $attributes);
        $value2 = $attributes[Constants::ATTR_SUBJECT_ID][0];

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
