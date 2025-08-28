<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml;

use PHPUnit\Framework\TestCase;
use SAML2\AuthnRequest;
use SimpleSAML\Configuration;
use SimpleSAML\Error as SSP_Error;
use SimpleSAML\Module\saml\Message;

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

        $this->acmeeCertificate = <<<EOCERTVALID
MIICVDCCAb2gAwIBAgIBADANBgkqhkiG9w0BAQ0FADBGMQswCQYDVQQGEwJ1czET
MBEGA1UECAwKY2FsaWZvcm5pYTEOMAwGA1UECgwFYWNtZWUxEjAQBgNVBAMMCWFj
bWVlLmNvbTAgFw0yNTA4MjgxNDE3MTZaGA8zMDI0MTIyOTE0MTcxNlowRjELMAkG
A1UEBhMCdXMxEzARBgNVBAgMCmNhbGlmb3JuaWExDjAMBgNVBAoMBWFjbWVlMRIw
EAYDVQQDDAlhY21lZS5jb20wgZ8wDQYJKoZIhvcNAQEBBQADgY0AMIGJAoGBALfi
YX68QeDEaOD1srtxTwtIMat1jtrqjdadI/Ksa/JPfQNv1Wjz1SvRgvAi4JXrl9KQ
7iAv7wn0UNV7HKEAQws0iFheHUHiqk9VrwxLTrZTwFiBjwkzoDo4yrLCArurPaDu
rqH03KD9dMu1EOgMHWvNYm3o+D2X60UelAVKP2DVAgMBAAGjUDBOMB0GA1UdDgQW
BBS3dLfltNkyLvyuktzZRjReuR3ZUjAfBgNVHSMEGDAWgBS3dLfltNkyLvyuktzZ
RjReuR3ZUjAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBDQUAA4GBAB4+5VK0QaEM
EF8pvC+qKs4qsnoojMkOzsswHZYoSHHF2m+ZWLnTZd2o1DQ2ZCV+Y8G/xKAgREns
3jvtWEBojSVJfwhkG/mwfxeGgTnVwOctmTJTRmRaT560L8BX0avx6tcexJal6G3y
CEpB/IjANYp6rT2+iCdpgbPJBlWbJsJx
EOCERTVALID;

        $this->acmeeCertificateMismatch = <<<EOCERTINVALID
MIIDnTCCAoWgAwIBAgIgLehYh1dpeK6jw2fEbJf2eapg3+74qpNqYXwj3pP9AxIw
DQYJKoZIhvcNAQEFBQAwZTEJMAcGA1UEBhMAMRAwDgYDVQQKDAdleGFtcGxlMQkw
BwYDVQQLDAAxFDASBgNVBAMMC2V4YW1wbGUuY29tMQ8wDQYJKoZIhvcNAQkBFgAx
FDASBgNVBAMMC2V4YW1wbGUuY29tMB4XDTI1MDgyMzEzMDQ0MVoXDTM1MDgyNDEz
MDQ0MVowTzEJMAcGA1UEBhMAMRAwDgYDVQQKDAdleGFtcGxlMQkwBwYDVQQLDAAx
FDASBgNVBAMMC2V4YW1wbGUuY29tMQ8wDQYJKoZIhvcNAQkBFgAwggEiMA0GCSqG
SIb3DQEBAQUAA4IBDwAwggEKAoIBAQDY7Y4ENmUFIjmiKaFK6HG/FvhuGG/yVAAp
7v+smI33lKnvTdkTrH3MujYp+3R5GyivcP6M4iY6Wi5VJ9vzUV0W7tNjD2NngG/0
0kM/6u41sMhHBZnSB97HcAwzOcPrPZjvjntG4UDemGDJ3clw6VL3/DMzJTsLGwx/
050sIiHNXsL3WsuP/XKY2aEmg6S4PoiKYiNz9NabCV2RROKGGk9Ar8+c4HD43x/x
VJdj36HizNaHB9tXQoJ00YVq8J3jYTwUCo9AnKDczRHZk2w0niCMolMi327hsx1K
H3uuL741KIhquOEzPzb90NaNd2yjHAHl5gXitBs7A3qZhhQYIgULAgMBAAGjTzBN
MB0GA1UdDgQWBBQf+e9Md02jLhFD6MXGOGsO/7YGrDAfBgNVHSMEGDAWgBQf+e9M
d02jLhFD6MXGOGsO/7YGrDALBgNVHREEBDACggAwDQYJKoZIhvcNAQEFBQADggEB
AD/wuKcY+NsctxeEO17Upd+7XSCau3GjAp+tT7Y7VZ7jDcW3x7zqWfQppmLThmIq
iXAMgKz2WTjtJphj/AV/QFUHff7wadFm4WLAL5a66LbQZN6/r6olvBw2rJq4Fwyl
QX4echQghnopDbdKNA516PCq9jxttQxdO+x1kSRqm05lkK51cV4aG1Nyt+kA+cKc
mnxDWVg96iuAKtPV5WBaksb821/AgOFLbNEW37y037Gr8UaLEhvZWpMIx9r+XO2u
bPkKAQmfc2FPQV9m4bEO0ihl8fKSljXB8WOefL/7jvHMUFTPOSzLZggHYXirAQ5r
OKTV+3OdzdvDlTqKhof9qKs=
EOCERTINVALID;

        $this->acmeeCertificateWrong = <<<EOCERTWRONG
