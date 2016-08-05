<?php

// Custom Exception to throw to terminate a TestCase
class ExitTestException extends Exception {
    private $testResult;

    public function __construct($testResult) {
        parent::__construct("ExitTestException", 0, null);
        $this->testResult = $testResult;
    }

    function getTestResult() {
        return $this->testResult;
    }
}


/* Wrap the SSP sspmod_saml_Auth_Source_SP class
   - Use introspection to make startSSO2Test available
   - Override sendSAML2AuthnRequest() to catch the AuthnRequest being sent
*/
class sspmod_saml_Auth_Source_SP_Tester extends \sspmod_saml_Auth_Source_SP
{
    public function __construct($info, $config) {
        parent::__construct($info, $config);
    }

    public function startSSO2Test(SimpleSAML_Configuration $idpMetadata, array $state) {
        $reflector = new ReflectionObject($this);
        $method=$reflector->getMethod('startSSO2');
        $method->setAccessible(true);
        $method->invoke($this, $idpMetadata, $state);
    }

    // Override
    public function sendSAML2AuthnRequest(array &$state, SAML2_Binding $binding, SAML2_AuthnRequest $ar) {
        // Exit test. Continuing would mean running into a assert(FALSE)
        throw new ExitTestException(
            array(
                'state' => $state,
                'binding' => $binding,
                'ar' => $ar,
            )
        );
    }
}

class Auth_Source_SP_Test extends PHPUnit_Framework_TestCase
{
    private $idpMetadata = NULL;
    private $idpConfigArray = array(
        'metadata-set' => 'saml20-idp-remote',
        'entityid' => 'https://engine.surfconext.nl/authentication/idp/metadata',
        'SingleSignOnService' =>
            array (
                0 =>
                    array (
                        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'Location' => 'https://engine.surfconext.nl/authentication/idp/single-sign-on',
                    ),
            ),
        'keys' =>
            array (
                0 =>
                    array (
                        'encryption' => false,
                        'signing' => true,
                        'type' => 'X509Certificate',
                        'X509Certificate' => 'MIID3zCCAsegAwIBAgIJAMVC9xn1ZfsuMA0GCSqGSIb3DQEBCwUAMIGFMQswCQYDVQQGEwJOTDEQMA4GA1UECAwHVXRyZWNodDEQMA4GA1UEBwwHVXRyZWNodDEVMBMGA1UECgwMU1VSRm5ldCBCLlYuMRMwEQYDVQQLDApTVVJGY29uZXh0MSYwJAYDVQQDDB1lbmdpbmUuc3VyZmNvbmV4dC5ubCAyMDE0MDUwNTAeFw0xNDA1MDUxNDIyMzVaFw0xOTA1MDUxNDIyMzVaMIGFMQswCQYDVQQGEwJOTDEQMA4GA1UECAwHVXRyZWNodDEQMA4GA1UEBwwHVXRyZWNodDEVMBMGA1UECgwMU1VSRm5ldCBCLlYuMRMwEQYDVQQLDApTVVJGY29uZXh0MSYwJAYDVQQDDB1lbmdpbmUuc3VyZmNvbmV4dC5ubCAyMDE0MDUwNTCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAKthMDbB0jKHefPzmRu9t2h7iLP4wAXr42bHpjzTEk6gttHFb4l/hFiz1YBI88TjiH6hVjnozo/YHA2c51us+Y7g0XoS7653lbUN/EHzvDMuyis4Xi2Ijf1A/OUQfH1iFUWttIgtWK9+fatXoGUS6tirQvrzVh6ZstEp1xbpo1SF6UoVl+fh7tM81qz+Crr/Kroan0UjpZOFTwxPoK6fdLgMAieKSCRmBGpbJHbQ2xxbdykBBrBbdfzIX4CDepfjE9h/40ldw5jRn3e392jrS6htk23N9BWWrpBT5QCk0kH3h/6F1Dm6TkyG9CDtt73/anuRkvXbeygI4wml9bL3rE8CAwEAAaNQME4wHQYDVR0OBBYEFD+Ac7akFxaMhBQAjVfvgGfY8hNKMB8GA1UdIwQYMBaAFD+Ac7akFxaMhBQAjVfvgGfY8hNKMAwGA1UdEwQFMAMBAf8wDQYJKoZIhvcNAQELBQADggEBAC8L9D67CxIhGo5aGVu63WqRHBNOdo/FAGI7LURDFeRmG5nRw/VXzJLGJksh4FSkx7aPrxNWF1uFiDZ80EuYQuIv7bDLblK31ZEbdg1R9LgiZCdYSr464I7yXQY9o6FiNtSKZkQO8EsscJPPy/Zp4uHAnADWACkOUHiCbcKiUUFu66dX0Wr/v53Gekz487GgVRs8HEeT9MU1reBKRgdENR8PNg4rbQfLc3YQKLWK7yWnn/RenjDpuCiePj8N8/80tGgrNgK/6fzM3zI18sSywnXLswxqDb/J+jgVxnQ6MrsTf1urM8MnfcxG/82oHIwfMh/sXPCZpo+DTLkhQxctJ3M=',
                    ),
            ),
    );

