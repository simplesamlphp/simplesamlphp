<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml;

use PHPUnit\Framework\TestCase;
use SAML2\AuthnRequest;
use SimpleSAML\Configuration;
use SimpleSAML\Error as SSP_Error;
use SimpleSAML\Error\ErrorCodes;
use SimpleSAML\Module\saml\Message;
use SimpleSAML\XMLSecurity\TestUtils\PEMCertificatesMock;

/**
 * Test for SAML Message handling
 *
 * @package SimpleSAML\Test\Module\saml
 */
class MessageTest extends TestCase
{
    /** @var string */
    protected string $acmeeEntityId;

    /** @var string */
    protected string $acmeeCertificate;

    /** @var array */
    protected array $acmeeMetadata;

    /** @var string */
    protected string $spEntityId;

    /** @var array */
    protected array $spMetadata;

    /** @var string */
    protected string $acmeeCertificateWrong;

    /** @var string */
    protected string $acmeeCertificateMismatch;


    /**
     * Set up for each test.
     */
    protected function setUp(): void
    {
        $this->acmeeEntityId = 'https://idp.acmee.com/example';

        $this->acmeeCertificate = PEMCertificatesMock::getPlainCertificateContents();

        $this->acmeeCertificateMismatch = PEMCertificatesMock::getPlainCertificateContents(
            PEMCertificatesMock::OTHER_CERTIFICATE,
        );

        $this->acmeeCertificateWrong = PEMCertificatesMock::getPlainCertificateContents(
            PEMCertificatesMock::CORRUPTED_CERTIFICATE,
        );

        // Metadata array, just like you'd see in saml20-idp-remote.php
        $this->acmeeMetadata = [
            'entityid' => $this->acmeeEntityId,
            'description' => [
                'en' => 'Acmee IdP for testing',
            ],
            'OrganizationName' => [
                'en' => 'Acmee',
            ],
            'name' => [
                'en' => 'Acmee Example Identity Provider',
            ],
            'OrganizationDisplayName' => [
                'en' => 'Acmee',
            ],
            'url' => [
                'en' => 'https://www.acmee.com/',
            ],
            'OrganizationURL' => [
                'en' => 'https://www.acmee.com/',
            ],
            'contacts' => [
                [
                    'contactType' => 'technical',
                    'givenName' => 'Test Admin',
                    'emailAddress' => [
                        'admin@acmee.com',
                    ],
                ],
            ],
            'metadata-set' => 'saml20-idp-remote',
            'SingleSignOnService' => [
                [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                    'Location' => 'https://idp.acmee.com/example/sso',
                ],
                [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                    'Location' => 'https://idp.acmee.com/example/sso',
                ],
            ],
            'SingleLogoutService' => [
                [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                    'Location' => 'https://idp.acmee.com/example/logout',
                ],
            ],
            'ArtifactResolutionService' => [],
            'NameIDFormats' => [
                'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
                'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
            ],
            'keys' => [
                [
                    'encryption' => false,
                    'signing' => true,
                    'type' => 'X509Certificate',
                    'X509Certificate' => $this->acmeeCertificate,
                ],
            ],
            'scope' => [
                'acmee.com',
            ],
            'UIInfo' => [
                'DisplayName' => [
                    'en' => 'Acmee Identity Provider',
                ],
                'Description' => [],
                'InformationURL' => [],
                'PrivacyStatementURL' => [],
            ],
        ];

        // Build minimal SP metadata:
        $this->spEntityId = 'https://sp.acmee.com/demo';
        $this->spMetadata = [
            'entityID' => $this->spEntityId,
            'AssertionConsumerService' => [
                [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                    'Location' => 'https://sp.acmee.com/demo/acs',
                ],
            ],
        ];

        parent::setUp();
    }


    public function testCheckSignThrowsWhenBadCertificate(): void
    {
        $this->expectException(\Exception::class);

        $localMetadata = $this->acmeeMetadata;
        foreach ($localMetadata['keys'] as $index => $cert) {
            $localMetadata['keys'][$index]['X509Certificate'] = $this->acmeeCertificateWrong;
        }
        $idpConfig = Configuration::loadFromArray(
            $localMetadata,
            $localMetadata['entityid'],
        );

        $spConfig = Configuration::loadFromArray(
            $this->spMetadata,
            $this->spMetadata['entityID'],
        );

        // Build the AuthnRequest using the Message helper:
        $authnRequest = Message::buildAuthnRequest($spConfig, $idpConfig);

        // You may now use $authnRequest with checkSign:
        Message::checkSign($idpConfig, $authnRequest);
    }


