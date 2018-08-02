<?php

use PHPUnit\Framework\TestCase;

/**
 * Test for the core:AttributeLimit filter.
 */
class Test_Core_Auth_Process_AttributeLimitTest extends TestCase
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
        $filter = new \SimpleSAML\Module\core\Auth\Process\AttributeLimit($config, NULL);
        $filter->process($request);
        return $request;
    }

    /**
     * Test reading IdP Attributes.
     */
    public function testIdPAttrs()
    {
        $config = array(
            'cn', 'mail'
        );

        $request = array(
            'Attributes' => array(
                 'eduPersonTargetedID' => array('eptid@example.org'),
                 'eduPersonAffiliation' => array('member'),
                 'cn' => array('user name'),
                 'mail' => array('user@example.org'),
             ),
            'Destination' => array(
             ),
            'Source' => array(
                'attributes' => array('cn','mail'),
             ),
        );

        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('cn', $attributes);
        $this->assertArrayHasKey('mail', $attributes);
        $this->assertArrayNotHasKey('eduPersonTargetedID', $attributes);
        $this->assertArrayNotHasKey('eduPersonAffiliation', $attributes);
        $this->assertCount(2, $attributes);

        $config = array(
            'cn',
            'default' => TRUE,
        );

        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('cn', $attributes);
        $this->assertArrayHasKey('mail', $attributes);
        $this->assertArrayNotHasKey('eduPersonTargetedID', $attributes);
        $this->assertArrayNotHasKey('eduPersonAffiliation', $attributes);
        $this->assertCount(2, $attributes);


    }

    /**
     * Tests when no attributes are in metadata.
     */
    public function testNULLMetadataAttrs()
    {
        $config = array(
            'cn', 'mail'
        );

        $request = array(
            'Attributes' => array(
                 'eduPersonTargetedID' => array('eptid@example.org'),
                 'eduPersonAffiliation' => array('member'),
                 'cn' => array('user name'),
                 'mail' => array('user@example.org'),
             ),
            'Destination' => array(
             ),
            'Source' => array(
             ),
        );

        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('cn', $attributes);
        $this->assertArrayHasKey('mail', $attributes);
        $this->assertArrayNotHasKey('eduPersonTargetedID', $attributes);
        $this->assertArrayNotHasKey('eduPersonAffiliation', $attributes);
        $this->assertCount(2, $attributes);

        $config = array(
            'cn',
            'default' => TRUE,
        );

        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('cn', $attributes);
        $this->assertArrayNotHasKey('mail', $attributes);
        $this->assertArrayNotHasKey('eduPersonTargetedID', $attributes);
        $this->assertArrayNotHasKey('eduPersonAffiliation', $attributes);
        $this->assertCount(1, $attributes);

        $config = array(
        );

        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertCount(4, $attributes);
        $this->assertArrayHasKey('eduPersonTargetedID', $attributes);
        $this->assertArrayHasKey('eduPersonAffiliation', $attributes);
        $this->assertArrayHasKey('cn', $attributes);
        $this->assertArrayHasKey('mail', $attributes);
    }

    /**
     * setUpBeforeClass a request that will be used for the following tests.
     * note the above tests don't use self::$request for processFilter input.
     */
    protected static $request;

    public static function setUpBeforeClass()
    {
        self::$request = array(
            'Attributes' => array(
                 'eduPersonTargetedID' => array('eptid@example.org'),
                 'eduPersonAffiliation' => array('member'),
                 'cn' => array('common name'),
                 'mail' => array('user@example.org'),
             ),
            'Destination' => array(
		'attributes' => array('cn','mail'),
             ),
            'Source' => array(
             ),
        );
    }

    /**
     * Test the most basic functionality.
     */
    public function testBasic()
    {
        $config = array(
            'cn', 'mail'
        );

        $result = self::processFilter($config, self::$request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('cn', $attributes);
        $this->assertArrayHasKey('mail', $attributes);
        $this->assertCount(2, $attributes);
    }

    /**
     * Test defaults with metadata available.
     */
    public function testDefaultWithMetadata()
    {
        $config = array(
            'default' => TRUE,
        );

        $result = self::processFilter($config, self::$request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('cn', $attributes);
        $this->assertArrayHasKey('mail', $attributes);
        $this->assertCount(2, $attributes);
    }

    /**
     * Test defaults with attributes and metadata
     */
    public function testDefaultWithAttrs()
    {
        $config = array(
            'default' => TRUE,
            'eduPersonTargetedID', 'eduPersonAffiliation',
        );

        $result = self::processFilter($config, self::$request);
        $attributes = $result['Attributes'];
        $this->assertCount(2, $attributes);
        $this->assertArrayHasKey('cn', $attributes);
        $this->assertArrayHasKey('mail', $attributes);
        $this->assertArrayNotHasKey('eduPersonTargetedID', $attributes);
        $this->assertArrayNotHasKey('eduPersonAffiliation', $attributes);
    }

    /**
     * Test for exception with illegal config.
     *
     * @expectedException Exception
     */
    public function testInvalidConfig()
    {
        $config = array(
            'invalidArg' => TRUE,
        );

        $result = self::processFilter($config, self::$request);
    }

    /**
     * Test for invalid attribute name
     *
     * @expectedException Exception
     */
    public function testInvalidAttributeName()
    {
        $config = array(
		null
        );

        $result = self::processFilter($config, self::$request);
    }


    /**
     * Test for attribute value matching
     */
    public function testMatchAttributeValues()
    {
        $config = array(
		'eduPersonAffiliation' => array('member')
        );

        $result = self::processFilter($config, self::$request);
        $attributes = $result['Attributes'];
        $this->assertCount(1, $attributes);
        $this->assertArrayHasKey('eduPersonAffiliation', $attributes);
        $this->assertEquals($attributes['eduPersonAffiliation'], array('member'));

        $config = array(
		'eduPersonAffiliation' => array('member','staff')
        );

        $result = self::processFilter($config, self::$request);
        $attributes = $result['Attributes'];
        $this->assertCount(1, $attributes);
        $this->assertArrayHasKey('eduPersonAffiliation', $attributes);
        $this->assertEquals($attributes['eduPersonAffiliation'], array('member'));

        $config = array(
		'eduPersonAffiliation' => array('student')
        );
        $result = self::processFilter($config, self::$request);
        $attributes = $result['Attributes'];
        $this->assertCount(0, $attributes);

        $config = array(
		'eduPersonAffiliation' => array('student','staff')
        );
        $result = self::processFilter($config, self::$request);
        $attributes = $result['Attributes'];
        $this->assertCount(0, $attributes);
    }

    public function testBadOptionsNotTreatedAsValidValues() {

        // Ensure really misconfigured ignoreCase and regex options are not interpretted as valid valus
        $config = array(
            'eduPersonAffiliation' => array('ignoreCase' => 'member', 'nomatch'),
            'mail' => array('regex' => 'user@example.org', 'nomatch')
        );
        $result = self::processFilter($config, self::$request);
        $attributes = $result['Attributes'];
        $this->assertCount(0, $attributes);
    }

    /**
     * Verify that the true value for ignoreCase doesn't get converted into a string ('1') by
     * php and matched against an attribute value of '1'
     */
    public function testThatIgnoreCaseOptionNotMatchBooleanAsStringValue() {
        $config = array(
            'someAttribute' => array('ignoreCase' => true, 'someValue')
        );

        $request = array(
            'Attributes' => array(
                'someAttribute' => array('1'), //boolean true as a string

            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertCount(0, $attributes);
    }

    /**
     * Test for attribute value matching ignore case
     */
    public function testMatchAttributeValuesIgnoreCase()
    {
        $config = array(
            'eduPersonAffiliation' => array('ignoreCase' => true, 'meMber')
        );

        $result = self::processFilter($config, self::$request);
        $attributes = $result['Attributes'];
        $this->assertCount(1, $attributes);
        $this->assertArrayHasKey('eduPersonAffiliation', $attributes);
        $this->assertEquals($attributes['eduPersonAffiliation'], array('member'));

        $config = array(
            'eduPersonAffiliation' => array('ignoreCase' => true, 'membeR','sTaff')
        );

        $result = self::processFilter($config, self::$request);
        $attributes = $result['Attributes'];
        $this->assertCount(1, $attributes);
        $this->assertArrayHasKey('eduPersonAffiliation', $attributes);
        $this->assertEquals($attributes['eduPersonAffiliation'], array('member'));

        $config = array(
            'eduPersonAffiliation' => array('ignoreCase' => true, 'Student')
        );
        $result = self::processFilter($config, self::$request);
        $attributes = $result['Attributes'];
        $this->assertCount(0, $attributes);

        $config = array(
            'eduPersonAffiliation' => array('ignoreCase' => true, 'studeNt','sTaff')
        );
        $result = self::processFilter($config, self::$request);
        $attributes = $result['Attributes'];
        $this->assertCount(0, $attributes);
    }

    /**
     * Test for attribute value matching
     */
    public function testMatchAttributeValuesRegex()
    {
        // SSP Logger requires a configuration to be set.
        \SimpleSAML\Configuration::loadFromArray(array(), '[ARRAY]', 'simplesaml');
        $state = self::$request;
        $state['Attributes']['eduPersonEntitlement'] = array(
            'urn:mace:example.terena.org:tcs:personal-user',
            'urn:x-surfnet:surfdomeinen.nl:role:dnsadmin',
            'urn:x-surfnet:surf.nl:surfdrive:quota:100',
            '1' //boolean true as a string
        );

        $config = array(
            'eduPersonEntitlement' => array(
                'regex' => true,
                '/^urn:x-surfnet:surf/'
            )
        );

        $result = self::processFilter($config, $state);
        $attributes = $result['Attributes'];
        $this->assertCount(1, $attributes);
        $this->assertArrayHasKey('eduPersonEntitlement', $attributes);
        $this->assertEquals(
            array('urn:x-surfnet:surfdomeinen.nl:role:dnsadmin', 'urn:x-surfnet:surf.nl:surfdrive:quota:100'),
            $attributes['eduPersonEntitlement']
        );

        // Matching multiple lines shouldn't duplicate the attribute
        $config = array(
            'eduPersonEntitlement' => array(
                'regex' => true,
                '/urn:x-surfnet:surf/',
                '/urn:x-surfnet/'

            )
        );

        $result = self::processFilter($config, $state);
        $attributes = $result['Attributes'];
        $this->assertCount(1, $attributes);
        $this->assertArrayHasKey('eduPersonEntitlement', $attributes);
        $this->assertEquals(
            array('urn:x-surfnet:surfdomeinen.nl:role:dnsadmin', 'urn:x-surfnet:surf.nl:surfdrive:quota:100'),
            $attributes['eduPersonEntitlement']
        );

        // Invalid and no-match regex expressions should not stop a valid regex from matching
        $config = array(
            'eduPersonEntitlement' => array(
                'regex' => true,
                '/urn:mace:example.terena.org:tcs:no-match/',
                '$invalidRegex[',
                '/^URN:x-surf.*SURF.*n$/i'
            )
        );

        $result = self::processFilter($config, $state);
        $attributes = $result['Attributes'];
        $this->assertCount(1, $attributes);
        $this->assertArrayHasKey('eduPersonEntitlement', $attributes);
        $this->assertEquals(
            array('urn:x-surfnet:surfdomeinen.nl:role:dnsadmin'),
            $attributes['eduPersonEntitlement']
        );

        // No matches should remove attribute
        $config = array(
            'eduPersonEntitlement' => array(
                'regex' => true,
                '/urn:x-no-match/'
            )
        );
        $result = self::processFilter($config, $state);
        $attributes = $result['Attributes'];
        $this->assertCount(0, $attributes);

        // A regex that matches an input value multiple times should work.
        $config = array(
            'eduPersonEntitlement' => array(
                'regex' => true,
                '/surf/'
            )
        );
        $result = self::processFilter($config, $state);
        $attributes = $result['Attributes'];
        $this->assertCount(1, $attributes);
        $this->assertArrayHasKey('eduPersonEntitlement', $attributes);
        $this->assertEquals(
            array('urn:x-surfnet:surfdomeinen.nl:role:dnsadmin', 'urn:x-surfnet:surf.nl:surfdrive:quota:100'),
            $attributes['eduPersonEntitlement']
        );
    }

    /**
     * Test for allowed attributes not an array.
     *
     * This test is very unlikely and would require malformed metadata processing.
     * Cannot be generated via config options.
     *
     * @expectedException Exception
     */
    public function testMatchAttributeValuesNotArray()
    {
        $config = array(
        );

        $request = array(
            'Attributes' => array(
                 'eduPersonTargetedID' => array('eptid@example.org'),
                 'eduPersonAffiliation' => array('member'),
                 'cn' => array('user name'),
                 'mail' => array('user@example.org'),
                 'discardme' => array('somethingiswrong'),
             ),
            'Destination' => array(
                'attributes' => array('eduPersonAffiliation' => 'student'),
             ),
            'Source' => array(
             ),
        );


        $result = self::processFilter($config, $request);
    }

    /**
     * Test attributes not intersecting
     */
    public function testNoIntersection()
    {
        $config = array(
            'default' => TRUE,
        );

        $request = array(
            'Attributes' => array(
                 'eduPersonTargetedID' => array('eptid@example.org'),
                 'eduPersonAffiliation' => array('member'),
                 'cn' => array('user name'),
                 'mail' => array('user@example.org'),
                 'discardme' => array('somethingiswrong'),
             ),
            'Destination' => array(
                'attributes' => array('urn:oid:1.2.840.113549.1.9.1'),
             ),
            'Source' => array(
             ),
        );

        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertCount(0, $attributes);
        $this->assertEmpty($attributes);
    }

    /**
     * Test AttributeConsumingService in SP metadata.
     */

     protected static $attributeConsumingService = array (
            0 =>
            array (
              'attributes' =>
              array (
                0 => 'eduPersonPrincipalName',
                1 => 'mail',
                2 => 'displayName',
              ),
              'attributes.required' =>
              array (
                0 => 'eduPersonPrincipalName',
              ),
            ),
            1 =>
            array (
              'attributes' =>
              array (
                0 => 'cn',
                1 => 'testN1',
                2 => 'testN2',
              ),
              'attributes.required' =>
              array (
                0 => 'cn',
                1 => 'testN1',
              ),
              'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic',
            ),
        );


    /**
     * Test when AttributeConsumingServiceIndex matches AttributeConsumingService in SP metadata.
     */

    public function testAttributeConsumingServiceIndex()
    {
        // default config
        $config = array(
        );

        //prepare request with AttributeConsumingServiceIndex = 1
        $request = array(
            'Attributes' => array(
                 'eduPersonPrincipalName' => array('eptid@example.org'),
                 'eduPersonAffiliation' => array('member'),
                 'cn' => array('user name'),
                 'mail' => array('user@example.org'),
                 'testN1' => array('testV1'),
                 'testN2' => array('testV2'),
                 'testN3' => array('testV3'),
             ),
            'Destination' => array(
                'AttributeConsumingService' => self::$attributeConsumingService,
                'AttributeConsumingService.default' => 0,
             ),
            'Source' => array(
             ),
             'saml:AttributeConsumingServiceIndex' => 1,
        );

        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('cn', $attributes);
        $this->assertArrayHasKey('testN1', $attributes);
        $this->assertArrayHasKey('testN2', $attributes);
        $this->assertCount(3, $attributes);
    }

    /**
     * Test default AttributeConsumingServiceIndex in SP metadata.
     */

    public function testAttributeConsumingServiceDefault()
    {
        // default config
        $config = array(
        );

        //prepare request without AttributeConsumingServiceIndex
        $request = array(
            'Attributes' => array(
                 'eduPersonPrincipalName' => array('eptid@example.org'),
                 'eduPersonAffiliation' => array('member'),
                 'cn' => array('user name'),
                 'mail' => array('user@example.org'),
                 'testN1' => array('testV1'),
                 'testN2' => array('testV2'),
                 'testN3' => array('testV3'),
             ),
            'Destination' => array(
                'AttributeConsumingService' => self::$attributeConsumingService,
                'AttributeConsumingService.default' => 0,
             ),
            'Source' => array(
             ),
        );

        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('eduPersonPrincipalName', $attributes);
        $this->assertArrayHasKey('mail', $attributes);
        $this->assertCount(2, $attributes);
    }

    /**
     * Test default AttributeConsumingServiceIndex in SP metadata
     * when saml:AttributeConsumingServiceIndex is null
     */

    public function testAttributeConsumingServiceNULLDefault()
    {
        // default config
        $config = array(
        );

        //prepare request without AttributeConsumingServiceIndex
        $request = array(
            'Attributes' => array(
                 'eduPersonPrincipalName' => array('eptid@example.org'),
                 'eduPersonAffiliation' => array('member'),
                 'cn' => array('user name'),
                 'mail' => array('user@example.org'),
                 'testN1' => array('testV1'),
                 'testN2' => array('testV2'),
                 'testN3' => array('testV3'),
             ),
            'Destination' => array(
                'AttributeConsumingService' => self::$attributeConsumingService,
                'AttributeConsumingService.default' => 0,
             ),
            'Source' => array(
             ),
             'saml:AttributeConsumingServiceIndex' => null,
        );

        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('eduPersonPrincipalName', $attributes);
        $this->assertArrayHasKey('mail', $attributes);
        $this->assertCount(2, $attributes);
    }

    /**
     * Test default AttributeConsumingServiceIndex not in SP metadata.
     *
     * @expectedException Exception 
     */

    public function testAttributeConsumingServiceWrongDefault()
    {
        // default config
        $config = array(
        );

        //prepare request without AttributeConsumingServiceIndex
        $request = array(
            'Attributes' => array(
                 'eduPersonPrincipalName' => array('eptid@example.org'),
                 'eduPersonAffiliation' => array('member'),
                 'cn' => array('user name'),
                 'mail' => array('user@example.org'),
                 'testN1' => array('testV1'),
                 'testN2' => array('testV2'),
                 'testN3' => array('testV3'),
             ),
            'Destination' => array(
                'AttributeConsumingService' => self::$attributeConsumingService,
                'AttributeConsumingService.default' => 2,
             ),
            'Source' => array(
             ),
        );

        $result = self::processFilter($config, $request);
    }

    /**
     * Test AttributeConsumingServiceIndex not in SP metadata.
     *
     * @expectedException Exception 
     */

    public function testAttributeConsumingServiceWrongIndex()
    {
        // default config
        $config = array(
        );

        //prepare request without AttributeConsumingServiceIndex
        $request = array(
            'Attributes' => array(
                 'eduPersonPrincipalName' => array('eptid@example.org'),
                 'eduPersonAffiliation' => array('member'),
                 'cn' => array('user name'),
                 'mail' => array('user@example.org'),
                 'testN1' => array('testV1'),
                 'testN2' => array('testV2'),
                 'testN3' => array('testV3'),
             ),
            'Destination' => array(
                'AttributeConsumingService' => self::$attributeConsumingService,
                'AttributeConsumingService.default' => 1,
             ),
            'Source' => array(
             ),
             'saml:AttributeConsumingServiceIndex' => 3,
        );

        $result = self::processFilter($config, $request);
    }
}
