<?php
/*
 * This file is part of the sgomezsimpleshibphp.
 *
 * (c) Sergio Gómez <sergio@uco.es>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace SimpleSAML\Test\XML\Shib13;

use SimpleSAML\XML\Shib13\AuthnResponse;

class AuthnResponseTest extends \PHPUnit_Framework_TestCase
{
    const XMLDOC = <<< XML
<Response xmlns="urn:oasis:names:tc:SAML:1.0:protocol" 
    MajorVersion="1" MinorVersion="1"
    ResponseID="" IssueInstant="">
    <Assertion xmlns="urn:oasis:names:tc:SAML:1.0:assertion"
        AssertionID="" IssueInstant=""
        MajorVersion="1" MinorVersion="1"
        Issuer="Issuer"
    >
        <AuthenticationStatement AuthenticationInstant="" AuthenticationMethod="">
            <Subject>
                <NameIdentifier Format="urn:mace:shibboleth:1.0:nameIdentifier">NameIdentifier</NameIdentifier>
            </Subject>
        </AuthenticationStatement>
    </Assertion>
</Response>
XML;

    const BADXMLDOC = <<< XML
<Response xmlns="urn:oasis:names:tc:SAML:1.0:protocol" 
    MajorVersion="1" MinorVersion="1"
    ResponseID="" IssueInstant="">
    <Assertion xmlns="urn:oasis:names:tc:SAML:1.0:assertion"
        AssertionID="" IssueInstant=""
        MajorVersion="1" MinorVersion="1"
    >
        <AuthenticationStatement AuthenticationInstant="" AuthenticationMethod="">
            <Subject>
                <NameIdentifier Format="urn:mace:shibboleth:1.0:nameIdentifier">NameIdentifier</NameIdentifier>
            </Subject>
        </AuthenticationStatement>
    </Assertion>
</Response>
XML;

    /**
     * @var AuthnResponse
     */
    private $xml;

    protected function setUp()
    {
        $this->xml = new AuthnResponse();
        $this->xml->setXML(static::XMLDOC);
    }

    /**
     * @covers \SimpleSAML\XML\Shib13\AuthnResponse::setXML
     * @test
     */
    public function setXML()
    {
        $this->xml = new AuthnResponse();
        $this->xml->setXML(static::XMLDOC);
    }

    /**
     * @covers \SimpleSAML\XML\Shib13\AuthnResponse::doXPathQuery
     * @covers \SimpleSAML\XML\Shib13\AuthnResponse::getIssuer
     * @covers \SimpleSAML\XML\Shib13\AuthnResponse::setXML
     * @test
     */
    public function getIssuer()
    {
        $result = $this->xml->getIssuer();

        $this->assertEquals(
            'Issuer',
            $result
        );
    }

    /**
     * @covers \SimpleSAML\XML\Shib13\AuthnResponse::getIssuer
     * @covers \SimpleSAML\XML\Shib13\AuthnResponse::setXML
     * @expectedException \Exception
     * @test
     */
    public function getIssuerException()
    {
        $xml = new AuthnResponse();
        $xml->setXML(static::BADXMLDOC);

        $xml->getIssuer();
    }

    /**
     * @covers \SimpleSAML\XML\Shib13\AuthnResponse::getNameID
     * @covers \SimpleSAML\XML\Shib13\AuthnResponse::setXML
     * @test
     */
    public function getNameID()
    {
        $result = $this->xml->getNameID();

        $this->assertEquals(
            array(
                'Value' => 'NameIdentifier',
                'Format' => 'urn:mace:shibboleth:1.0:nameIdentifier',
            ),
            $result
        );
    }
}