    public function testCheckSignThrowsWhenMissingCertificate(): void
    {
        $this->expectException(SSP_Error\Exception::class);
        $this->expectExceptionMessage('Missing certificate in metadata for \'https://idp.acmee.com/example\'');

        $localMetadata = $this->acmeeMetadata;
        unset($localMetadata['keys']);
        $idpConfig = Configuration::loadFromArray(
            $localMetadata,
            $localMetadata['entityid'],
        );

        $spConfig = Configuration::loadFromArray(
            $this->spMetadata,
            $this->spMetadata['entityID'],
        );

        // Build the AuthnRequest using the Message helper:
        $authnRequest = Message::buildAuthnRequest($spConfig, $idpConfig);

        // You may now use $authnRequest with checkSign:
        Message::checkSign($idpConfig, $authnRequest);
    }


    public function testCheckSignThrowsWhenCertificateMismatch(): void
    {
        $this->expectException(SSP_Error\Error::class);
        $expectedMessage =  [
            'errorCode' => ErrorCodes::NOTVALIDCERTSIGNATURE,
            '%MESSAGE%' => (new ErrorCodes())->getMessage(ErrorCodes::NOTVALIDCERTSIGNATURE),
            '%ELEMENT%'  => 'SAML2\AuthnRequest',
            '%ISSUER%'   => 'https://sp.acmee.com/demo',
            '%ENTITYID%' => 'https://idp.acmee.com/example',
        ];

        $this->expectExceptionMessage(json_encode($expectedMessage));

        $localMetadata = $this->acmeeMetadata;
        $localMetadata['signature.privatekey'] = PEMCertificatesMock::buildKeysPath(
            PEMCertificatesMock::PRIVATE_KEY,
        );
        $localMetadata['signature.privatekey_pass'] = PEMCertificatesMock::PASSPHRASE;
        $localMetadata['sign.authnrequest'] = true;
        foreach ($localMetadata['keys'] as $index => $cert) {
            $localMetadata['keys'][$index]['X509Certificate'] = $this->acmeeCertificateMismatch;
        }
        $idpConfig = Configuration::loadFromArray(
            $localMetadata,
            $localMetadata['entityid'],
        );

        $spConfig = Configuration::loadFromArray(
            $this->spMetadata,
            $this->spMetadata['entityID'],
        );

        // Build the AuthnRequest using the Message helper:
        $authnRequest = Message::buildAuthnRequest($spConfig, $idpConfig);
        // Extract to signed xml and then parse again to validate
        $signedDomElement = $authnRequest->toSignedXML();
        $parsed = new AuthnRequest($signedDomElement);
        Message::checkSign($idpConfig, $parsed);
    }


    public function testCheckSignSucceeds(): void
    {
        $localMetadata = $this->acmeeMetadata;
        $localMetadata['signature.privatekey'] = PEMCertificatesMock::buildKeysPath(
            PEMCertificatesMock::PRIVATE_KEY,
        );
        $localMetadata['signature.privatekey_pass'] = PEMCertificatesMock::PASSPHRASE;
        $localMetadata['sign.authnrequest'] = true;

        $idpConfig = Configuration::loadFromArray(
            $localMetadata,
            $localMetadata['entityid'],
        );

        $spConfig = Configuration::loadFromArray(
            $this->spMetadata,
            $this->spMetadata['entityID'],
        );

        // Build the AuthnRequest using the Message helper:
        $authnRequest = Message::buildAuthnRequest($spConfig, $idpConfig);
        // Extract to signed xml and then parse again to validate
        $signedDomElement = $authnRequest->toSignedXML();
        $parsed = new AuthnRequest($signedDomElement);
        $isValid = Message::checkSign($idpConfig, $parsed);
        $this->assertTrue($isValid);
    }
}
