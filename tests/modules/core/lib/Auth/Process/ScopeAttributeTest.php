<?php

namespace SimpleSAML\Test\Module\core\Auth\Process;

use PHPUnit\Framework\TestCase;

/**
 * Test for the core:ScopeAttribute filter.
 */
class ScopeAttributeTest extends TestCase
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
        $filter = new \SimpleSAML\Module\core\Auth\Process\ScopeAttribute($config, null);
        $filter->process($request);
        return $request;
    }


    /**
     * Test the most basic functionality.
     * @return void
     */
    public function testBasic()
    {
        $config = [
            'scopeAttribute' => 'eduPersonPrincipalName',
            'sourceAttribute' => 'eduPersonAffiliation',
            'targetAttribute' => 'eduPersonScopedAffiliation',
        ];
        $request = [
            'Attributes' => [
                'eduPersonPrincipalName' => ['jdoe@example.com'],
                'eduPersonAffiliation' => ['member'],
            ]
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('eduPersonScopedAffiliation', $attributes);
        $this->assertEquals($attributes['eduPersonScopedAffiliation'], ['member@example.com']);
    }


    /**
     * If target attribute already set, module must add, not overwrite.
     * @return void
     */
    public function testNoOverwrite()
    {
        $config = [
            'scopeAttribute' => 'eduPersonPrincipalName',
            'sourceAttribute' => 'eduPersonAffiliation',
            'targetAttribute' => 'eduPersonScopedAffiliation',
        ];
        $request = [
            'Attributes' => [
                'eduPersonPrincipalName' => ['jdoe@example.com'],
                'eduPersonAffiliation' => ['member'],
                'eduPersonScopedAffiliation' => ['library-walk-in@example.edu'],
            ]
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertEquals(
            $attributes['eduPersonScopedAffiliation'],
            ['library-walk-in@example.edu', 'member@example.com']
        );
    }


    /**
     * If same scope already set, module must do nothing, not duplicate value.
     * @return void
     */
    public function testNoDuplication()
    {
        $config = [
            'scopeAttribute' => 'eduPersonPrincipalName',
            'sourceAttribute' => 'eduPersonAffiliation',
            'targetAttribute' => 'eduPersonScopedAffiliation',
        ];
        $request = [
            'Attributes' => [
                'eduPersonPrincipalName' => ['jdoe@example.com'],
                'eduPersonAffiliation' => ['member'],
                'eduPersonScopedAffiliation' => ['member@example.com'],
            ]
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertEquals($attributes['eduPersonScopedAffiliation'], ['member@example.com']);
    }


    /**
     * If source attribute not set, nothing happens
     * @return void
     */
    public function testNoSourceAttribute()
    {
        $config = [
            'scopeAttribute' => 'eduPersonPrincipalName',
            'sourceAttribute' => 'eduPersonAffiliation',
            'targetAttribute' => 'eduPersonScopedAffiliation',
        ];
        $request = [
            'Attributes' => [
                'mail' => ['j.doe@example.edu', 'john@example.org'],
                'eduPersonAffiliation' => ['member'],
                'eduPersonScopedAffiliation' => ['library-walk-in@example.edu'],
            ]
        ];
        $result = self::processFilter($config, $request);
        $this->assertEquals($request['Attributes'], $result['Attributes']);
    }


    /**
     * If scope attribute not set, nothing happens
     * @return void
     */
    public function testNoScopeAttribute()
    {
        $config = [
            'scopeAttribute' => 'eduPersonPrincipalName',
            'sourceAttribute' => 'eduPersonAffiliation',
            'targetAttribute' => 'eduPersonScopedAffiliation',
        ];
        $request = [
            'Attributes' => [
                'mail' => ['j.doe@example.edu', 'john@example.org'],
                'eduPersonScopedAffiliation' => ['library-walk-in@example.edu'],
                'eduPersonPrincipalName' => ['jdoe@example.com'],
            ]
        ];
        $result = self::processFilter($config, $request);
        $this->assertEquals($request['Attributes'], $result['Attributes']);
    }


    /**
     * When multiple @ signs in attribute, will use the first one.
     * @return void
     */
    public function testMultiAt()
    {
        $config = [
            'scopeAttribute' => 'eduPersonPrincipalName',
            'sourceAttribute' => 'eduPersonAffiliation',
            'targetAttribute' => 'eduPersonScopedAffiliation',
        ];
        $request = [
            'Attributes' => [
                'eduPersonPrincipalName' => ['john@doe@example.com'],
                'eduPersonAffiliation' => ['member'],
            ]
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertEquals($attributes['eduPersonScopedAffiliation'], ['member@doe@example.com']);
    }


    /**
     * When multiple values in source attribute, should render multiple targets.
     * @return void
     */
    public function testMultivaluedSource()
    {
        $config = [
            'scopeAttribute' => 'eduPersonPrincipalName',
            'sourceAttribute' => 'eduPersonAffiliation',
            'targetAttribute' => 'eduPersonScopedAffiliation',
        ];
        $request = [
            'Attributes' => [
                'eduPersonPrincipalName' => ['jdoe@example.com'],
                'eduPersonAffiliation' => ['member', 'staff', 'faculty'],
            ]
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertEquals(
            $attributes['eduPersonScopedAffiliation'],
            ['member@example.com', 'staff@example.com', 'faculty@example.com']
        );
    }


    /**
     * When the source attribute doesn't have a scope, the entire value is used.
     * @return void
     */
    public function testNoAt()
    {
        $config = [
            'scopeAttribute' => 'schacHomeOrganization',
            'sourceAttribute' => 'eduPersonAffiliation',
            'targetAttribute' => 'eduPersonScopedAffiliation',
        ];
        $request = [
            'Attributes' => [
                'schacHomeOrganization' => ['example.org'],
                'eduPersonAffiliation' => ['student'],
            ]
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertEquals($attributes['eduPersonScopedAffiliation'], ['student@example.org']);
    }


    /**
     * When the target attribute exists and onlyIfEmpty is set
     * @return void
     */
    public function testOnlyIfEmpty()
    {
        $config = [
            'scopeAttribute' => 'schacHomeOrganization',
            'sourceAttribute' => 'eduPersonAffiliation',
            'targetAttribute' => 'eduPersonScopedAffiliation',
            'onlyIfEmpty' => true,
        ];
        $request = [
            'Attributes' => [
                'schacHomeOrganization' => ['example.org'],
                'eduPersonAffiliation' => ['student'],
                'eduPersonScopedAffiliation' => ['staff@example.org', 'member@example.org'],
            ]
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertEquals($attributes['eduPersonScopedAffiliation'], ['staff@example.org', 'member@example.org']);
    }
}
