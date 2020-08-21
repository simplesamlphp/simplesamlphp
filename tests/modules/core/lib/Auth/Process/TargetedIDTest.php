<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\core\Auth\Process;

use Exception;
use PHPUnit\Framework\TestCase;
use SAML2\Constants;
use SAML2\XML\saml\NameID;
use SimpleSAML\Configuration;
use SimpleSAML\Module\core\Auth\Process\TargetedID;
use SimpleSAML\Utils;

/**
 * Test for the core:TargetedID filter.
 */
class TargetedIDTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected $config;

    /** @var \SimpleSAML\Utils\Config */
    protected static $configUtils;

    /**
     * Set up for each test.
     * @return void
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
        $filter = new TargetedID($config, null);
        $filter->setConfigUtils(self::$configUtils);
        $filter->process($request);
        return $request;
    }


    /**
     * Test the most basic functionality
     * @return void
     */
    public function testBasic()
    {
        $config = ['identifyingAttribute' => 'uid'];
        $request = [
            'Attributes' => ['uid' => ['user2@example.org']],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('eduPersonTargetedID', $attributes);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $attributes['eduPersonTargetedID'][0]);
    }


    /**
     * Test with src and dst entityIds.
     * Make sure to overwrite any present eduPersonTargetedId
     * @return void
     */
    public function testWithSrcDst()
    {
        $config = ['identifyingAttribute' => 'uid'];
        $request = [
            'Attributes' => [
                'eduPersonTargetedID' => ['dummy'],
                'uid' => ['user2@example.org'],
            ],
            'Source' => [
                'metadata-set' => 'saml20-idp-hosted',
                'entityid' => 'urn:example:src:id',
            ],
            'Destination' => [
                'metadata-set' => 'saml20-sp-remote',
                'entityid' => 'joe',
            ],
        ];

        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];

        $this->assertArrayHasKey('eduPersonTargetedID', $attributes);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $attributes['eduPersonTargetedID'][0]);
    }


    /**
     * Test with nameId config option set.
     * @return void
     */
    public function testNameIdGeneration()
    {
        $nameid = new NameID();
        $nameid->setFormat(Constants::NAMEID_PERSISTENT);
        $nameid->setNameQualifier('urn:example:src:id');
        $nameid->setSPNameQualifier('joe');
        $nameid->setValue('joe');

        $config = [
            'nameId' => true,
            'identifyingAttribute' => 'eduPersonPrincipalName',
        ];

        $request = array(
            'Attributes' => [
                'eduPersonPrincipalName' => ['joe'],
                'eduPersonTargetedID' => [$nameid->toXML()->ownerDocument->saveXML()],
            ],
            'Source' => [
                'metadata-set' => 'saml20-idp-hosted',
                'entityid' => 'urn:example:src:id',
            ],
            'Destination' => [
                'metadata-set' => 'saml20-sp-remote',
                'entityid' => 'joe',
            ],
        );

        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];

        $this->assertArrayHasKey('eduPersonTargetedID', $attributes);
        $this->assertMatchesRegularExpression(
            '#^<saml:NameID xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" NameQualifier="urn:example:src:id"' .
            ' SPNameQualifier="joe"' .
            ' Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">[0-9a-f]{40}</saml:NameID>$#',
            strval($attributes['eduPersonTargetedID'][0])
        );
    }


    /**
     * Test the outcome to make sure the algorithm remains unchanged
     * @return void
     */
    public function testOutcome()
    {
        $config = ['identifyingAttribute' => 'uid'];
        $request = [
            'Attributes' => ['uid' => ['user2@example.org']],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('eduPersonTargetedID', $attributes);
        $this->assertEquals('c1ae2c2ef77b73f7c47b700e42617117b6ec4adc', $attributes['eduPersonTargetedID'][0]);
    }


    /**
     * Test the outcome when multiple values are given
     * @return void
     */
    public function testOutcomeMultipleValues()
    {
        $config = ['identifyingAttribute' => 'uid'];
        $request = [
            'Attributes' => ['uid' => ['user2@example.org', 'donald@duck.org']],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $this->assertArrayHasKey('eduPersonTargetedID', $attributes);
        $this->assertEquals('c1ae2c2ef77b73f7c47b700e42617117b6ec4adc', $attributes['eduPersonTargetedID'][0]);
    }


    /**
     * Test that Id is the same for subsequent invocations with same input.
     * @return void
     */
    public function testIdIsPersistent()
    {
        $config = ['identifyingAttribute' => 'uid'];
        $request = [
            'Attributes' => [
                'eduPersonTargetedID' => ['dummy'],
                'uid' => ['user2@example.org'],
            ],
            'Source' => [
                'metadata-set' => 'saml20-idp-hosted',
                'entityid' => 'urn:example:src:id',
            ],
            'Destination' => [
                'metadata-set' => 'saml20-sp-remote',
                'entityid' => 'joe',
            ],
        ];

        for ($i = 0; $i < 10; ++$i) {
            $result = self::processFilter($config, $request);
            $attributes = $result['Attributes'];
            $tid = $attributes['eduPersonTargetedID'][0];
            if (isset($prevtid)) {
                $this->assertEquals($prevtid, $tid);
            }
            $prevtid = $tid;
        }
    }


    /**
     * Test that Id is different for two different usernames and two different sp's
     * @return void
     */
    public function testIdIsUnique()
    {
        $config = ['identifyingAttribute' => 'uid'];
        $request = [
            'Attributes' => ['uid' => ['user2@example.org']],
            'Source' => [
                'metadata-set' => 'saml20-idp-hosted',
                'entityid' => 'urn:example:src:id',
            ],
            'Destination' => [
                'metadata-set' => 'saml20-sp-remote',
                'entityid' => 'joe',
            ],
        ];

        $result = self::processFilter($config, $request);
        $tid1 = $result['Attributes']['eduPersonTargetedID'][0];

        $request['Attributes']['uid'][0] = 'user3@example.org';
        $result = self::processFilter($config, $request);
        $tid2 = $result['Attributes']['eduPersonTargetedID'][0];

        $this->assertNotEquals($tid1, $tid2);

        $request['Destination']['entityid'] = 'urn:example.org:another-sp';
        $result = self::processFilter($config, $request);
        $tid3 = $result['Attributes']['eduPersonTargetedID'][0];

        $this->assertNotEquals($tid2, $tid3);
    }


    /**
     * Test no userid set
     * @return void
     */
    public function testNoUserID(): void
    {
        $this->expectException(Exception::class);
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
    public function testAttributeNotExists(): void
    {
        $this->expectException(Exception::class);
        $config = [
            'attributename' => 'uid',
        ];
        $request = [
            'Attributes' => [
                'displayName' => ['Jack Student'],
            ],
        ];
        self::processFilter($config, $request);
    }


    /**
     * Test with configuration error 1
     * @return void
     */
    public function testConfigInvalidAttributeName(): void
    {
        $this->expectException(Exception::class);
        $config = [
            'attributename' => 5,
        ];
        $request = [
            'Attributes' => [
                'displayName' => ['Jack Student'],
            ],
        ];
        self::processFilter($config, $request);
    }


    /**
     * Test with configuration error 2
     * @return void
     */
    public function testConfigInvalidNameId(): void
    {
        $this->expectException(Exception::class);
        $config = [
            'nameId' => 'persistent',
        ];
        $request = [
            'Attributes' => [
                'displayName' => ['Jack Student'],
            ],
        ];
        self::processFilter($config, $request);
    }
}
