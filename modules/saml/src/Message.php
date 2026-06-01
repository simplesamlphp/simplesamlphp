<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml;

use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Assertion;
use SAML2\AuthnRequest;
use SAML2\Constants;
use SAML2\EncryptedAssertion;
use SAML2\LogoutRequest;
use SAML2\LogoutResponse;
use SAML2\Response;
use SAML2\SignedElement;
use SAML2\StatusResponse;
use SAML2\XML\ds\KeyInfo;
use SAML2\XML\ds\X509Certificate;
use SAML2\XML\ds\X509Data;
use SAML2\XML\saml\Issuer;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;
use SimpleSAML\Error as SSP_Error;
use SimpleSAML\Logger;
use SimpleSAML\Utils;

/**
 * Common code for building SAML 2 messages based on the available metadata.
 *
 * @package SimpleSAMLphp
 */
class Message
{
    /**
     * Add signature key and sender certificate to an element (Message or Assertion).
     *
     * @param \SimpleSAML\Configuration $srcMetadata The metadata of the sender.
     * @param \SimpleSAML\Configuration $dstMetadata The metadata of the recipient.
     * @param \SAML2\SignedElement $element The element we should add the data to.
     */
    public static function addSign(
        Configuration $srcMetadata,
        Configuration $dstMetadata,
        SignedElement $element,
    ): void {
        $dstPrivateKey = $dstMetadata->getOptionalString('signature.privatekey', null);
        $cryptoUtils = new Utils\Crypto();

        if ($dstPrivateKey !== null) {
            /** @var array $keyArray */
            $keyArray = $cryptoUtils->loadPrivateKey($dstMetadata, true, 'signature.');
            $certArray = $cryptoUtils->loadPublicKey($dstMetadata, false, 'signature.');
        } else {
            /** @var array $keyArray */
            $keyArray = $cryptoUtils->loadPrivateKey($srcMetadata, true);
            $certArray = $cryptoUtils->loadPublicKey($srcMetadata, false);
        }

        $algo = $dstMetadata->getOptionalString('signature.algorithm', null);
        if ($algo === null) {
            $algo = $srcMetadata->getOptionalString('signature.algorithm', XMLSecurityKey::RSA_SHA256);
        }

        $privateKey = new XMLSecurityKey($algo, ['type' => 'private']);
        if (array_key_exists('password', $keyArray)) {
            $privateKey->passphrase = $keyArray['password'];
        }
        $privateKey->loadKey($keyArray['PEM'], false);

        $element->setSignatureKey($privateKey);

        if ($certArray === null) {
            // we don't have a certificate to add
            return;
        }

        if (!array_key_exists('PEM', $certArray)) {
            // we have a public key with only a fingerprint
            return;
        }

        $element->setCertificates([$certArray['PEM']]);
    }


    /**
     * Add signature key and and senders certificate to message.
     *
     * @param \SimpleSAML\Configuration $srcMetadata The metadata of the sender.
     * @param \SimpleSAML\Configuration $dstMetadata The metadata of the recipient.
     * @param \SAML2\Message $message The message we should add the data to.
     */
    private static function addRedirectSign(
        Configuration $srcMetadata,
        Configuration $dstMetadata,
        \SAML2\Message $message,
    ): void {
        $signingEnabled = null;
        if ($message instanceof LogoutRequest || $message instanceof LogoutResponse) {
            $signingEnabled = $srcMetadata->getOptionalBoolean('sign.logout', null);
            if ($signingEnabled === null) {
                $signingEnabled = $dstMetadata->getOptionalBoolean('sign.logout', null);
            }
        } elseif ($message instanceof AuthnRequest) {
            $signingEnabled = $srcMetadata->getOptionalBoolean('sign.authnrequest', null);
            if ($signingEnabled === null) {
                $signingEnabled = $dstMetadata->getOptionalBoolean('sign.authnrequest', null);
            }
        }

        if ($signingEnabled === null) {
            $signingEnabled = $dstMetadata->getOptionalBoolean('redirect.sign', null);
            if ($signingEnabled === null) {
                $signingEnabled = $srcMetadata->getOptionalBoolean('redirect.sign', false);
            }
        }
        if (!$signingEnabled) {
            return;
        }

        self::addSign($srcMetadata, $dstMetadata, $message);
    }


    /**
     * Check the signature on a SAML2 message or assertion.
     *
     * @param \SimpleSAML\Configuration $srcMetadata The metadata of the sender.
     * @param \SAML2\SignedElement $element Either a \SAML2\Response or a \SAML2\Assertion.
     * @return bool True if the signature is correct, false otherwise.
     *
     * @throws \SimpleSAML\Error\Exception if there is not certificate in the metadata for the entity.
     * @throws \Exception if the signature validation fails with an exception.
     */
    public static function checkSign(Configuration $srcMetadata, SignedElement $element): bool
    {
        // find the public key that should verify signatures by this entity
        $keys = $srcMetadata->getPublicKeys('signing');
        if (!empty($keys)) {
            $pemKeys = [];
            foreach ($keys as $key) {
                switch ($key['type']) {
                    case 'X509Certificate':
                        $pemKeys[] = "-----BEGIN CERTIFICATE-----\n" .
                            chunk_split($key['X509Certificate'], 64) .
                            "-----END CERTIFICATE-----\n";
                        break;
                    default:
                        Logger::debug('Skipping unknown key type: ' . $key['type']);
                }
            }
        } else {
            throw new SSP_Error\Exception(
                'Missing certificate in metadata for ' .
                var_export($srcMetadata->getString('entityid'), true),
            );
        }

        Logger::debug('Has ' . count($pemKeys) . ' candidate keys for validation.');

        $lastException = null;
        foreach ($pemKeys as $i => $pem) {
            $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'public']);
            $key->loadKey($pem);

