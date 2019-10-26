<?php

/**
 * Test for the saml:NameIDAttribute filter.
 *
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\Test\Module\saml\Auth\Process;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\saml\Auth\Process\NameIDAttribute;
use SAML2\XML\saml\NameID;
use SAML2\Constants;

class NameIDAttributeTest extends TestCase
{
    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param array $config  The filter configuration.
     * @param array $request  The request state.
     * @return array  The state array after processing.
     */
    private function processFilter(array $config, array $request)
    {
        $filter = new NameIDAttribute($config, null);
        $filter->process($request);
        return $request;
    }


    /**
     * Test minimal configuration.
     * @return void
     */
    public function testMinimalConfig()
    {
        $config = [];
        $spId = 'eugeneSP';
        $idpId = 'eugeneIdP';


        $nameId = new NameID();
        $nameId->setValue('eugene@oombaas');
        $nameId->setFormat(Constants::NAMEID_PERSISTENT);
        $nameId->setNameQualifier($idpId);
        $nameId->setSPNameQualifier($spId);

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
        $this->assertEquals("{$spId}!{$idpId}!{$nameId->getValue()}", $result['Attributes']['nameid'][0]);
    }


    /**
     * Test custom attribute name.
     * @return void
     */
    public function testCustomAttributeName()
    {
        $attributeName = 'eugeneNameIDAttribute';
        $config = ['attribute' => $attributeName];
        $spId = 'eugeneSP';
        $idpId = 'eugeneIdP';

        $nameId = new NameID();
        $nameId->setValue('eugene@oombaas');
        $nameId->setFormat(Constants::NAMEID_PERSISTENT);
        $nameId->setNameQualifier($idpId);
        $nameId->setSPNameQualifier($spId);

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
        $this->assertEquals("{$spId}!{$idpId}!{$nameId->getValue()}", $result['Attributes'][$attributeName][0]);
    }


    /**
     * Test custom format.
     * @return void
     */
    public function testFormat()
    {
        $config = ['format' => '%V'];
        $spId = 'eugeneSP';
        $idpId = 'eugeneIdP';

        $nameId = new NameID();
        $nameId->setValue('eugene@oombaas');
        $nameId->setFormat(Constants::NAMEID_PERSISTENT);
        $nameId->setNameQualifier($idpId);
        $nameId->setSPNameQualifier($spId);

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
        $this->assertEquals("{$nameId->getValue()}", $result['Attributes']['nameid'][0]);
    }


    /**
     * Test custom attribute name with format.
     * @return void
     */
    public function testCustomAttributeNameAndFormat()
    {
        $attributeName = 'eugeneNameIDAttribute';
        $config = ['attribute' => $attributeName, 'format' => '%V'];
        $spId = 'eugeneSP';
        $idpId = 'eugeneIdP';

        $nameId = new NameID();
        $nameId->setValue('eugene@oombaas');
        $nameId->setFormat(Constants::NAMEID_PERSISTENT);
        $nameId->setNameQualifier($idpId);
        $nameId->setSPNameQualifier($spId);

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
        $this->assertEquals("{$nameId->getValue()}", $result['Attributes'][$attributeName][0]);
    }
}
