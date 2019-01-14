<?php

namespace SimpleSAML\Test\Module\core\Auth\Process;

use PHPUnit\Framework\TestCase;

/**
 * Test for the core:AttributeRealm filter.
 */
class AttributeRealmTest extends TestCase
{

    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param array $config  The filter configuration.
     * @param array $request  The request state.
     * @return array  The state array after processing.
     */
    private static function processFilter(array $config, array $request)
    {
        $filter = new \SimpleSAML\Module\core\Auth\Process\AttributeRealm($config, null);
        $filter->process($request);
        return $request;
    }

    /**
     * Test the most basic functionality.
     */
    public function testBasic()
    {
        $config = [
        ];
        $request = [
            'Attributes' => [],
            'UserID' => 'user2@example.org',
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('realm', $attributes);
        $this->assertEquals($attributes['realm'], ['example.org']);
    }

    /**
     * Test no userid set
     *
     * @expectedException Exception
     */
    public function testNoUserID()
    {
        $config = [
        ];
        $request = [
            'Attributes' => [],
        ];
        self::processFilter($config, $request);
    }

    /**
     * Test with configuration.
     */
    public function testAttributeNameConfig()
    {
        $config = [
            'attributename' => 'schacHomeOrganization',
        ];
        $request = [
            'Attributes' => [
                'displayName' => 'Joe User',
                'schacGender' => 9,
            ],
            'UserID' => 'user2@example.org',
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('schacHomeOrganization', $attributes);
        $this->assertArrayHasKey('displayName', $attributes);
        $this->assertEquals($attributes['schacHomeOrganization'], ['example.org']);
    }

    /**
     * When target attribute exists it will be overwritten
     */
    public function testTargetAttributeOverwritten()
    {
        $config = [
            'attributename' => 'schacHomeOrganization',
        ];
        $request = [
            'Attributes' => [
                'displayName' => 'Joe User',
                'schacGender' => 9,
                'schacHomeOrganization' => 'example.com',
            ],
            'UserID' => 'user2@example.org',
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('schacHomeOrganization', $attributes);
        $this->assertEquals($attributes['schacHomeOrganization'], ['example.org']);
    }

    /**
     * When source attribute has no "@" no realm is added
     */
    public function testNoAtisNoOp()
    {
        $config = [];
        $request = [
            'Attributes' => [
                'displayName' => 'Joe User',
            ],
            'UserID' => 'user2',
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayNotHasKey('realm', $attributes);
    }

    /**
     * When source attribute has more than one "@" no realm is added
     */
    public function testMultiAtisNoOp()
    {
        $config = [];
        $request = [
            'Attributes' => [
                'displayName' => 'Joe User',
            ],
            'UserID' => 'user2@home@example.org',
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayNotHasKey('realm', $attributes);
    }
}