MIIDiDCCAnACCQC5NZIb4AVJuDANBgkqhkiG9w0BAQsFADCBhzELMAkGA1UEBhMC
VVMxCzAJBgNVBAgMAkNBMQswCQYDVQQHDAJMQTEQMA4GA1UECgwHQWNtZWUgSW5j
MQ4wDAYDVQQLDAVJVCBEZXAxKjAoBgNVBAMMIWh0dHBzOi8vaWRwLmFjbWVlLmNv
bS9leGFtcGxlMB4XDTI0MDgyODEzNDk1OFoXDTM0MDgyNTEzNDk1OFowgYcxCzAJ
BgNVBAYTAlVTMQswCQYDVQQIDAJDQTELMAkGA1UEBwwCTEEhEDAOBgNVBAoMB0Fj
bWVlIEluYzEOMAwGA1UECwwFSVQgRGVwMSowKAYDVQQDDCFTSFQ1czBJZHBcY21l
ZS5jb20vZXhhbXBsZTCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBALXN
dlFSXwL5MZqcmF7vPhBk8Nqpw1PQAS1/aCta/CcZtqcaOrJgEbg4qM5QgRAkmk5B
YT/JQqwyhrIUF7fArw9dZyEvNk96zM1rNR9ez8rANMTb8P6+WM7CjQnUeHLJ2jno
FEvKTPio4co3M6wrCkD7tDjposL7YTbD7cGylWHaIyzFzmDbkEi8UuPQq10uQxtz
K3gvt08HcgccpLMhq1pyqStwoxjhNBU5B616uQuT6ayEPyAAriBo5gvUG3iSw5f7
XapQidoxvN7MTEswtbyxxWhHT1Edk0QXOgZIa4vDCilAGe3A2If77mIhbIqSmqmP
X3AiBOOnfR4r+3vK/cMCAwEAATANBgkqhkiG9w0BAQsFAAOCAQEAM7QjRrw8Da7A
R39pFka3lrQOjYFo0U49TAhCE7SiHsVJjaJ7MTGvPA1uNBW5SmzxXKWC5NRuFkgU
3OhXHn6cS3MEruHDYtT/jldwP6SAAV4AfQRd2rzGptO2au/cAnGCb5WQ6kV1Kv8Y
IMNGvNnj3eLhtAf7EXQHvFb3n0MkzyJtvHNPy0lcmJF7daRtaDKmKo+ooKwCw8he
8YAT4CtGJJf3hWwZUVugCi1Eu+h4lW3u+09GyjhNoC6GBrTEPUm5TfAAKQk2294G
rmt3CCz2F8T6fboKo+LwoAAp/Y8PaCnebR81OHeuMY8LB1u5lIFASrufA+haOg7i
50Qk4cxlzg==
EOCERTWRONG;

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
            'errorCode' => '0',
            'element'  => 'SAML2\AuthnRequest',
            'message'  => 'Unable to validate Signature',
            'issuer'   => 'https://sp.acmee.com/demo',
            'entityid' => 'https://idp.acmee.com/example',
        ];

        $this->expectExceptionMessage(json_encode($expectedMessage));

        $localMetadata = $this->acmeeMetadata;
        $localMetadata['signature.privatekey'] = __DIR__ . '/certs/acmee.priv.key';
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
        $localMetadata['signature.privatekey'] = __DIR__ . '/certs/acmee.priv.key';
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
