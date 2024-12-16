<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml\Auth\Process;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\saml\Auth\Process\AttributeNameID;
use SimpleSAML\SAML2\Constants as C;

/**
 * Test for the AttributeNameID filter.
 *
 * @package SimpleSAMLphp
 */
#[CoversClass(AttributeNameID::class)]
class AttributeNameIDTest extends TestCase
{
    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param array $config  The filter configuration.
     * @param array $request  The request state.
     * @return array  The state array after processing.
     */
    private function processFilter(array $config, array $request): array
    {
        $filter = new AttributeNameID($config, null);
        $filter->process($request);
        return $request;
    }


    /**
     * Test minimal configuration.
     */
    public function testMinimalConfig(): void
    {
        $config = [];
        $spId = 'urn:x-simplesamlphp:sp';
        $idpId = 'urn:x-simplesamlphp:idp';
        $expectedEmail = 'foo@there';

        $config = [
            'class' => 'saml:AttributeNameID',
            'identifyingAttributes' => ['mail','eduPersonPrincipalName'],
            'Format' => C::NAMEID_PERSISTENT,
        ];

        $request = [
            'Source'     => [
                'entityid' => $spId,
            ],
            'Destination' => [
                'entityid' => $idpId,
            ],
            'Attributes' => [
                'eduPersonPrincipalName' => ['foo@there'],
            ],
        ];
        $result = $this->processFilter($config, $request);
        $this->assertNotNull($result['saml:NameID']);
        $resultNameId = $result['saml:NameID'][C::NAMEID_PERSISTENT];
        $this->assertNotNull($resultNameId);
        $this->assertEquals($expectedEmail, $resultNameId->getValue());
    }


    /**
     * Test third element in chain
     */
    public function testSuccessInThirdElement(): void
    {
        $config = [];
        $spId = 'urn:x-simplesamlphp:sp';
        $idpId = 'urn:x-simplesamlphp:idp';
        $expectedEmail = 'foo@there';

        $config = [
            'class' => 'saml:AttributeNameID',
            'identifyingAttributes' => ['mail','somethingelse','eduPersonPrincipalName'],
            'Format' => C::NAMEID_PERSISTENT,
        ];

        $request = [
            'Source'     => [
                'entityid' => $spId,
            ],
            'Destination' => [
                'entityid' => $idpId,
            ],
            'Attributes' => [
                'eduPersonPrincipalName' => ['foo@there'],
            ],
        ];
        $result = $this->processFilter($config, $request);
        $this->assertNotNull($result['saml:NameID']);
        $resultNameId = $result['saml:NameID'][C::NAMEID_PERSISTENT];
        $this->assertNotNull($resultNameId);
        $this->assertEquals($expectedEmail, $resultNameId->getValue());
    }


    /**
     * Test attributes in list not found.
     */
    public function testNotFound(): void
    {
        $config = [];
        $spId = 'urn:x-simplesamlphp:sp';
        $idpId = 'urn:x-simplesamlphp:idp';
        $expectedEmail = 'foo@there';

        $config = [
            'class' => 'saml:AttributeNameID',
            'identifyingAttributes' => ['mail','eduPersonPrincipalName'],
            'Format' => C::NAMEID_PERSISTENT,
        ];

        $request = [
            'Source'     => [
                'entityid' => $spId,
            ],
            'Destination' => [
                'entityid' => $idpId,
            ],
            'Attributes' => [
                'magic' => ['foo@there'],
            ],
        ];
        $result = $this->processFilter($config, $request);
        $this->assertNull($result['saml:NameID']);
    }
}
