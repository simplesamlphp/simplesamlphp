<?php

/**
 * Tests for SimpleSAML_Auth_Simple
 */
class Auth_SimpleTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test that getNameIDDataArray() returns null if the user is not authorized.
     */
    public function testGetNameIDDataArrayReturnsNullIfNotAuthorized()
    {
        $auth = $this->getMockBuilder('SimpleSAML_Auth_Simple')
            ->disableOriginalConstructor()
            ->setMethods(array('isAuthenticated'))
            ->getMock();

        $auth->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(false);

        $this->assertNull($auth->getNameIDDataArray());
    }

    /**
     * Test that getNameIDDataArray() returns an array
     */
    public function testGetNameIDDataArrayReturnsArray()
    {
        $auth = $this->getMockBuilder('SimpleSAML_Auth_Simple')
            ->disableOriginalConstructor()
            ->setMethods(array('isAuthenticated', 'getAuthData'))
            ->getMock();

        $auth->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true);

        $auth->expects($this->once())
            ->method('getAuthData')
            ->with('saml:sp:NameID')
            ->willReturn(array(
                'Value' => '12345',
                'SPNameQualifier' => 'http:/localhost/saml/module.php/saml/sp/metadata.php/example.com',
                'Format' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent'
            ));

        $nameIDData = $auth->getNameIDDataArray();

        $this->assertTrue(is_array($nameIDData));
        $this->assertArrayHasKey('Value', $nameIDData);
        $this->assertArrayHasKey('SPNameQualifier', $nameIDData);
        $this->assertArrayHasKey('Format', $nameIDData);

        $this->assertEquals(
            '12345',
            $nameIDData['Value']
        );

        $this->assertEquals(
            'http:/localhost/saml/module.php/saml/sp/metadata.php/example.com',
            $nameIDData['SPNameQualifier']
        );

        $this->assertEquals(
            'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
            $nameIDData['Format']
        );
    }

    /**
     * Test that getNameIDData() returns a value if it is set
     */
    public function testGetNameIDData()
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
                'SPNameQualifier' => 'http:/localhost/saml/module.php/saml/sp/metadata.php/example.com',
                'Format' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent'
            ));

        $this->assertEquals(
            '12345',
            $auth->getNameIDData('Value')
        );

        $this->assertEquals(
            'http:/localhost/saml/module.php/saml/sp/metadata.php/example.com',
            $auth->getNameIDData('SPNameQualifier')
        );

        $this->assertEquals(
            'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
            $auth->getNameIDData('Format')
        );
    }

    /**
     * Test that getNameIDData() returns null if the user is not authorized.
     */
    public function testGetNameIDDataReturnsNullIfNotAuthorized()
    {
        $auth = $this->getMockBuilder('SimpleSAML_Auth_Simple')
            ->disableOriginalConstructor()
            ->setMethods(array('isAuthenticated'))
            ->getMock();

        $auth->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(false);

        $this->assertNull($auth->getNameIDData('Value'));
    }

    /**
     * Test that getNameIDData() returns null if the value is not set
     */
    public function testGetNameIDDataReturnsNullIfNotSet()
    {
        $auth = $this->getMockBuilder('SimpleSAML_Auth_Simple')
            ->disableOriginalConstructor()
            ->setMethods(array('isAuthenticated', 'getAuthData'))
            ->getMock();

        $auth->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true);

        $auth->expects($this->once())
            ->method('getAuthData')
            ->with('saml:sp:NameID')
            ->willReturn(array(
                'Value' => '12345',
                'SPNameQualifier' => 'http:/localhost/saml/module.php/saml/sp/metadata.php/example.com',
                'Format' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent'
            ));

        $this->assertNull($auth->getNameIDData('DoesNotExist'));
    }
}
