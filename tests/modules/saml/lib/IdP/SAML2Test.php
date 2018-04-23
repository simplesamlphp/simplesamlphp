<?php

class sspmod_saml_IdP_SAML2Test extends \PHPUnit_Framework_TestCase
{
    public function testProcessSOAPAuthnRequest()
    {
        $username = $_SERVER['PHP_AUTH_USER'] = 'username';
        $password = $_SERVER['PHP_AUTH_PW'] = 'password';
        $state = array();

        sspmod_saml_IdP_SAML2::processSOAPAuthnRequest($state);

        $this->assertEquals($username, $state['core:auth:username']);
        $this->assertEquals($password, $state['core:auth:password']);
    }

    public function testProcessSOAPAuthnRequestMissingUsername()
    {
        $this->setExpectedException('SimpleSAML_Error_Error', 'WRONGUSERPASS');

        $_SERVER['PHP_AUTH_PW'] = 'password';
        unset($_SERVER['PHP_AUTH_USER']);
        $state = array();

        sspmod_saml_IdP_SAML2::processSOAPAuthnRequest($state);
    }

    public function testProcessSOAPAuthnRequestMissingPassword()
    {
        $this->setExpectedException('SimpleSAML_Error_Error', 'WRONGUSERPASS');

        $_SERVER['PHP_AUTH_USER'] = 'username';
        unset($_SERVER['PHP_AUTH_PW']);
        $state = array();

        sspmod_saml_IdP_SAML2::processSOAPAuthnRequest($state);
    }
}
