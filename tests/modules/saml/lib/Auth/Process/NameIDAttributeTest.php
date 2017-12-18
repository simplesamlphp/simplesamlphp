<?php
/**
 * Test for the saml:NameIDAttribute filter.
 *
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package SimpleSAMLphp
 */

use PHPUnit\Framework\TestCase;

class NameIDAttributeTest extends TestCase
{

    /*
     * Helper function to run the filter with a given configuration.
     *
     * @param array $config  The filter configuration.
     * @param array $request  The request state.
     * @return array  The state array after processing.
     */
    private function processFilter(array $config, array $request)
    {
        $filter = new sspmod_saml_Auth_Process_NameIDAttribute($config, null);
        $filter->process($request);
        return $request;
    }


    /**
     * Test minimal configuration.
     */
    public function testMinimalConfig()
    {
        $config = array();

        $nameId = new \SAML2\XML\saml\NameID();
        $nameId->value = 'eugene@oombaas';
        $nameId->Format = \SAML2\Constants::NAMEID_PERSISTENT;

        $spId = 'eugeneSP';
        $idpId = 'eugeneIdP';

        $request = array(
            'Source'     => array(
                'entityid' => $spId,
            ),
            'Destination' => array(
                'entityid' => $idpId,
            ),
            'saml:sp:NameID' => $nameId,
        );
        $result = $this->processFilter($config, $request);
        $this->assertEquals("{$spId}!{$idpId}!{$nameId->value}", $result['Attributes']['nameid'][0]);
    }

    /**
     * Test custom attribute name.
     */
    public function testCustomAttributeName()
    {
        $attributeName = 'eugeneNameIDAttribute';
        $config = array('attribute' => $attributeName);

        $nameId = new \SAML2\XML\saml\NameID();
        $nameId->value = 'eugene@oombaas';
        $nameId->Format = \SAML2\Constants::NAMEID_PERSISTENT;

        $spId = 'eugeneSP';
        $idpId = 'eugeneIdP';

        $request = array(
            'Source'     => array(
                'entityid' => $spId,
            ),
            'Destination' => array(
                'entityid' => $idpId,
            ),
            'saml:sp:NameID' => $nameId,
        );
        $result = $this->processFilter($config, $request);
        $this->assertTrue(isset($result['Attributes'][$attributeName]));
        $this->assertEquals("{$spId}!{$idpId}!{$nameId->value}", $result['Attributes'][$attributeName][0]);
    }

    /**
     * Test custom format.
     */
    public function testFormat()
    {
        $config = array('format' => '%V');

        $nameId = new \SAML2\XML\saml\NameID();
        $nameId->value = 'eugene@oombaas';
        $nameId->Format = \SAML2\Constants::NAMEID_PERSISTENT;

        $spId = 'eugeneSP';
        $idpId = 'eugeneIdP';

        $request = array(
            'Source'     => array(
                'entityid' => $spId,
            ),
            'Destination' => array(
                'entityid' => $idpId,
            ),
            'saml:sp:NameID' => $nameId,
        );
        $result = $this->processFilter($config, $request);
        $this->assertEquals("{$nameId->value}", $result['Attributes']['nameid'][0]);
    }


    /**
     * Test custom attribute name with format.
     */
    public function testCustomAttributeNameAndFormat()
    {
        $attributeName = 'eugeneNameIDAttribute';
        $config = array('attribute' => $attributeName, 'format' => '%V');

        $nameId = new \SAML2\XML\saml\NameID();
        $nameId->value = 'eugene@oombaas';
        $nameId->Format = \SAML2\Constants::NAMEID_PERSISTENT;

        $spId = 'eugeneSP';
        $idpId = 'eugeneIdP';

        $request = array(
            'Source'     => array(
                'entityid' => $spId,
            ),
            'Destination' => array(
                'entityid' => $idpId,
            ),
            'saml:sp:NameID' => $nameId,
        );
        $result = $this->processFilter($config, $request);
        $this->assertTrue(isset($result['Attributes'][$attributeName]));
        $this->assertEquals("{$nameId->value}", $result['Attributes'][$attributeName][0]);
    }
}