    private function getIdpMetadata() {

        if (!$this->idpMetadata) {
            $this->idpMetadata = new SimpleSAML_Configuration($this->idpConfigArray, 'Auth_Source_SP_Test::getIdpMetadata()');
        }

        return $this->idpMetadata;
    }

    /** Create a SAML AuthnRequest using sspmod_saml_Auth_Source_SP
     * @param $state State Array to use in the test. This is an array of the Parameters described in section 2 of
     *               https://simplesamlphp.org/docs/development/saml:sp
     * @return SAML2_AuthnRequest
     */
    private function CreateAuthnRequest($state = array()) {
        $info=array( 'AuthId' => 'default-sp' );
        $config=array();
        $as = new \sspmod_saml_Auth_Source_SP_Tester($info, $config);

        /** @var SAML2_AuthnRequest $ar */
        $ar=NULL;
        try {
            $as->startSSO2Test($this->getIdpMetadata(), $state);
            $this->assertTrue(FALSE, 'Expected ExitTestException');
        }
        catch (ExitTestException $e) {
            $r = $e->getTestResult();
            $ar = $r['ar'];
        }
        return $ar;
    }

    /** Test generating a authnrequest
     * @test **/
    public function TestAuthnRequest() {
        /** @var SAML2_AuthnRequest $ar */
        $ar = $this->CreateAuthnRequest();

        // Assert values in the generated AuthnRequest
        /** @var $xml DOMElement */
        $xml=$ar->toSignedXML();
        // echo $xml->ownerDocument->saveXML($xml);  // Print XML
        $q=SAML2_Utils::xpQuery($xml, '/samlp:AuthnRequest/@Destination');
        $this->assertEquals(
            $this->idpConfigArray['SingleSignOnService'][0]['Location'],
            $q[0]->value);
        $q=SAML2_Utils::xpQuery($xml, '/samlp:AuthnRequest/saml:Issuer');
        $this->assertEquals(
            'http://localhost/simplesaml/module.php/saml/sp/metadata.php/default-sp',
            $q[0]->textContent);
    }

    /** Test setting a Subject
      * @test **/
    public function TestNameID() {
        $state=array(
            'saml:NameID' => array('Value' => 'user@example.org', 'Format' => SAML2_Const::NAMEID_UNSPECIFIED)
        );

        /** @var SAML2_AuthnRequest $ar */
        $ar = $this->CreateAuthnRequest($state);

        $nameID=$ar->getNameId();
        $this->assertEquals($state['saml:NameID']['Value'], $nameID['Value']);
        $this->assertEquals($state['saml:NameID']['Format'], $nameID['Format']);

        /** @var $xml DOMElement */
        $xml=$ar->toSignedXML();
        //echo $xml->ownerDocument->saveXML($xml);  // Print XML
        $q=SAML2_Utils::xpQuery($xml, '/samlp:AuthnRequest/saml:Subject/saml:NameID/@Format');
        $this->assertEquals(
            $state['saml:NameID']['Format'],
            $q[0]->value);
        $q=SAML2_Utils::xpQuery($xml, '/samlp:AuthnRequest/saml:Subject/saml:NameID');
        $this->assertEquals(
            $state['saml:NameID']['Value'],
            $q[0]->textContent);
    }

    /** Test setting an AuthnConextClassRef
      * @test **/
    public function TestAuthnContextClassRef() {
        $state=array(
            'saml:AuthnContextClassRef' => 'http://example.com/myAuthnContextClassRef'
        );

        /** @var SAML2_AuthnRequest $ar */
        $ar = $this->CreateAuthnRequest($state);

        $a=$ar->getRequestedAuthnContext();
        $this->assertEquals(
            $state['saml:AuthnContextClassRef'],
            $a['AuthnContextClassRef'][0] );

        /** @var $xml DOMElement */
        $xml=$ar->toSignedXML();
        //echo $xml->ownerDocument->saveXML($xml);  // Print XML
        $q=SAML2_Utils::xpQuery($xml, '/samlp:AuthnRequest/samlp:RequestedAuthnContext/saml:AuthnContextClassRef');
        $this->assertEquals(
            $state['saml:AuthnContextClassRef'],
            $q[0]->textContent);
    }

    /** Test setting ForcedAuthn
     * @test **/
    public function TestForcedAuthn() {
        $state=array(
            'ForceAuthn' => true
        );

        /** @var SAML2_AuthnRequest $ar */
        $ar = $this->CreateAuthnRequest($state);

        $this->assertEquals(
            $state['ForceAuthn'],
            $ar->getForceAuthn() );

        /** @var $xml DOMElement */
        $xml=$ar->toSignedXML();
        //echo $xml->ownerDocument->saveXML($xml);  // Print XML
        $q=SAML2_Utils::xpQuery($xml, '/samlp:AuthnRequest/@ForceAuthn');
        $this->assertEquals(
            $state['ForceAuthn'] ? 'true' : 'false',
            $q[0]->value);
    }

}
