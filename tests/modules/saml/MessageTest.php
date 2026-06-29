<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SAML2\Assertion;
use SAML2\AuthnRequest;
use SAML2\Response;
use SAML2\XML\saml\Issuer;
use SAML2\XML\saml\SubjectConfirmation;
use SAML2\XML\saml\SubjectConfirmationData;
use SimpleSAML\Configuration;
use SimpleSAML\Error as SSP_Error;
use SimpleSAML\Error\ErrorCodes;
use SimpleSAML\Module\saml\Message;
use SimpleSAML\SAML2\Constants;
use SimpleSAML\Utils;
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

        $_SERVER['REQUEST_URI'] = '/dummy';
        $_SERVER['REQUEST_METHOD'] = 'GET';
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


    /**
     * Creates and returns the arguments required for testing SubjectConfirmation validation.
     *
     * @param string $currentUrl The expected recipient URL.
     * @param ?string $responseInResponseTo The expected InResponseTo value in the Response.
     * @param ?string $scdInResponseTo The InResponseTo value in the SubjectConfirmationData.
     * @param bool $isUnsolicited Indicates whether the response is unsolicited.
     * @param ?string $expectedRequestId The expected request ID if solicited.
     * @param bool $hok Whether holder-of-key (HoK) validation mode is enabled.
     *
         * @return array{
         *   0:\SAML2\XML\saml\SubjectConfirmation,
         *   1:\SAML2\Response,
         *   2:\SimpleSAML\Utils\HTTP,
         *   3:string,
         *   4:int,
         *   5:bool,
         *   6:?string,
         *   7:bool
         * }
     */
    private function buildValidateSubjectConfirmationArgs(
        string $currentUrl,
        ?string $responseInResponseTo,
        ?string $scdInResponseTo,
        bool $isUnsolicited,
        ?string $expectedRequestId,
        bool $hok = false,
    ): array {
        $scd = new SubjectConfirmationData();
        $scd->setRecipient($currentUrl);
        $scd->setNotOnOrAfter(time() + 300);
        if ($scdInResponseTo !== null) {
            $scd->setInResponseTo($scdInResponseTo);
        }

        $sc = new SubjectConfirmation();
        $sc->setMethod(Constants::CM_BEARER);
        $sc->setSubjectConfirmationData($scd);

        $issuer = new Issuer();
        $issuer->setValue($this->acmeeEntityId);

        $response = new Response();
        $response->setIssuer($issuer);
        $response->setDestination($currentUrl);
        if ($responseInResponseTo !== null) {
            $response->setInResponseTo($responseInResponseTo);
        }

        $httpUtils = new Utils\HTTP();
        $now = time();

        return [$sc, $response, $httpUtils, $currentUrl, $now, $isUnsolicited, $expectedRequestId, $hok];
    }


    /**
     * Invokes the private validateSubjectConfirmation() method on the Message class.
     *
     * @param \SAML2\XML\saml\SubjectConfirmation $sc The SubjectConfirmation instance for validation.
     * @param bool $hok Whether holder-of-key (HoK) validation mode is enabled.
     * @param \SimpleSAML\Utils\HTTP $httpUtils The HTTP utility instance.
     * @param string $currentURL The current recipient URL.
     * @param \SAML2\Response $response The SAML response instance.
     * @param int $now The current timestamp for validation.
     * @param ?string $expectedRequestId The expected request ID for validation.
     * @param bool $isUnsolicited Indicates if the response is unsolicited.
     *
     * @return ?string Null if valid, otherwise returns error message.
     */
    private function callValidateSubjectConfirmation(
        SubjectConfirmation $sc,
        bool $hok,
        Utils\HTTP $httpUtils,
        string $currentURL,
        Response $response,
        int $now,
        ?string $expectedRequestId,
        bool $isUnsolicited,
    ): ?string {
        $m = new ReflectionMethod(Message::class, 'validateSubjectConfirmation');

        try {
            $m->invoke(
                null,
                $sc,
                $hok,
                $httpUtils,
                $currentURL,
                $response,
                $now,
                $expectedRequestId,
                $isUnsolicited,
            );

            return null;
        } catch (SSP_Error\Exception $e) {
            return $e->getMessage();
        }
    }


    /**
     * Provides a set of data scenarios for testing how SubjectConfirmation handles
     * various InResponseTo policies in both solicited and unsolicited responses.
     *
     * @return array The data sets for test cases.
     */
    public static function validateSubjectConfirmationInResponseToProvider(): array
    {
        return [
            'unsolicited rejects SCD InResponseTo' => [
                true,   // isUnsolicited
                null,   // expectedRequestId
                null,   // response InResponseTo
                '_req_1',// scd InResponseTo
                'Unsolicited Response MUST NOT contain SubjectConfirmationData InResponseTo.',
            ],
            'unsolicited rejects Response InResponseTo' => [
                true,   // isUnsolicited
                null,   // expectedRequestId
                '_req_1',// response InResponseTo
                null,   // scd InResponseTo
                'Unsolicited Response MUST NOT contain Response InResponseTo.',
            ],
            'solicited rejects missing expectedRequestId' => [
                false,
                null,
                '_req_1',
                '_req_1',
                'Missing expected request ID for solicited Response validation.',
            ],
            'solicited rejects missing SCD InResponseTo' => [
                false,
                '_req_1',
                '_req_1',
                null,
                'Solicited Response requires SubjectConfirmationData InResponseTo.',
            ],
            'solicited rejects SCD InResponseTo mismatch with expected request id' => [
                false,
                '_req_1',
                '_req_1',
                '_req_2',
                'InResponseTo in SubjectConfirmationData does not match expected request ID.',
            ],
            'solicited rejects missing Response InResponseTo' => [
                false,
                '_req_1',
                null,
                '_req_1',
                'Solicited Response must contain Response InResponseTo.',
            ],
            'solicited rejects SCD InResponseTo mismatch with Response InResponseTo' => [
                false,
                '_req_1',
                '_req_2',
                '_req_1',
                'InResponseTo in SubjectConfirmationData does not match the Response.',
            ],
        ];
    }


    /**
     * Validates how the SubjectConfirmation enforces the InResponseTo policy, ensuring compliance
     * with both solicited and unsolicited response rules as defined by the SAML standard.
     *
     * @dataProvider validateSubjectConfirmationInResponseToProvider
     * @param bool $isUnsolicited Whether the response is unsolicited.
     * @param ?string $expectedRequestId The expected request ID for solicited responses.
     * @param ?string $responseInResponseTo The InResponseTo value in the Response.
     * @param ?string $scdInResponseTo The InResponseTo value in the SubjectConfirmationData.
     * @param string $expectedErrorSubstring The expected error substring in failure cases.
     *
     * @return void
     */
    #[DataProvider('validateSubjectConfirmationInResponseToProvider')]
    public function testValidateSubjectConfirmationEnforcesInResponseToPolicy(
        bool $isUnsolicited,
        ?string $expectedRequestId,
        ?string $responseInResponseTo,
        ?string $scdInResponseTo,
        string $expectedErrorSubstring,
    ): void {
        $currentUrl = 'https://sp.acmee.com/demo/acs';

        [
            $sc,
            $response,
            $httpUtils,
            $currentURL,
            $now,
            $isUnsolicitedBuilt,
            $expectedRequestIdBuilt,
            $hok,
        ] = $this->buildValidateSubjectConfirmationArgs(
            $currentUrl,
            $responseInResponseTo,
            $scdInResponseTo,
            $isUnsolicited,
            $expectedRequestId,
        );

        $result = $this->callValidateSubjectConfirmation(
            $sc,
            $hok,
            $httpUtils,
            $currentURL,
            $response,
            $now,
            $expectedRequestIdBuilt,
            $isUnsolicitedBuilt,
        );

        $this->assertIsString($result);
        $this->assertStringContainsString($expectedErrorSubstring, $result);
    }


    private function setRequireSignedResponseFlag(bool $enabled): void
    {
        Configuration::setPreLoadedConfig(
            Configuration::loadFromArray(
                [
                    'response.require_signed' => $enabled,
                ],
                '[ARRAY]',
                'simplesaml',
            ),
            'saml2int.conf.php',
            'simplesaml',
        );
    }


    public function testProcessResponseRequireSignedResponseFeatureFlag(): void
    {
        $idpMetadataArray = $this->acmeeMetadata;

        // Provide IdP signing key material for signature validation.
        $idpMetadataArray['privatekey'] = PEMCertificatesMock::buildKeysPath(PEMCertificatesMock::PRIVATE_KEY);
        $idpMetadataArray['privatekey_pass'] = PEMCertificatesMock::PASSPHRASE;
        $idpMetadataArray['certificate'] =
            'vendor/simplesamlphp/xml-security/resources/certificates/' . PEMCertificatesMock::CERTIFICATE;

        $idpConfig = Configuration::loadFromArray($idpMetadataArray, $idpMetadataArray['entityid']);
        $spConfig = Configuration::loadFromArray($this->spMetadata, $this->spMetadata['entityID']);

        $expectedRequestId = '_req_1';

        $scd = new SubjectConfirmationData();
        $scd->setNotOnOrAfter(time() + 300);
        $scd->setInResponseTo($expectedRequestId);

        $sc = new SubjectConfirmation();
        $sc->setMethod(Constants::CM_BEARER);
        $sc->setSubjectConfirmationData($scd);

        // Mock the Assertion to avoid saml2-legacy encrypted-attributes state issues.
        $assertionIssuer = new Issuer();
        $assertionIssuer->setValue($this->acmeeEntityId);

        $assertion = $this->createStub(Assertion::class);
        $assertion->method('hasEncryptedAttributes')->willReturn(false);
        $assertion->method('isNameIdEncrypted')->willReturn(false);
        $assertion->method('getIssuer')->willReturn($assertionIssuer);
        $assertion->method('getSubjectConfirmation')->willReturn([$sc]);

        // Timing/audience checks in Message::processAssertion()
        $assertion->method('getNotBefore')->willReturn(null);
        $assertion->method('getNotOnOrAfter')->willReturn(time() + 300);
        $assertion->method('getSessionNotOnOrAfter')->willReturn(null);
        $assertion->method('getValidAudiences')->willReturn(null);

        // Signature validation in Message::checkSign() calls $assertion->validate($key)
        $assertion->method('validate')->willReturn(true);

        $responseIssuer = new Issuer();
        $responseIssuer->setValue($this->acmeeEntityId);

        $response = new Response();
        $response->setIssuer($responseIssuer);
        $response->setInResponseTo($expectedRequestId);
        $response->setAssertions([$assertion]);

        $process = function () use ($spConfig, $idpConfig, $response, $expectedRequestId): array {
            return Message::processResponse(
                $spConfig,
                $idpConfig,
                $response,
                $expectedRequestId,
                false,
                $this->acmeeEntityId,
            );
        };

        // 1) Flag OFF: allow unsigned Response as long as assertions/signatures validate.
        $this->setRequireSignedResponseFlag(false);
        $assertions = $process();
        $this->assertCount(1, $assertions);

        // 2) Flag ON: reject unsigned Response immediately.
        $this->setRequireSignedResponseFlag(true);
        $this->expectException(SSP_Error\Exception::class);
        $this->expectExceptionMessage('Response must be signed.');
        $process();
    }
}
