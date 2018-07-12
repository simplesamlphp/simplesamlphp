<?php

class SAML2Test extends \PHPUnit_Framework_TestCase
{
    public function testProcessSOAPAuthnRequest()
    {
        $username = $_SERVER['PHP_AUTH_USER'] = 'username';
        $password = $_SERVER['PHP_AUTH_PW'] = 'password';
        $state = array();

        \SimpleSAML\Module\saml\IdP\SAML2::processSOAPAuthnRequest($state);

        $this->assertEquals($username, $state['core:auth:username']);
        $this->assertEquals($password, $state['core:auth:password']);
    }

    public function testProcessSOAPAuthnRequestMissingUsername()
    {
        $this->setExpectedException('\SimpleSAML\Error\Error', 'WRONGUSERPASS');

        $_SERVER['PHP_AUTH_PW'] = 'password';
        unset($_SERVER['PHP_AUTH_USER']);
        $state = array();

        \SimpleSAML\Module\saml\IdP\SAML2::processSOAPAuthnRequest($state);
    }

    public function testProcessSOAPAuthnRequestMissingPassword()
    {
        $this->setExpectedException('\SimpleSAML\Error\Error', 'WRONGUSERPASS');

        $_SERVER['PHP_AUTH_USER'] = 'username';
        unset($_SERVER['PHP_AUTH_PW']);
        $state = array();

        \SimpleSAML\Module\saml\IdP\SAML2::processSOAPAuthnRequest($state);
    }
}
