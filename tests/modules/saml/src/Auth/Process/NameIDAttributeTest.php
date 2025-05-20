<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml\Auth\Process;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Error;
use SimpleSAML\Module\saml\Auth\Process\NameIDAttribute;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\XML\saml\NameID;

/**
 * Test for the saml:NameIDAttribute filter.
 *
 * @package SimpleSAMLphp
 */
#[CoversClass(NameIDAttribute::class)]
class NameIDAttributeTest extends TestCase
{
    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param array $config  The filter configuration.
     * @param array $request  The request state.
     * @return array  The state array after processing.
     * @throws \SimpleSAML\Error\Exception
     */
    private function processFilter(array $config, array $request): array
    {
        $filter = new NameIDAttribute($config, null);
        $filter->process($request);
        return $request;
    }


    /**
     * Test minimal configuration.
     * @throws \SimpleSAML\Error\Exception
     */
    public function testMinimalConfig(): void
    {
        $config = [];
        $spId = 'urn:x-simplesamlphp:sp';
        $idpId = 'urn:x-simplesamlphp:idp';


        $nameId = new NameID(
            value: 'eugene@oombaas',
            Format: C::NAMEID_PERSISTENT,
            NameQualifier: $idpId,
            SPNameQualifier: $spId,
        );

        $request = [
            'Source'     => [
                'entityid' => $spId,
            ],
            'Destination' => [
                'entityid' => $idpId,
            ],
            'saml:sp:NameID' => $nameId,
        ];
        $result = $this->processFilter($config, $request);
        $this->assertEquals("{$idpId}!{$spId}!{$nameId->getContent()}", $result['Attributes']['nameid'][0]);
    }


    /**
     * Test custom attribute name.
     * @throws \SimpleSAML\Error\Exception
     */
    public function testCustomAttributeName(): void
    {
        $attributeName = 'eugeneNameIDAttribute';
        $config = ['attribute' => $attributeName];
        $spId = 'urn:x-simplesamlphp:sp';
        $idpId = 'urn:x-simplesamlphp:idp';

        $nameId = new NameID(
            value: 'eugene@oombaas',
            Format: C::NAMEID_PERSISTENT,
            NameQualifier: $idpId,
            SPNameQualifier: $spId,
        );

        $request = [
            'Source'     => [
                'entityid' => $spId,
            ],
            'Destination' => [
                'entityid' => $idpId,
            ],
            'saml:sp:NameID' => $nameId,
        ];
        $result = $this->processFilter($config, $request);
        $this->assertTrue(isset($result['Attributes'][$attributeName]));
        $this->assertEquals("{$idpId}!{$spId}!{$nameId->getContent()}", $result['Attributes'][$attributeName][0]);
    }


    /**
     * Test custom format.
     * @throws \SimpleSAML\Error\Exception
     */
    public function testFormat(): void
    {
        $config = ['format' => '%V!%%'];
        $spId = 'urn:x-simplesamlphp:sp';
        $idpId = 'urn:x-simplesamlphp:idp';

        $nameId = new NameID(
            value: 'eugene@oombaas',
            Format: C::NAMEID_PERSISTENT,
            NameQualifier: $idpId,
            SPNameQualifier: $spId,
        );

        $request = [
            'Source'     => [
                'entityid' => $spId,
            ],
            'Destination' => [
                'entityid' => $idpId,
            ],
            'saml:sp:NameID' => $nameId,
        ];
        $result = $this->processFilter($config, $request);
        $this->assertEquals("{$nameId->getContent()}!%", $result['Attributes']['nameid'][0]);
    }


    /**
     * Test invalid format throws an exception.
     */
    public function testInvalidFormatThrowsException(): void
    {
        $config = ['format' => '%X'];
        $spId = 'urn:x-simplesamlphp:sp';
        $idpId = 'urn:x-simplesamlphp:idp';

        $nameId = new NameID('eugene@oombaas');

        $request = [
            'Source'     => [
                'entityid' => $spId,
            ],
            'Destination' => [
                'entityid' => $idpId,
            ],
            'saml:sp:NameID' => $nameId,
        ];

        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage('NameIDAttribute: Invalid replacement: "%X"');

        $this->processFilter($config, $request);
    }


    /**
     * Test invalid request silently continues, leaving the state untouched
     * @throws \SimpleSAML\Error\Exception
     */
    public function testInvalidRequestLeavesStateUntouched(): void
    {
        $config = ['format' => '%V!%F'];
        $spId = 'urn:x-simplesamlphp:sp';
        $idpId = 'urn:x-simplesamlphp:idp';

        $request = [
            'Source'     => [
                'entityid' => $spId,
            ],
            'Destination' => [
                'entityid' => $idpId,
            ],
        ];

        $pre = $request;
        $this->processFilter($config, $request);
        $this->assertEquals($pre, $request);
    }


    /**
     * Test custom attribute name with format.
     * @throws \SimpleSAML\Error\Exception
     */
    public function testCustomAttributeNameAndFormat(): void
    {
        $attributeName = 'eugeneNameIDAttribute';
        $config = ['attribute' => $attributeName, 'format' => '%V'];
        $spId = 'urn:x-simplesamlphp:sp';
        $idpId = 'urn:x-simplesamlphp:idp';

        $nameId = new NameID(
            value: 'eugene@oombaas',
            Format: C::NAMEID_PERSISTENT,
            NameQualifier: $idpId,
            SPNameQualifier: $spId,
        );

        $request = [
            'Source'     => [
                'entityid' => $spId,
            ],
            'Destination' => [
                'entityid' => $idpId,
            ],
            'saml:sp:NameID' => $nameId,
        ];
        $result = $this->processFilter($config, $request);
        $this->assertTrue(isset($result['Attributes'][$attributeName]));
        $this->assertEquals("{$nameId->getContent()}", $result['Attributes'][$attributeName][0]);
    }


    /**
     * Test overriding NameID Format/NameQualifier/SPNameQualifier with defaults.
     * @throws \SimpleSAML\Error\Exception
     */
    public function testOverrideNameID(): void
    {
        $spId = 'urn:x-simplesamlphp:sp';
        $idpId = 'urn:x-simplesamlphp:idp';

        $nameId = new NameID('eugene@oombaas');

        $request = [
            'Source'     => [
                'entityid' => $spId,
            ],
            'Destination' => [
                'entityid' => $idpId,
            ],
            'saml:sp:NameID' => $nameId,
        ];
        $result = $this->processFilter([], $request);
        $nameId = $result['saml:sp:NameID'];

        $this->assertEquals("{$nameId->getFormat()}", C::NAMEID_UNSPECIFIED);
        $this->assertEquals("{$nameId->getNameQualifier()}", $spId);
        $this->assertEquals("{$nameId->getSPNameQualifier()}", $idpId);
    }
}