            try {
                // make sure that we have a valid signature on either the response or the assertion
                $res = $element->validate($key);
                if ($res) {
                    Logger::debug('Validation with key #' . $i . ' succeeded.');
                    return true;
                }
                Logger::debug('Validation with key #' . $i . ' failed without exception.');
            } catch (\Exception $e) {
                Logger::debug('Validation with key #' . $i . ' failed with exception: ' . $e->getMessage());

                $issuerValue = null;
                if (method_exists($element, 'getIssuer')) {
                    $issuer = $element->getIssuer();
                    if ($issuer instanceof Issuer) {
                        $issuerValue = $issuer->getValue();
                    } elseif (is_string($issuer)) {
                        $issuerValue = $issuer;
                    }
                }

                // Clone the exception and improve the message
                $lastException = new SSP_Error\Error(
                    [
                        SSP_Error\ErrorCodes::NOTVALIDCERTSIGNATURE,
                        '%MESSAGE%' => (new SSP_Error\ErrorCodes())->getMessage(SSP_Error\ErrorCodes::NOTVALIDCERTSIGNATURE),
                        '%ELEMENT%' => get_class($element),
                        '%ISSUER%' => $issuerValue ?? '',
                        '%ENTITYID%' => $srcMetadata->getString('entityid'),
                    ],
                    $e->getPrevious(),
                );
            }
        }

        // we were unable to validate the signature with any of our keys
        if ($lastException !== null) {
            throw $lastException;
        } else {
            return false;
        }
    }


    /**
     * Check signature on a SAML2 message if enabled.
     *
     * @param \SimpleSAML\Configuration $srcMetadata The metadata of the sender.
     * @param \SimpleSAML\Configuration $dstMetadata The metadata of the recipient.
     * @param \SAML2\Message $message The message we should check the signature on.
     * @return bool Whether or not the message was validated.
     *
     * @throws \SimpleSAML\Error\Exception if message validation is enabled, but there is no signature in the message.
     */
    public static function validateMessage(
        Configuration $srcMetadata,
        Configuration $dstMetadata,
        \SAML2\Message $message,
    ): bool {
        $enabled = null;
        if ($message instanceof LogoutRequest || $message instanceof LogoutResponse) {
            $enabled = $srcMetadata->getOptionalBoolean('validate.logout', null);
            if ($enabled === null) {
                $enabled = $dstMetadata->getOptionalBoolean('validate.logout', null);
            }
        } elseif ($message instanceof AuthnRequest) {
            $enabled = $srcMetadata->getOptionalBoolean('validate.authnrequest', null);
            if ($enabled === null) {
                $enabled = $dstMetadata->getOptionalBoolean('validate.authnrequest', null);
            }
        }

        // If not specifically set to false, the signature must be checked to conform to SAML2INT
        if (
            (isset($_REQUEST['Signature'])
                || $message->isMessageConstructedWithSignature() === true)
            && ($enabled !== false)
        ) {
            $enabled = true;
        } elseif ($enabled === null) {
            $enabled = $srcMetadata->getOptionalBoolean('redirect.validate', null);
            if ($enabled === null) {
                $enabled = $dstMetadata->getOptionalBoolean('redirect.validate', false);
            }
        }

        if (!$enabled) {
            return false;
        } elseif (!self::checkSign($srcMetadata, $message)) {
            throw new SSP_Error\Exception(
                'Validation of received messages enabled, but no signature found on message.',
            );
        }
        return true;
    }


    /**
     * Retrieve the decryption keys from metadata.
     *
     * @param \SimpleSAML\Configuration $srcMetadata The metadata of the sender (IdP).
     * @param \SimpleSAML\Configuration $dstMetadata The metadata of the recipient (SP).
     * @param string|null $encryptionMethod
     *   The EncryptionMethod from the assertion.
     *
     * @return array Array of decryption keys.
     */
    public static function getDecryptionKeys(
        Configuration $srcMetadata,
        Configuration $dstMetadata,
        $encryptionMethod = null,
    ): array {
        $sharedKey = $srcMetadata->getOptionalString('sharedkey', null);
        if ($sharedKey !== null) {
            if ($encryptionMethod !== null) {
                // @TODO: for saml2v5 replace this line
                //$algo = $encryptionMethod->getAlgorithm();
                $algo = $encryptionMethod;
            } else {
                $algo = $srcMetadata->getOptionalString('sharedkey_algorithm', null);
                if ($algo === null) {
                    // If no algorithm is supplied or configured, use a sane default as a last resort
                    $algo = $dstMetadata->getOptionalString('sharedkey_algorithm', XMLSecurityKey::AES128_GCM);
                }
            }

            $key = new XMLSecurityKey($algo);
            $key->loadKey($sharedKey);
            return [$key];
        }

        $keys = [];
        $cryptoUtils = new Utils\Crypto();

        // load the new private key if it exists
        $keyArray = $cryptoUtils->loadPrivateKey($dstMetadata, false, 'new_');
        if ($keyArray !== null) {
            assert::keyExists($keyArray, 'PEM');

            $key = new XMLSecurityKey(XMLSecurityKey::RSA_1_5, ['type' => 'private']);
            if (array_key_exists('password', $keyArray)) {
                $key->passphrase = $keyArray['password'];
            }
            $key->loadKey($keyArray['PEM']);
            $keys[] = $key;
        }

        /**
         * find the existing private key
         *
         * @var array $keyArray  Because the second param is true
         */
        $keyArray = $cryptoUtils->loadPrivateKey($dstMetadata, true);
        Assert::keyExists($keyArray, 'PEM');

        $key = new XMLSecurityKey(XMLSecurityKey::RSA_1_5, ['type' => 'private']);
        if (array_key_exists('password', $keyArray)) {
            $key->passphrase = $keyArray['password'];
        }
        $key->loadKey($keyArray['PEM']);
        $keys[] = $key;

        return $keys;
    }


    /**
     * Retrieve blacklisted algorithms.
     *
     * Remote configuration overrides local configuration.
     *
     * @param \SimpleSAML\Configuration $srcMetadata The metadata of the sender.
     * @param \SimpleSAML\Configuration $dstMetadata The metadata of the recipient.
     *
     * @return array  Array of blacklisted algorithms.
     */
    public static function getBlacklistedAlgorithms(
        Configuration $srcMetadata,
        Configuration $dstMetadata,
    ): array {
        $blacklist = $srcMetadata->getOptionalArray('encryption.blacklisted-algorithms', null);
        if ($blacklist === null) {
            $blacklist = $dstMetadata->getOptionalArray('encryption.blacklisted-algorithms', [XMLSecurityKey::RSA_1_5]);
        }
        return $blacklist;
    }


    /**
     * Decrypt an assertion.
     *
     * @param \SimpleSAML\Configuration $srcMetadata The metadata of the sender (IdP).
     * @param \SimpleSAML\Configuration $dstMetadata The metadata of the recipient (SP).
     * @param \SAML2\Assertion|\SAML2\EncryptedAssertion $assertion The assertion we are decrypting.
     *
     * @return \SAML2\Assertion The assertion.
     *
     * @throws \SimpleSAML\Error\Exception if encryption is enabled but the assertion is not encrypted, or if we cannot
     * get the decryption keys.
     * @throws \Exception if decryption fails for whatever reason.
     */
    private static function decryptAssertion(
        Configuration $srcMetadata,
        Configuration $dstMetadata,
        Assertion|EncryptedAssertion $assertion,
    ): Assertion {
        if ($assertion instanceof Assertion) {
            $encryptAssertion = $srcMetadata->getOptionalBoolean('assertion.encryption', null);
            if ($encryptAssertion === null) {
                $encryptAssertion = $dstMetadata->getOptionalBoolean('assertion.encryption', false);
            }
            if ($encryptAssertion) {
                /* The assertion was unencrypted, but we have encryption enabled. */
                throw new \Exception('Received unencrypted assertion, but encryption was enabled.');
            }

            return $assertion;
        }

        try {
            // @todo Enable this code for saml2v5 to automatically determine encryption algorithm
            //$encryptionMethod = $assertion->getEncryptedData()->getEncryptionMethod();
            //$keys = self::getDecryptionKeys($srcMetadata, $dstMetadata, $encryptionMethod);

            $encryptionMethod = null;
            $keys = self::getDecryptionKeys($srcMetadata, $dstMetadata, $encryptionMethod);
        } catch (\Exception $e) {
            throw new SSP_Error\Exception('Error decrypting assertion: ' . $e->getMessage());
        }

        $blacklist = self::getBlacklistedAlgorithms($srcMetadata, $dstMetadata);

        $lastException = null;
        foreach ($keys as $i => $key) {
            try {
                $ret = $assertion->getAssertion($key, $blacklist);
                Logger::debug('Decryption with key #' . $i . ' succeeded.');
                return $ret;
            } catch (\Exception $e) {
                Logger::debug('Decryption with key #' . $i . ' failed with exception: ' . $e->getMessage());
                $lastException = $e;
            }
        }

        /**
         * The annotation below is not working - See vimeo/psalm#1909
         * @psalm-suppress InvalidThrow
         * @var \Exception $lastException
         */
        throw $lastException;
    }


    /**
     * Decrypt any encrypted attributes in an assertion.
     *
     * @param \SimpleSAML\Configuration $srcMetadata The metadata of the sender (IdP).
     * @param \SimpleSAML\Configuration $dstMetadata The metadata of the recipient (SP).
     * @param \SAML2\Assertion|\SAML2\Assertion $assertion The assertion containing any possibly encrypted attributes.
     *
     *
     * @throws \SimpleSAML\Error\Exception if we cannot get the decryption keys or decryption fails.
     */
    private static function decryptAttributes(
        Configuration $srcMetadata,
        Configuration $dstMetadata,
        Assertion &$assertion,
    ): void {
        if (!$assertion->hasEncryptedAttributes()) {
            return;
        }

        try {
            $keys = self::getDecryptionKeys($srcMetadata, $dstMetadata);
        } catch (\Exception $e) {
            throw new SSP_Error\Exception('Error decrypting attributes: ' . $e->getMessage());
        }

        $blacklist = self::getBlacklistedAlgorithms($srcMetadata, $dstMetadata);

        $error = true;
        foreach ($keys as $i => $key) {
            try {
                $assertion->decryptAttributes($key, $blacklist);
                Logger::debug('Attribute decryption with key #' . $i . ' succeeded.');
                $error = false;
                break;
            } catch (\Exception $e) {
                Logger::debug('Attribute decryption failed with exception: ' . $e->getMessage());
            }
        }
        if ($error) {
            throw new SSP_Error\Exception('Could not decrypt the attributes');
        }
    }


    /**
     * Retrieve the status code of a response as a \SimpleSAML\Module\saml\Error.
     *
     * @param \SAML2\StatusResponse $response The response.
     *
     * @return \SimpleSAML\Module\saml\Error The error.
     */
    public static function getResponseError(StatusResponse $response): \SimpleSAML\Module\saml\Error
    {
        $status = $response->getStatus();
        return new \SimpleSAML\Module\saml\Error($status['Code'], $status['SubCode'], $status['Message']);
    }


    /**
     * Build an authentication request based on information in the metadata.
     *
     * @param \SimpleSAML\Configuration $spMetadata The metadata of the service provider.
     * @param \SimpleSAML\Configuration $idpMetadata The metadata of the identity provider.
     * @return \SAML2\AuthnRequest An authentication request object.
     */
    public static function buildAuthnRequest(
        Configuration $spMetadata,
        Configuration $idpMetadata,
    ): AuthnRequest {
        $ar = new AuthnRequest();

        // get the NameIDPolicy to apply. IdP metadata has precedence.
        $nameIdPolicy = null;
        if ($idpMetadata->hasValue('NameIDPolicy')) {
            $nameIdPolicy = $idpMetadata->getValue('NameIDPolicy');
        } elseif ($spMetadata->hasValue('NameIDPolicy')) {
            $nameIdPolicy = $spMetadata->getValue('NameIDPolicy');
        }

        $policy = Utils\Config\Metadata::parseNameIdPolicy($nameIdPolicy);
        // empty array signals not to set any NameIdPolicy element
        if ($policy !== []) {
            $ar->setNameIdPolicy($policy);
        }

        $ar->setForceAuthn($spMetadata->getOptionalBoolean('ForceAuthn', false));
        $ar->setIsPassive($spMetadata->getOptionalBoolean('IsPassive', false));

        $protbind = $spMetadata->getOptionalValueValidate('ProtocolBinding', [
            Constants::BINDING_HTTP_POST,
            Constants::BINDING_HOK_SSO,
            Constants::BINDING_HTTP_ARTIFACT,
            Constants::BINDING_HTTP_REDIRECT,
        ], Constants::BINDING_HTTP_POST);

        // Shoaib: setting the appropriate binding based on parameter in sp-metadata defaults to HTTP_POST
        $ar->setProtocolBinding($protbind);
        $issuer = new Issuer();
        $issuer->setValue($spMetadata->getString('entityID'));
        $ar->setIssuer($issuer);
        $ar->setAssertionConsumerServiceIndex(
            $spMetadata->getOptionalInteger('AssertionConsumerServiceIndex', null),
        );
        $ar->setAttributeConsumingServiceIndex(
            $spMetadata->getOptionalInteger('AttributeConsumingServiceIndex', null),
        );

        if ($spMetadata->hasValue('AuthnContextClassRef')) {
            $accr = $spMetadata->getArrayizeString('AuthnContextClassRef');
            $comp = $spMetadata->getOptionalValueValidate('AuthnContextComparison', [
                Constants::COMPARISON_EXACT,
                Constants::COMPARISON_MINIMUM,
                Constants::COMPARISON_MAXIMUM,
                Constants::COMPARISON_BETTER,
            ], Constants::COMPARISON_EXACT);
            $ar->setRequestedAuthnContext(['AuthnContextClassRef' => $accr, 'Comparison' => $comp]);
        }

        self::addRedirectSign($spMetadata, $idpMetadata, $ar);

        return $ar;
    }


    /**
     * Build a logout request based on information in the metadata.
     *
     * @param \SimpleSAML\Configuration $srcMetadata The metadata of the sender.
     * @param \SimpleSAML\Configuration $dstMetadata The metadata of the recipient.
     * @return \SAML2\LogoutRequest A logout request object.
     */
    public static function buildLogoutRequest(
        Configuration $srcMetadata,
        Configuration $dstMetadata,
    ): LogoutRequest {
        $lr = new LogoutRequest();
        $issuer = new Issuer();
        $issuer->setValue($srcMetadata->getString('entityid'));
        $issuer->setFormat(Constants::NAMEID_ENTITY);
        $lr->setIssuer($issuer);

        self::addRedirectSign($srcMetadata, $dstMetadata, $lr);

        return $lr;
    }


    /**
     * Build a logout response based on information in the metadata.
     *
     * @param \SimpleSAML\Configuration $srcMetadata The metadata of the sender.
     * @param \SimpleSAML\Configuration $dstMetadata The metadata of the recipient.
     * @return \SAML2\LogoutResponse A logout response object.
     */
    public static function buildLogoutResponse(
        Configuration $srcMetadata,
        Configuration $dstMetadata,
    ): LogoutResponse {
        $lr = new LogoutResponse();
        $issuer = new Issuer();
        $issuer->setValue($srcMetadata->getString('entityid'));
        $issuer->setFormat(Constants::NAMEID_ENTITY);
        $lr->setIssuer($issuer);

        self::addRedirectSign($srcMetadata, $dstMetadata, $lr);

        return $lr;
    }


    /**
     * Process a response message.
     *
     * If the response is an error response, we will throw a \SimpleSAML\Module\saml\Error exception with the error.
     *
     * @param \SimpleSAML\Configuration $spMetadata The metadata of the service provider.
     * @param \SimpleSAML\Configuration $idpMetadata The metadata of the identity provider.
     * @param \SAML2\Response $response The response.
     * @param string|null $expectedRequestId
     *    The AuthnRequest ID that this Response is expected to correlate with (solicited / SP-initiated flow).
     *    Caller guarantee: when $isUnsolicited is false, this MUST be the original AuthnRequest ID the SP created.
     * @param bool $isUnsolicited
     *    Whether the Response is processed as unsolicited (IdP-initiated).
     * @param string|null $expectedIssuer
     *    If set, the Response (or Assertion, if Response Issuer is missing) MUST be issued by this entityID.
     *    This prevents misuse where callers validate a Response using metadata for a different IdP than the one
     *    the SP state was created for.
     *
     * @return array Array with \SAML2\Assertion objects, containing valid assertions from the response.
     *
     * @throws \SimpleSAML\Error\Exception if there are no assertions in the response.
     * @throws \Exception if the destination of the response does not match the current URL.
     */
    public static function processResponse(
        Configuration $spMetadata,
        Configuration $idpMetadata,
        Response $response,
        ?string $expectedRequestId = null,
        bool $isUnsolicited = false,
        ?string $expectedIssuer = null,
    ): array {
        if (!$response->isSuccess()) {
            throw self::getResponseError($response);
        }

        // validate Response-element destination
        $httpUtils = new \SimpleSAML\Utils\HTTP();
        $currentURL = $httpUtils->getSelfURLNoQuery();
        $msgDestination = $response->getDestination();
        if ($msgDestination !== null && $msgDestination !== $currentURL) {
            throw new \Exception(sprintf(
                'Destination in response doesn\'t match the current URL. Destination is "%s", current URL is "%s".',
                $msgDestination,
                $currentURL,
            ));
        }

        // Resolve issuer (Response Issuer MAY be missing; fall back to first unencrypted Assertion issuer)
        $issuer = $response->getIssuer();
        if ($issuer === null) {
            foreach ($response->getAssertions() as $a) {
                if ($a instanceof Assertion) {
                    $issuer = $a->getIssuer();
                    break;
                }
            }
        }
        $issuerValue = $issuer?->getValue();

        // Expected issuer binding (when provided, issuer must be resolvable and must match)
        if ($expectedIssuer !== null) {
            if ($issuerValue === null) {
                throw new SSP_Error\Exception(sprintf(
                    'Missing issuer in Response/Assertion; cannot enforce expected issuer %s.',
                    var_export($expectedIssuer, true),
                ));
            }

            if ($issuerValue !== $expectedIssuer) {
                throw new SSP_Error\Exception(sprintf(
                    'Issuer mismatch. Expected %s, got %s.',
                    var_export($expectedIssuer, true),
                    var_export($issuerValue, true),
                ));
            }
        }

        $responseSigned = self::checkSign($idpMetadata, $response);

        // Project policy: Response MUST be signed (SAML2Int)
        $saml2IntConfig = Configuration::getOptionalConfig('saml2int.conf.php');
        $requireSignedResponse = $saml2IntConfig->getOptionalBoolean('response.require_signed', false);
        if ($requireSignedResponse && $responseSigned !== true) {
            throw new SSP_Error\Exception('Response must be signed.');
        }

        $responseInResponseTo = $response->getInResponseTo();

        // Hard policy:
        // - unsolicited: MUST NOT contain Response/@InResponseTo
        // - solicited: MUST contain Response/@InResponseTo and MUST match the expected request ID
        if ($isUnsolicited) {
            if ($responseInResponseTo !== null) {
                throw new SSP_Error\Exception(sprintf(
                    'Unsolicited Response MUST NOT contain InResponseTo. Got: %s',
                    var_export($responseInResponseTo, true),
                ));
            }
        } else {
            if ($expectedRequestId === null || $expectedRequestId === '') {
                throw new SSP_Error\Exception('Missing expected request ID for solicited Response validation.');
            }
            if ($responseInResponseTo === null) {
                throw new SSP_Error\Exception('Solicited Response must contain InResponseTo.');
            }
            if ($responseInResponseTo !== $expectedRequestId) {
                throw new SSP_Error\Exception(sprintf(
                    'Response InResponseTo does not match expected request ID. Expected %s, got %s.',
                    var_export($expectedRequestId, true),
                    var_export($responseInResponseTo, true),
                ));
            }
        }

        /*
         * When we get this far, the response itself is valid.
         * We only need to check signatures and conditions of the response.
         */
        $assertion = $response->getAssertions();
        if (empty($assertion)) {
            throw new SSP_Error\Exception('No assertions found in response from IdP.');
        }

        $ret = [];
        foreach ($assertion as $a) {
            $ret[] = self::processAssertion(
                $spMetadata,
                $idpMetadata,
                $response,
                $a,
                $responseSigned,
                $expectedRequestId,
                $isUnsolicited,
            );
        }

        return $ret;
    }


    /**
     * Process an assertion in a response.
     *
     * @param \SimpleSAML\Configuration $spMetadata The metadata of the service provider.
     * @param \SimpleSAML\Configuration $idpMetadata The metadata of the identity provider.
     * @param \SAML2\Response $response The response containing the assertion.
     * @param \SAML2\Assertion|\SAML2\EncryptedAssertion $assertion The assertion.
     * @param bool $responseSigned Whether the response is signed.
     * @param string|null $expectedRequestId The AuthnRequest ID we expect this response to be correlated with.
     * @param bool $isUnsolicited Whether this response is being processed as unsolicited (IdP-initiated).
     *
     * @return \SAML2\Assertion The assertion, if it is valid.
     *
     * @throws \SimpleSAML\Error\Exception if an error occurs while trying to validate the assertion, or if a assertion
     * is not signed and it should be, or if we are unable to decrypt the NameID due to a local failure (missing or
     * invalid decryption key).
     * @throws \Exception if we couldn't decrypt the NameID for unexpected reasons.
     */
    private static function processAssertion(
        Configuration $spMetadata,
        Configuration $idpMetadata,
        Response $response,
        Assertion|EncryptedAssertion $assertion,
        bool $responseSigned,
        ?string $expectedRequestId,
        bool $isUnsolicited,
    ): Assertion {
        $assertion = self::decryptAssertion($idpMetadata, $spMetadata, $assertion);
        self::decryptAttributes($idpMetadata, $spMetadata, $assertion);

        if (!self::checkSign($idpMetadata, $assertion)) {
            if (!$responseSigned) {
                throw new SSP_Error\Exception('Neither the assertion nor the response was signed.');
            }
        } // at least one valid signature found

        $httpUtils = new Utils\HTTP();
        $currentURL = $httpUtils->getSelfURLNoQuery();
        $now = time();

        // check various properties of the assertion
        $config = Configuration::getInstance();
        $allowed_clock_skew = $config->getOptionalInteger('assertion.allowed_clock_skew', 180);
        $allowed_clock_skew = max(180, min(300, $allowed_clock_skew));

        /**
         * Validate assertion time constraints and audience restriction.
         *
         * Uses the following extracted assertion values:
         * - Assertion/Conditions/@NotBefore (via {@see \SAML2\Assertion::getNotBefore()})
         * - Assertion/Conditions/@NotOnOrAfter (via {@see \SAML2\Assertion::getNotOnOrAfter()})
         * - Assertion/AuthnStatement/@SessionNotOnOrAfter (via {@see \SAML2\Assertion::getSessionNotOnOrAfter()})
         * - Assertion/Conditions/AudienceRestriction/Audience (via {@see \SAML2\Assertion::getValidAudiences()})
         *
         * Uses the following local values:
         * - $now: current Unix timestamp used as the comparison baseline
         * - $allowed_clock_skew: allowed clock skew in seconds
         *
         * @throws \SimpleSAML\Error\Exception When the assertion is not yet valid, is expired, the session is expired,
         *   or the SP entityID is not an allowed audience.
         */
        self::validateAssertionTimingAndAudience(
            $assertion,
            $spMetadata,
            $now,
            $allowed_clock_skew,
        );

        // is SSO with HoK enabled? IdP remote metadata overwrites SP metadata configuration
        $hok = $spMetadata->getOptionalBoolean('saml20.hok.assertion', false);

        $found = false;
        $lastError = 'No SubjectConfirmation element in Subject.';

        foreach ($assertion->getSubjectConfirmation() as $sc) {
            try {
                self::validateSubjectConfirmation(
                    $sc,
                    $hok,
                    $httpUtils,
                    $currentURL,
                    $response,
                    $now,
                    $expectedRequestId,
                    $isUnsolicited,
                );

                $found = true;
                break;
            } catch (\SimpleSAML\Error\Exception $e) {
                $lastError = $e->getMessage();
                continue;
            }
        }

        if (!$found) {
            throw new SSP_Error\Exception('Error validating SubjectConfirmation in Assertion: ' . $lastError);
        }
        // as far as we can tell, the assertion is valid

        // decrypt the NameID element if it is encrypted
        if ($assertion->isNameIdEncrypted()) {
            try {
                $keys = self::getDecryptionKeys($idpMetadata, $spMetadata);
            } catch (\Exception $e) {
                throw new SSP_Error\Exception('Error decrypting NameID: ' . $e->getMessage());
            }

            $blacklist = self::getBlacklistedAlgorithms($idpMetadata, $spMetadata);

            $lastException = null;
            foreach ($keys as $i => $key) {
                try {
                    $assertion->decryptNameId($key, $blacklist);
                    Logger::debug('Decryption with key #' . $i . ' succeeded.');
                    $lastException = null;
                    break;
                } catch (\Exception $e) {
                    Logger::debug('Decryption with key #' . $i . ' failed with exception: ' . $e->getMessage());
                    $lastException = $e;
                }
            }
            if ($lastException !== null) {
                throw $lastException;
            }
        }

        return $assertion;
    }


    /**
     * Validates the timing constraints and audience restrictions in the SAML assertion.
     *
     * @param \SAML2\Assertion $assertion The SAML assertion to validate.
     * @param \SimpleSAML\Configuration $spMetadata The metadata of the service provider.
     * @param int $now The current timestamp used for validation.
     * @param int $allowedClockSkew The maximum allowed clock skew, in seconds, for timing validations.
     *
     * @throws \SimpleSAML\Error\Exception If the assertion is invalid due to timing constraints or audience issues.
     */
    private static function validateAssertionTimingAndAudience(
        Assertion $assertion,
        Configuration $spMetadata,
        int $now,
        int $allowedClockSkew,
    ): void {
        $timingChecks = [
            [
                'value' => $assertion->getNotBefore(),
                'invalid' => static fn (int $value): bool => $value > ($now + $allowedClockSkew),
                // phpcs:ignore Generic.Files.LineLength
                'message' => 'Received an assertion that is valid in the future. Check clock synchronization on IdP and SP.',
            ],
            [
                'value' => $assertion->getNotOnOrAfter(),
                'invalid' => static fn (int $value): bool => $value <= ($now - $allowedClockSkew),
                'message' => 'Received an assertion that has expired. Check clock synchronization on IdP and SP.',
            ],
            [
                'value' => $assertion->getSessionNotOnOrAfter(),
                'invalid' => static fn (int $value): bool => $value <= ($now - $allowedClockSkew),
                // phpcs:ignore Generic.Files.LineLength
                'message' => 'Received an assertion with a session that has expired. Check clock synchronization on IdP and SP.',
            ],
        ];

        foreach ($timingChecks as $check) {
            $value = $check['value'];
            if ($value === null) {
                continue;
            }

            /** @var \Closure(int):bool $isInvalid */
            $isInvalid = $check['invalid'];
            if ($isInvalid($value)) {
                throw new SSP_Error\Exception($check['message']);
            }
        }

        $validAudiences = $assertion->getValidAudiences();
        if ($validAudiences === null) {
            return;
        }

        $spEntityId = $spMetadata->getString('entityid');
        if (!in_array($spEntityId, $validAudiences, true)) {
            $candidates = '[' . implode('], [', $validAudiences) . ']';
            throw new SSP_Error\Exception(sprintf(
                'This SP [%s] is not a valid audience for the assertion. Candidates were: %s',
                $spEntityId,
                $candidates,
            ));
        }
    }


    /**
     * Validates a SubjectConfirmation element in a SAML assertion and checks whether it is valid
     * according to the SAML2 specification and binding-specific policies.
     *
     * This method handles validation of Bearer and Holder-of-Key profiles, ensuring criteria such as:
     * - The SubjectConfirmation method is valid for the current SP configuration.
     * - Time constraints like NotBefore and NotOnOrAfter are met.
     * - The Recipient URL and InResponseTo values are correctly set when applicable.
     *
     * @param \SAML2\XML\saml\SubjectConfirmation $sc
     *   The SubjectConfirmation element to validate.
     * @param bool $hok
     *   Whether the Holder-of-Key (HoK) profile is enabled for this SP.
     * @param \SimpleSAML\Utils\HTTP $httpUtils
     *   Utility for accessing HTTP-related data.
     * @param string $currentURL
     *   The current URL used to validate the recipient of the assertion.
     * @param \SAML2\Response $response
     *   The SAML response containing the assertion.
     * @param int $now
     *   The current timestamp for time-based validations.
     * @param string|null $expectedRequestId
     *   The ID of the original AuthnRequest this response is related to, or null for unsolicited responses.
     * @param bool $isUnsolicited
     *   Whether the response was unsolicited (IdP-initiated).
     *
     * @throws \SimpleSAML\Error\Exception When the SubjectConfirmation is invalid.
     */
    private static function validateSubjectConfirmation(
        \SAML2\XML\saml\SubjectConfirmation $sc,
        bool $hok,
        Utils\HTTP $httpUtils,
        string $currentURL,
        Response $response,
        int $now,
        ?string $expectedRequestId,
        bool $isUnsolicited,
    ): void {
        $method = $sc->getMethod();
        $scd = $sc->getSubjectConfirmationData();

        $validSCMethods = [Constants::CM_BEARER, Constants::CM_HOK, Constants::CM_VOUCHES];

        $rejectReason = match (true) {
            !in_array($method, $validSCMethods, true)
            => sprintf('Invalid Method on SubjectConfirmation: %s', var_export($method, true)),

            $method === Constants::CM_BEARER && $hok
            => 'Bearer SubjectConfirmation received, but Holder-of-Key SubjectConfirmation needed',

            $method === Constants::CM_HOK && !$hok
            => 'Holder-of-Key SubjectConfirmation received, but the Holder-of-Key profile is not enabled.',

            $scd === null
            => 'No SubjectConfirmationData provided',

            default
            => null,
        };

        if ($rejectReason !== null) {
            throw new SSP_Error\Exception($rejectReason);
        }

        /** @var \SAML2\XML\saml\SubjectConfirmationData $scd */
        if ($method === Constants::CM_HOK) {
            $hokError = self::validateHolderOfKeySubjectConfirmationData($scd, $httpUtils);
            if ($hokError !== null) {
                throw new SSP_Error\Exception($hokError);
            }
        }

        $notBefore = $scd->getNotBefore();
        if (is_int($notBefore) && $notBefore > $now + 60) {
            throw new SSP_Error\Exception(
                sprintf('NotBefore in SubjectConfirmationData is in the future: %s', $notBefore),
            );
        }

        $notOnOrAfter = $scd->getNotOnOrAfter();
        if (is_int($notOnOrAfter) && $notOnOrAfter <= $now - 60) {
            throw new SSP_Error\Exception(
                sprintf('NotOnOrAfter in SubjectConfirmationData is in the past: %s', $notOnOrAfter),
            );
        }

        $recipient = $scd->getRecipient();
        if ($recipient !== null && $recipient !== $currentURL) {
            $errorMessage = sprintf(
            // phpcs:ignore Generic.Files.LineLength
                'Recipient in SubjectConfirmationData does not match the current URL. Recipient is %s, current URL is %s.',
                var_export($recipient, true),
                var_export($currentURL, true),
            );
            throw new SSP_Error\Exception($errorMessage);
        }

        $inResponseTo = $scd->getInResponseTo();
        $responseInResponseTo = $response->getInResponseTo();

        // Hard policy: unsolicited responses MUST NOT contain any InResponseTo values.
        if ($isUnsolicited) {
            if ($inResponseTo !== null) {
                throw new SSP_Error\Exception(
                    'Unsolicited Response MUST NOT contain SubjectConfirmationData InResponseTo.',
                );
            }

            if ($responseInResponseTo !== null) {
                throw new SSP_Error\Exception('Unsolicited Response MUST NOT contain Response InResponseTo.');
            }

            return;
        }

        if ($expectedRequestId === null || $expectedRequestId === '') {
            throw new SSP_Error\Exception('Missing expected request ID for solicited Response validation.');
        }

        // Hard policy for solicited responses:
        // SubjectConfirmationData MUST contain InResponseTo and MUST match the request ID, and MUST match Response.
        if ($inResponseTo === null) {
            throw new SSP_Error\Exception('Solicited Response requires SubjectConfirmationData InResponseTo.');
        }

        if ($inResponseTo !== $expectedRequestId) {
            throw new SSP_Error\Exception(sprintf(
                'InResponseTo in SubjectConfirmationData does not match expected request ID. Expected %s, got %s.',
                var_export($expectedRequestId, true),
                var_export($inResponseTo, true),
            ));
        }

        if ($responseInResponseTo === null) {
            throw new SSP_Error\Exception('Solicited Response must contain Response InResponseTo.');
        }

        if ($inResponseTo !== $responseInResponseTo) {
            $errorMessage = sprintf(
            // phpcs:ignore Generic.Files.LineLength
                'InResponseTo in SubjectConfirmationData does not match the Response. Response has %s, SubjectConfirmationData has %s.',
                var_export($responseInResponseTo, true),
                var_export($inResponseTo, true),
            );
            throw new SSP_Error\Exception($errorMessage);
        }
    }


    /**
     * Validate Holder-of-Key (HoK) proof material inside SubjectConfirmationData.
     * Returns null when valid, or an error message when invalid.
     */
    private static function validateHolderOfKeySubjectConfirmationData(
        \SAML2\XML\saml\SubjectConfirmationData $scd,
        Utils\HTTP $httpUtils,
    ): ?string {
        if ($httpUtils->isHTTPS() === false) {
            return 'No HTTPS connection, but required for Holder-of-Key SSO';
        }

        if (isset($_SERVER['SSL_CLIENT_CERT']) && empty($_SERVER['SSL_CLIENT_CERT'])) {
            return 'No client certificate provided during TLS Handshake with SP';
        }

        $clientCert = $_SERVER['SSL_CLIENT_CERT'];
        $pattern = '/^-----BEGIN CERTIFICATE-----([^-]*)^-----END CERTIFICATE-----/m';
        if (!preg_match($pattern, $clientCert, $matches)) {
            return 'Error while looking for client certificate during TLS handshake with SP, ' .
                'the client certificate does not have the expected structure';
        }

        $clientCert = str_replace(["\r", "\n", " "], '', $matches[1]);

        $keyInfo = [];
        foreach ($scd->getInfo() as $thing) {
            if ($thing instanceof KeyInfo) {
                $keyInfo[] = $thing;
            }
        }
        if (count($keyInfo) != 1) {
            return 'Error validating Holder-of-Key assertion: Only one <ds:KeyInfo> element in ' .
                '<SubjectConfirmationData> allowed';
        }

        $x509data = [];
        foreach ($keyInfo[0]->getInfo() as $thing) {
            if ($thing instanceof X509Data) {
                $x509data[] = $thing;
            }
        }
        if (count($x509data) != 1) {
            return 'Error validating Holder-of-Key assertion: Only one <ds:X509Data> element in ' .
                '<ds:KeyInfo> within <SubjectConfirmationData> allowed';
        }

        $x509cert = [];
        foreach ($x509data[0]->getData() as $thing) {
            if ($thing instanceof X509Certificate) {
                $x509cert[] = $thing;
            }
        }
        if (count($x509cert) != 1) {
            return 'Error validating Holder-of-Key assertion: Only one <ds:X509Certificate> element in ' .
                '<ds:X509Data> within <SubjectConfirmationData> allowed';
        }

        $HoKCertificate = $x509cert[0]->getCertificate();
        if ($HoKCertificate !== $clientCert) {
            return 'Provided client certificate does not match the certificate bound to the ' .
                'Holder-of-Key assertion';
        }

        return null;
    }


    /**
     * Retrieve the encryption key for the given entity.
     *
     * @param \SimpleSAML\Configuration $metadata The metadata of the entity.
     *
     * @return \RobRichards\XMLSecLibs\XMLSecurityKey  The encryption key.
     *
     * @throws \SimpleSAML\Error\Exception if there is no supported encryption key in the metadata of this entity.
     */
    public static function getEncryptionKey(Configuration $metadata): XMLSecurityKey
    {
        $sharedKey = $metadata->getOptionalString('sharedkey', null);
        if ($sharedKey !== null) {
            $key = new XMLSecurityKey(XMLSecurityKey::AES128_CBC);
            $key->loadKey($sharedKey);
            return $key;
        }

        $keys = $metadata->getPublicKeys('encryption', true);
        foreach ($keys as $key) {
            switch ($key['type']) {
                case 'X509Certificate':
                    $pemKey = "-----BEGIN CERTIFICATE-----\n" .
                        chunk_split($key['X509Certificate'], 64) .
                        "-----END CERTIFICATE-----\n";
                    $key = new XMLSecurityKey(XMLSecurityKey::RSA_OAEP_MGF1P, ['type' => 'public']);
                    $key->loadKey($pemKey);
                    return $key;
            }
        }

        throw new SSP_Error\Exception('No supported encryption key in ' .
            var_export($metadata->getString('entityid'), true));
    }
}
