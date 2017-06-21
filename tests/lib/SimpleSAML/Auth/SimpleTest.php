<?php

/**
 * Tests for SimpleSAML_Auth_Simple
 */
class Auth_SimpleTest extends PHPUnit_Framework_TestCase
{

    public function testGetNameIDHelpersReturnsNullIfNotAuthorized()
    {
        $auth = $this->getMockBuilder('SimpleSAML_Auth_Simple')
            ->disableOriginalConstructor()
            ->setMethods(array('isAuthenticated'))
            ->getMock();

        $auth->expects($this->exactly(3))
            ->method('isAuthenticated')
            ->willReturn(false);

        $this->assertNull($auth->getNameIDValue());
        $this->assertNull($auth->getNameIDFormat());
        $this->assertNull($auth->getNameIDNameQualifier());
    }

    public function testGetNameIDHelpersReturnProperValues()
    {
        $auth = $this->getMockBuilder('SimpleSAML_Auth_Simple')
            ->disableOriginalConstructor()
            ->setMethods(array('isAuthenticated', 'getAuthData'))
            ->getMock();

        $auth->expects($this->exactly(3))
            ->method('isAuthenticated')
            ->willReturn(true);

        $auth->expects($this->exactly(3))
            ->method('getAuthData')
            ->with('saml:sp:NameID')
            ->willReturn(array(
                'Value' => '12345',
                'Format' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
                'SPNameQualifier' => 'http:/localhost/saml/module.php/saml/sp/metadata.php/example.com'
            ));

        $this->assertEquals(
            '12345',
            $auth->getNameIDValue()
        );

        $this->assertEquals(
            'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
            $auth->getNameIDFormat()
        );

        $this->assertEquals(
            'http:/localhost/saml/module.php/saml/sp/metadata.php/example.com',
            $auth->getNameIDNameQualifier()
        );
    }
}
