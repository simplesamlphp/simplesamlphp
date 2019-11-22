<?php

namespace SimpleSAML\Test\Module\core\Auth\Process;

use PHPUnit\Framework\TestCase;

/**
 * Test for the core:TargetedID filter.
 */
class TargetedIDTest extends TestCase
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
        $filter = new \SimpleSAML\Module\core\Auth\Process\TargetedID($config, null);
        $filter->process($request);
        return $request;
    }


//    /**
//     * Test the most basic functionality
//     * @return void
//     */
//    public function testBasic()
//    {
//        $config = [];
//        $request = [
//            'Attributes' => [],
//            'UserID' => 'user2@example.org',
//        ];
//        $result = self::processFilter($config, $request);
//        $attributes = $result['Attributes'];
//        $this->assertArrayHasKey('eduPersonTargetedID', $attributes);
//        $this->assertRegExp('/^[0-9a-f]{40}$/', $attributes['eduPersonTargetedID'][0]);
//    }
//
//
//    /**
//     * Test with src and dst entityIds.
//     * Make sure to overwrite any present eduPersonTargetedId
//     * @return void
//     */
//    public function testWithSrcDst()
//    {
//        $config = [];
//        $request = [
//            'Attributes' => [
//                'eduPersonTargetedID' => 'dummy',
//            ],
//            'UserID' => 'user2@example.org',
//            'Source' => [
//                'metadata-set' => 'saml20-idp-hosted',
//                'entityid' => 'urn:example:src:id',
//            ],
//            'Destination' => [
//                'metadata-set' => 'saml20-sp-remote',
//                'entityid' => 'joe',
//            ],
//        ];
//        $result = self::processFilter($config, $request);
//        $attributes = $result['Attributes'];
//        $this->assertArrayHasKey('eduPersonTargetedID', $attributes);
//        $this->assertRegExp('/^[0-9a-f]{40}$/', $attributes['eduPersonTargetedID'][0]);
//    }
//
//
//    /**
//     * Test with nameId config option set.
//     * @return void
//     */
//    public function testNameIdGeneration()
//    {
//        $config = [
//            'nameId' => true,
//        ];
//        $request = array(
//            'Attributes' => [],
//            'UserID' => 'user2@example.org',
//            'Source' => [
//                'metadata-set' => 'saml20-idp-hosted',
//                'entityid' => 'urn:example:src:id',
//            ],
//            'Destination' => [
//                'metadata-set' => 'saml20-sp-remote',
//                'entityid' => 'joe',
//            ],
//        );
//        $result = self::processFilter($config, $request);
//        $attributes = $result['Attributes'];
//        $this->assertArrayHasKey('eduPersonTargetedID', $attributes);
//        $this->assertRegExp(
//            '#^<saml:NameID xmlns:saml="urn:oasis:names:tc:SAML:2\.0:assertion" NameQualifier="urn:example:src:id"' .
//            ' SPNameQualifier="joe"' .
//            ' Format="urn:oasis:names:tc:SAML:2\.0:nameid-format:persistent">[0-9a-f]{40}</saml:NameID>$#',
//            $attributes['eduPersonTargetedID'][0]
//        );
//    }
//
//
//    /**
//     * Test that Id is the same for subsequent invocations with same input.
//     * @return void
//     */
//    public function testIdIsPersistent()
//    {
//        $config = [];
//        $request = [
//            'Attributes' => [
//                'eduPersonTargetedID' => 'dummy',
//            ],
//            'UserID' => 'user2@example.org',
//            'Source' => [
//                'metadata-set' => 'saml20-idp-hosted',
//                'entityid' => 'urn:example:src:id',
//            ],
//            'Destination' => [
//                'metadata-set' => 'saml20-sp-remote',
//                'entityid' => 'joe',
//            ],
//        ];
//        for ($i = 0; $i < 10; ++$i) {
//            $result = self::processFilter($config, $request);
//            $attributes = $result['Attributes'];
//            $tid = $attributes['eduPersonTargetedID'][0];
//            if (isset($prevtid)) {
//                $this->assertEquals($prevtid, $tid);
//                $prevtid = $tid;
//            }
//        }
//    }
//
//
//    /**
//     * Test that Id is different for two different usernames and two different sp's
//     * @return void
//     */
//    public function testIdIsUnique()
//    {
//        $config = [];
//        $request = [
//            'Attributes' => [],
//            'UserID' => 'user2@example.org',
//            'Source' => [
//                'metadata-set' => 'saml20-idp-hosted',
//                'entityid' => 'urn:example:src:id',
//            ],
//            'Destination' => [
//                'metadata-set' => 'saml20-sp-remote',
//                'entityid' => 'joe',
//            ],
//        ];
//        $result = self::processFilter($config, $request);
//        $tid1 = $result['Attributes']['eduPersonTargetedID'][0];
//
//        $request['UserID'] = 'user3@example.org';
//        $result = self::processFilter($config, $request);
//        $tid2 = $result['Attributes']['eduPersonTargetedID'][0];
//
//        $this->assertNotEquals($tid1, $tid2);
//
//        $request['Destination']['entityid'] = 'urn:example.org:another-sp';
//        $result = self::processFilter($config, $request);
//        $tid3 = $result['Attributes']['eduPersonTargetedID'][0];
//
//        $this->assertNotEquals($tid2, $tid3);
//    }


    /**
     * Test no userid set
     * @return void
     */
    public function testNoUserID()
    {
        $this->expectException(\Exception::class);
        $config = [];
        $request = [
            'Attributes' => [],
        ];
        self::processFilter($config, $request);
    }


    /**
     * Test with specified attribute not set
     * @return void
     */
    public function testAttributeNotExists()
    {
        $this->expectException(\Exception::class);
        $config = [
            'attributename' => 'uid',
        ];
        $request = [
            'Attributes' => [
                'displayName' => 'Jack Student',
            ],
        ];
        self::processFilter($config, $request);
    }


    /**
     * Test with configuration error 1
     * @return void
     */
    public function testConfigInvalidAttributeName()
    {
        $this->expectException(\Exception::class);
        $config = [
            'attributename' => 5,
        ];
        $request = [
            'Attributes' => [
                'displayName' => 'Jack Student',
            ],
        ];
        self::processFilter($config, $request);
    }


    /**
     * Test with configuration error 2
     * @return void
     */
    public function testConfigInvalidNameId()
    {
        $this->expectException(\Exception::class);
        $config = [
            'nameId' => 'persistent',
        ];
        $request = [
            'Attributes' => [
                'displayName' => 'Jack Student',
            ],
        ];
        self::processFilter($config, $request);
    }
}
