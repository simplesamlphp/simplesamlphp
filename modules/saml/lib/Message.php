<?php

use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * Common code for building SAML 2 messages based on the available metadata.
 *
 * @package SimpleSAMLphp
 */
class sspmod_saml_Message
{

    /**
     * Add signature key and sender certificate to an element (Message or Assertion).
     *
     * @param SimpleSAML_Configuration $srcMetadata The metadata of the sender.
     * @param SimpleSAML_Configuration $dstMetadata The metadata of the recipient.
     * @param \SAML2\SignedElement $element The element we should add the data to.
     */
    public static function addSign(
        SimpleSAML_Configuration $srcMetadata,
        SimpleSAML_Configuration $dstMetadata,
        \SAML2\SignedElement $element
    ) {
        $dstPrivateKey = $dstMetadata->getString('signature.privatekey', null);

        if ($dstPrivateKey !== null) {
            $keyArray = SimpleSAML\Utils\Crypto::loadPrivateKey($dstMetadata, true, 'signature.');
            $certArray = SimpleSAML\Utils\Crypto::loadPublicKey($dstMetadata, false, 'signature.');
        } else {
            $keyArray = SimpleSAML\Utils\Crypto::loadPrivateKey($srcMetadata, true);
            $certArray = SimpleSAML\Utils\Crypto::loadPublicKey($srcMetadata, false);
        }

        $algo = $dstMetadata->getString('signature.algorithm', null);
        if ($algo === null) {
            /*
             * In the NIST Special Publication 800-131A, SHA-1 became deprecated for generating
             * new digital signatures in 2011, and will be explicitly disallowed starting the 1st
             * of January, 2014. We'll keep this as a default for the next release and mark it
             * as deprecated, as part of the transition to SHA-256.
             *
             * See http://csrc.nist.gov/publications/nistpubs/800-131A/sp800-131A.pdf for more info.
             *
             * TODO: change default to XMLSecurityKey::RSA_SHA256.
             */
            $algo = $srcMetadata->getString('signature.algorithm', XMLSecurityKey::RSA_SHA1);
        }

        $privateKey = new XMLSecurityKey($algo, array('type' => 'private'));
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

        $element->setCertificates(array($certArray['PEM']));
    }


    /**
     * Add signature key and and senders certificate to message.
     *
     * @param SimpleSAML_Configuration $srcMetadata The metadata of the sender.
     * @param SimpleSAML_Configuration $dstMetadata The metadata of the recipient.
     * @param \SAML2\Message $message The message we should add the data to.
     */
    private static function addRedirectSign(
        SimpleSAML_Configuration $srcMetadata,
        SimpleSAML_Configuration $dstMetadata,
        \SAML2\Message $message
    ) {

        $signingEnabled = null;
        if ($message instanceof \SAML2\LogoutRequest || $message instanceof \SAML2\LogoutResponse) {
            $signingEnabled = $srcMetadata->getBoolean('sign.logout', null);
            if ($signingEnabled === null) {
                $signingEnabled = $dstMetadata->getBoolean('sign.logout', null);
            }
        } elseif ($message instanceof \SAML2\AuthnRequest) {
            $signingEnabled = $srcMetadata->getBoolean('sign.authnrequest', null);
            if ($signingEnabled === null) {
                $signingEnabled = $dstMetadata->getBoolean('sign.authnrequest', null);
            }
        }

        if ($signingEnabled === null) {
            $signingEnabled = $dstMetadata->getBoolean('redirect.sign', null);
            if ($signingEnabled === null) {
                $signingEnabled = $srcMetadata->getBoolean('redirect.sign', false);
            }
        }
        if (!$signingEnabled) {
            return;
        }

        self::addSign($srcMetadata, $dstMetadata, $message);
    }


    /**
     * Find the certificate used to sign a message or assertion.
     *
     * An exception is thrown if we are unable to locate the certificate.
     *
     * @param array $certFingerprints The fingerprints we are looking for.
     * @param array $certificates Array of certificates.
     *
     * @return string Certificate, in PEM-format.
     *
     * @throws SimpleSAML_Error_Exception if we cannot find the certificate matching the fingerprint.
     */
    private static function findCertificate(array $certFingerprints, array $certificates)
    {
        $candidates = array();

        foreach ($certificates as $cert) {
            $fp = strtolower(sha1(base64_decode($cert)));
            if (!in_array($fp, $certFingerprints, true)) {
                $candidates[] = $fp;
                continue;
            }

            /* We have found a matching fingerprint. */
            $pem = "-----BEGIN CERTIFICATE-----\n".
                chunk_split($cert, 64).
                "-----END CERTIFICATE-----\n";
            return $pem;
        }

        $candidates = "'".implode("', '", $candidates)."'";
        $fps = "'".implode("', '", $certFingerprints)."'";
        throw new SimpleSAML_Error_Exception('Unable to find a certificate matching the configured '.
            'fingerprint. Candidates: '.$candidates.'; certFingerprint: '.$fps.'.');
    }


    /**
     * Check the signature on a SAML2 message or assertion.
     *
     * @param SimpleSAML_Configuration $srcMetadata The metadata of the sender.
     * @param \SAML2\SignedElement $element Either a \SAML2\Response or a \SAML2\Assertion.
     * @return boolean True if the signature is correct, false otherwise.
     *
     * @throws \SimpleSAML_Error_Exception if there is not certificate in the metadata for the entity.
     * @throws \Exception if the signature validation fails with an exception.
     */
    public static function checkSign(SimpleSAML_Configuration $srcMetadata, \SAML2\SignedElement $element)
    {
        // find the public key that should verify signatures by this entity
        $keys = $srcMetadata->getPublicKeys('signing');
        if ($keys !== null) {
            $pemKeys = array();
            foreach ($keys as $key) {
                switch ($key['type']) {
                    case 'X509Certificate':
                        $pemKeys[] = "-----BEGIN CERTIFICATE-----\n".
                            chunk_split($key['X509Certificate'], 64).
                            "-----END CERTIFICATE-----\n";
                        break;
                    default:
                        SimpleSAML\Logger::debug('Skipping unknown key type: '.$key['type']);
                }
            }
        } elseif ($srcMetadata->hasValue('certFingerprint')) {
            SimpleSAML\Logger::notice(
                "Validating certificates by fingerprint is deprecated. Please use ".
                "certData or certificate options in your remote metadata configuration."
            );

            $certFingerprint = $srcMetadata->getArrayizeString('certFingerprint');
            foreach ($certFingerprint as &$fp) {
                $fp = strtolower(str_replace(':', '', $fp));
            }

            $certificates = $element->getCertificates();

            // we don't have the full certificate stored. Try to find it in the message or the assertion instead
            if (count($certificates) === 0) {
                /* We need the full certificate in order to match it against the fingerprint. */
                SimpleSAML\Logger::debug('No certificate in message when validating against fingerprint.');
                return false;
            } else {
                SimpleSAML\Logger::debug('Found '.count($certificates).' certificates in '.get_class($element));
            }

            $pemCert = self::findCertificate($certFingerprint, $certificates);
            $pemKeys = array($pemCert);
        } else {
            throw new SimpleSAML_Error_Exception(
                'Missing certificate in metadata for '.
                var_export($srcMetadata->getString('entityid'), true)
            );
        }

        SimpleSAML\Logger::debug('Has '.count($pemKeys).' candidate keys for validation.');

        $lastException = null;
        foreach ($pemKeys as $i => $pem) {
            $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type' => 'public'));
            $key->loadKey($pem);

            try {
                // make sure that we have a valid signature on either the response or the assertion
                $res = $element->validate($key);
                if ($res) {
                    SimpleSAML\Logger::debug('Validation with key #'.$i.' succeeded.');
                    return true;
                }
                SimpleSAML\Logger::debug('Validation with key #'.$i.' failed without exception.');
            } catch (Exception $e) {
                SimpleSAML\Logger::debug('Validation with key #'.$i.' failed with exception: '.$e->getMessage());
                $lastException = $e;
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
     * @param SimpleSAML_Configuration $srcMetadata The metadata of the sender.
     * @param SimpleSAML_Configuration $dstMetadata The metadata of the recipient.
     * @param \SAML2\Message $message The message we should check the signature on.
     *
     * @throws \SimpleSAML_Error_Exception if message validation is enabled, but there is no signature in the message.
     */
    public static function validateMessage(
        SimpleSAML_Configuration $srcMetadata,
        SimpleSAML_Configuration $dstMetadata,
        \SAML2\Message $message
    ) {
        $enabled = null;
        if ($message instanceof \SAML2\LogoutRequest || $message instanceof \SAML2\LogoutResponse) {
            $enabled = $srcMetadata->getBoolean('validate.logout', null);
            if ($enabled === null) {
                $enabled = $dstMetadata->getBoolean('validate.logout', null);
            }
        } elseif ($message instanceof \SAML2\AuthnRequest) {
            $enabled = $srcMetadata->getBoolean('validate.authnrequest', null);
            if ($enabled === null) {
                $enabled = $dstMetadata->getBoolean('validate.authnrequest', null);
            }
        }

        if ($enabled === null) {
            $enabled = $srcMetadata->getBoolean('redirect.validate', null);
            if ($enabled === null) {
                $enabled = $dstMetadata->getBoolean('redirect.validate', false);
            }
        }

        if (!$enabled) {
            return;
        }

        if (!self::checkSign($srcMetadata, $message)) {
            throw new SimpleSAML_Error_Exception(
                'Validation of received messages enabled, but no signature found on message.'
            );
        }
    }


    /**
     * Retrieve the decryption keys from metadata.
     *
     * @param SimpleSAML_Configuration $srcMetadata The metadata of the sender (IdP).
     * @param SimpleSAML_Configuration $dstMetadata The metadata of the recipient (SP).
     *
     * @return array Array of decryption keys.
     */
    public static function getDecryptionKeys(
        SimpleSAML_Configuration $srcMetadata,
        SimpleSAML_Configuration $dstMetadata
    ) {
        $sharedKey = $srcMetadata->getString('sharedkey', null);
        if ($sharedKey !== null) {
            $key = new XMLSecurityKey(XMLSecurityKey::AES128_CBC);
            $key->loadKey($sharedKey);
            return array($key);
        }

        $keys = array();

        // load the new private key if it exists
        $keyArray = SimpleSAML\Utils\Crypto::loadPrivateKey($dstMetadata, false, 'new_');
        if ($keyArray !== null) {
            assert('isset($keyArray["PEM"])');

            $key = new XMLSecurityKey(XMLSecurityKey::RSA_1_5, array('type' => 'private'));
            if (array_key_exists('password', $keyArray)) {
                $key->passphrase = $keyArray['password'];
            }
            $key->loadKey($keyArray['PEM']);
            $keys[] = $key;
        }

        // find the existing private key
        $keyArray = SimpleSAML\Utils\Crypto::loadPrivateKey($dstMetadata, true);
        assert('isset($keyArray["PEM"])');

        $key = new XMLSecurityKey(XMLSecurityKey::RSA_1_5, array('type' => 'private'));
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
     * @param SimpleSAML_Configuration $srcMetadata The metadata of the sender.
     * @param SimpleSAML_Configuration $dstMetadata The metadata of the recipient.
     *
     * @return array  Array of blacklisted algorithms.
     */
    public static function getBlacklistedAlgorithms(
        SimpleSAML_Configuration $srcMetadata,
        SimpleSAML_Configuration $dstMetadata
    ) {
        $blacklist = $srcMetadata->getArray('encryption.blacklisted-algorithms', null);
        if ($blacklist === null) {
            $blacklist = $dstMetadata->getArray('encryption.blacklisted-algorithms', array(XMLSecurityKey::RSA_1_5));
        }
        return $blacklist;
    }


    /**
     * Decrypt an assertion.
     *
     * @param SimpleSAML_Configuration $srcMetadata The metadata of the sender (IdP).
     * @param SimpleSAML_Configuration $dstMetadata The metadata of the recipient (SP).
     * @param \SAML2\Assertion|\SAML2\EncryptedAssertion $assertion The assertion we are decrypting.
     *
     * @return \SAML2\Assertion The assertion.
     *
     * @throws \SimpleSAML_Error_Exception if encryption is enabled but the assertion is not encrypted, or if we cannot
     * get the decryption keys.
     * @throws \Exception if decryption fails for whatever reason.
     */
    private static function decryptAssertion(
        SimpleSAML_Configuration $srcMetadata,
        SimpleSAML_Configuration $dstMetadata,
        $assertion
    ) {
        assert('$assertion instanceof \SAML2\Assertion || $assertion instanceof \SAML2\EncryptedAssertion');

        if ($assertion instanceof \SAML2\Assertion) {
            $encryptAssertion = $srcMetadata->getBoolean('assertion.encryption', null);
            if ($encryptAssertion === null) {
                $encryptAssertion = $dstMetadata->getBoolean('assertion.encryption', false);
            }
            if ($encryptAssertion) {
                /* The assertion was unencrypted, but we have encryption enabled. */
                throw new Exception('Received unencrypted assertion, but encryption was enabled.');
            }

            return $assertion;
        }

        try {
            $keys = self::getDecryptionKeys($srcMetadata, $dstMetadata);
        } catch (Exception $e) {
            throw new SimpleSAML_Error_Exception('Error decrypting assertion: '.$e->getMessage());
        }

        $blacklist = self::getBlacklistedAlgorithms($srcMetadata, $dstMetadata);

        $lastException = null;
        foreach ($keys as $i => $key) {
            try {
                $ret = $assertion->getAssertion($key, $blacklist);
                SimpleSAML\Logger::debug('Decryption with key #'.$i.' succeeded.');
                return $ret;
            } catch (Exception $e) {
                SimpleSAML\Logger::debug('Decryption with key #'.$i.' failed with exception: '.$e->getMessage());
                $lastException = $e;
            }
        }
        throw $lastException;
    }


    /**
     * Retrieve the status code of a response as a sspmod_saml_Error.
     *
     * @param \SAML2\StatusResponse $response The response.
     *
     * @return sspmod_saml_Error The error.
     */
    public static function getResponseError(\SAML2\StatusResponse $response)
    {
        $status = $response->getStatus();
        return new sspmod_saml_Error($status['Code'], $status['SubCode'], $status['Message']);
    }


    /**
     * Build an authentication request based on information in the metadata.
     *
     * @param SimpleSAML_Configuration $spMetadata The metadata of the service provider.
     * @param SimpleSAML_Configuration $idpMetadata The metadata of the identity provider.
     * @return \SAML2\AuthnRequest An authentication request object.
     */
    public static function buildAuthnRequest(
        SimpleSAML_Configuration $spMetadata,
        SimpleSAML_Configuration $idpMetadata
    ) {
        $ar = new \SAML2\AuthnRequest();

        // get the NameIDPolicy to apply. IdP metadata has precedence.
        $nameIdPolicy = array();
        if ($idpMetadata->hasValue('NameIDPolicy')) {
            $nameIdPolicy = $idpMetadata->getValue('NameIDPolicy');
        } elseif ($spMetadata->hasValue('NameIDPolicy')) {
            $nameIdPolicy = $spMetadata->getValue('NameIDPolicy');
        }

        if (!is_array($nameIdPolicy)) {
            // handle old configurations where 'NameIDPolicy' was used to specify just the format
            $nameIdPolicy = array('Format' => $nameIdPolicy);
        }

        $nameIdPolicy_cf = SimpleSAML_Configuration::loadFromArray($nameIdPolicy);
        $policy = array(
            'Format'      => $nameIdPolicy_cf->getString('Format', \SAML2\Constants::NAMEID_TRANSIENT),
            'AllowCreate' => $nameIdPolicy_cf->getBoolean('AllowCreate', true),
        );
        $spNameQualifier = $nameIdPolicy_cf->getString('SPNameQualifier', false);
        if ($spNameQualifier !== false) {
            $policy['SPNameQualifier'] = $spNameQualifier;
        }
        $ar->setNameIdPolicy($policy);

        $ar->setForceAuthn($spMetadata->getBoolean('ForceAuthn', false));
        $ar->setIsPassive($spMetadata->getBoolean('IsPassive', false));

        $protbind = $spMetadata->getValueValidate('ProtocolBinding', array(
            \SAML2\Constants::BINDING_HTTP_POST,
            \SAML2\Constants::BINDING_HOK_SSO,
            \SAML2\Constants::BINDING_HTTP_ARTIFACT,
            \SAML2\Constants::BINDING_HTTP_REDIRECT,
        ), \SAML2\Constants::BINDING_HTTP_POST);

        // Shoaib: setting the appropriate binding based on parameter in sp-metadata defaults to HTTP_POST
        $ar->setProtocolBinding($protbind);
        $ar->setIssuer($spMetadata->getString('entityid'));
        $ar->setAssertionConsumerServiceIndex($spMetadata->getInteger('AssertionConsumerServiceIndex', null));
        $ar->setAttributeConsumingServiceIndex($spMetadata->getInteger('AttributeConsumingServiceIndex', null));

        if ($spMetadata->hasValue('AuthnContextClassRef')) {
            $accr = $spMetadata->getArrayizeString('AuthnContextClassRef');
            $comp = $spMetadata->getValueValidate('AuthnContextComparison', array(
                \SAML2\Constants::COMPARISON_EXACT,
                \SAML2\Constants::COMPARISON_MINIMUM,
                \SAML2\Constants::COMPARISON_MAXIMUM,
                \SAML2\Constants::COMPARISON_BETTER,
            ), \SAML2\Constants::COMPARISON_EXACT);
            $ar->setRequestedAuthnContext(array('AuthnContextClassRef' => $accr, 'Comparison' => $comp));
        }

        self::addRedirectSign($spMetadata, $idpMetadata, $ar);

        return $ar;
    }


    /**
     * Build a logout request based on information in the metadata.
     *
     * @param SimpleSAML_Configuration $srcMetadata The metadata of the sender.
     * @param SimpleSAML_Configuration $dstMetadata The metadata of the recipient.
     * @return \SAML2\LogoutRequest A logout request object.
     */
    public static function buildLogoutRequest(
        SimpleSAML_Configuration $srcMetadata,
        SimpleSAML_Configuration $dstMetadata
    ) {
        $lr = new \SAML2\LogoutRequest();
        $lr->setIssuer($srcMetadata->getString('entityid'));

        self::addRedirectSign($srcMetadata, $dstMetadata, $lr);

        return $lr;
    }


    /**
     * Build a logout response based on information in the metadata.
     *
     * @param SimpleSAML_Configuration $srcMetadata The metadata of the sender.
     * @param SimpleSAML_Configuration $dstMetadata The metadata of the recipient.
     * @return \SAML2\LogoutResponse A logout response object.
     */
    public static function buildLogoutResponse(
        SimpleSAML_Configuration $srcMetadata,
        SimpleSAML_Configuration $dstMetadata
    ) {
        $lr = new \SAML2\LogoutResponse();
        $lr->setIssuer($srcMetadata->getString('entityid'));

        self::addRedirectSign($srcMetadata, $dstMetadata, $lr);

        return $lr;
    }


    /**
     * Process a response message.
     *
     * If the response is an error response, we will throw a sspmod_saml_Error exception with the error.
     *
     * @param SimpleSAML_Configuration $spMetadata The metadata of the service provider.
     * @param SimpleSAML_Configuration $idpMetadata The metadata of the identity provider.
     * @param \SAML2\Response $response The response.
     *
     * @return array Array with \SAML2\Assertion objects, containing valid assertions from the response.
     *
     * @throws \SimpleSAML_Error_Exception if there are no assertions in the response.
     * @throws \Exception if the destination of the response does not match the current URL.
     */
    public static function processResponse(
        SimpleSAML_Configuration $spMetadata,
        SimpleSAML_Configuration $idpMetadata,
        \SAML2\Response $response
    ) {
        if (!$response->isSuccess()) {
            throw self::getResponseError($response);
        }

        // validate Response-element destination
        $currentURL = \SimpleSAML\Utils\HTTP::getSelfURLNoQuery();
        $msgDestination = $response->getDestination();
        if ($msgDestination !== null && $msgDestination !== $currentURL) {
            throw new Exception('Destination in response doesn\'t match the current URL. Destination is "'.
                $msgDestination.'", current URL is "'.$currentURL.'".');
        }

        $responseSigned = self::checkSign($idpMetadata, $response);

        /*
         * When we get this far, the response itself is valid.
         * We only need to check signatures and conditions of the response.
         */
        $assertion = $response->getAssertions();
        if (empty($assertion)) {
            throw new SimpleSAML_Error_Exception('No assertions found in response from IdP.');
        }

        $ret = array();
        foreach ($assertion as $a) {
            $ret[] = self::processAssertion($spMetadata, $idpMetadata, $response, $a, $responseSigned);
        }

        return $ret;
    }


    /**
     * Process an assertion in a response.
     *
     * @param SimpleSAML_Configuration $spMetadata The metadata of the service provider.
     * @param SimpleSAML_Configuration $idpMetadata The metadata of the identity provider.
     * @param \SAML2\Response $response The response containing the assertion.
     * @param \SAML2\Assertion|\SAML2\EncryptedAssertion $assertion The assertion.
     * @param bool $responseSigned Whether the response is signed.
     *
     * @return \SAML2\Assertion The assertion, if it is valid.
     *
     * @throws \SimpleSAML_Error_Exception if an error occurs while trying to validate the assertion, or if a assertion
     * is not signed and it should be, or if we are unable to decrypt the NameID due to a local failure (missing or
     * invalid decryption key).
     * @throws \Exception if we couldn't decrypt the NameID for unexpected reasons.
     */
    private static function processAssertion(
        SimpleSAML_Configuration $spMetadata,
        SimpleSAML_Configuration $idpMetadata,
        \SAML2\Response $response,
        $assertion,
        $responseSigned
    ) {
        assert('$assertion instanceof \SAML2\Assertion || $assertion instanceof \SAML2\EncryptedAssertion');
        assert('is_bool($responseSigned)');

        $assertion = self::decryptAssertion($idpMetadata, $spMetadata, $assertion);

        if (!self::checkSign($idpMetadata, $assertion)) {
            if (!$responseSigned) {
                throw new SimpleSAML_Error_Exception('Neither the assertion nor the response was signed.');
            }
        } // at least one valid signature found

        $currentURL = \SimpleSAML\Utils\HTTP::getSelfURLNoQuery();

        // check various properties of the assertion
        $notBefore = $assertion->getNotBefore();
        if ($notBefore !== null && $notBefore > time() + 60) {
            throw new SimpleSAML_Error_Exception(
                'Received an assertion that is valid in the future. Check clock synchronization on IdP and SP.'
            );
        }
        $notOnOrAfter = $assertion->getNotOnOrAfter();
        if ($notOnOrAfter !== null && $notOnOrAfter <= time() - 60) {
            throw new SimpleSAML_Error_Exception(
                'Received an assertion that has expired. Check clock synchronization on IdP and SP.'
            );
        }
        $sessionNotOnOrAfter = $assertion->getSessionNotOnOrAfter();
        if ($sessionNotOnOrAfter !== null && $sessionNotOnOrAfter <= time() - 60) {
            throw new SimpleSAML_Error_Exception(
                'Received an assertion with a session that has expired. Check clock synchronization on IdP and SP.'
            );
        }
        $validAudiences = $assertion->getValidAudiences();
        if ($validAudiences !== null) {
            $spEntityId = $spMetadata->getString('entityid');
            if (!in_array($spEntityId, $validAudiences, true)) {
                $candidates = '['.implode('], [', $validAudiences).']';
                throw new SimpleSAML_Error_Exception('This SP ['.$spEntityId.
                    ']  is not a valid audience for the assertion. Candidates were: '.$candidates);
            }
        }

        $found = false;
        $lastError = 'No SubjectConfirmation element in Subject.';
        $validSCMethods = array(\SAML2\Constants::CM_BEARER, \SAML2\Constants::CM_HOK, \SAML2\Constants::CM_VOUCHES);
        foreach ($assertion->getSubjectConfirmation() as $sc) {
            if (!in_array($sc->Method, $validSCMethods, true)) {
                $lastError = 'Invalid Method on SubjectConfirmation: '.var_export($sc->Method, true);
                continue;
            }

            // is SSO with HoK enabled? IdP remote metadata overwrites SP metadata configuration
            $hok = $idpMetadata->getBoolean('saml20.hok.assertion', null);
            if ($hok === null) {
                $hok = $spMetadata->getBoolean('saml20.hok.assertion', false);
            }
            if ($sc->Method === \SAML2\Constants::CM_BEARER && $hok) {
                $lastError = 'Bearer SubjectConfirmation received, but Holder-of-Key SubjectConfirmation needed';
                continue;
            }
            if ($sc->Method === \SAML2\Constants::CM_HOK && !$hok) {
                $lastError = 'Holder-of-Key SubjectConfirmation received, '.
                    'but the Holder-of-Key profile is not enabled.';
                continue;
            }

            $scd = $sc->SubjectConfirmationData;
            if ($sc->Method === \SAML2\Constants::CM_HOK) {
                // check HoK Assertion
                if (\SimpleSAML\Utils\HTTP::isHTTPS() === false) {
                    $lastError = 'No HTTPS connection, but required for Holder-of-Key SSO';
                    continue;
                }
                if (isset($_SERVER['SSL_CLIENT_CERT']) && empty($_SERVER['SSL_CLIENT_CERT'])) {
                    $lastError = 'No client certificate provided during TLS Handshake with SP';
                    continue;
                }
                // extract certificate data (if this is a certificate)
                $clientCert = $_SERVER['SSL_CLIENT_CERT'];
                $pattern = '/^-----BEGIN CERTIFICATE-----([^-]*)^-----END CERTIFICATE-----/m';
                if (!preg_match($pattern, $clientCert, $matches)) {
                    $lastError = 'Error while looking for client certificate during TLS handshake with SP, the client '.
                        'certificate does not have the expected structure';
                    continue;
                }
                // we have a valid client certificate from the browser
                $clientCert = str_replace(array("\r", "\n", " "), '', $matches[1]);

                $keyInfo = array();
                foreach ($scd->info as $thing) {
                    if ($thing instanceof \SAML2\XML\ds\KeyInfo) {
                        $keyInfo[] = $thing;
                    }
                }
                if (count($keyInfo) != 1) {
                    $lastError = 'Error validating Holder-of-Key assertion: Only one <ds:KeyInfo> element in '.
                        '<SubjectConfirmationData> allowed';
                    continue;
                }

                $x509data = array();
                foreach ($keyInfo[0]->info as $thing) {
                    if ($thing instanceof \SAML2\XML\ds\X509Data) {
                        $x509data[] = $thing;
                    }
                }
                if (count($x509data) != 1) {
                    $lastError = 'Error validating Holder-of-Key assertion: Only one <ds:X509Data> element in '.
                        '<ds:KeyInfo> within <SubjectConfirmationData> allowed';
                    continue;
                }

                $x509cert = array();
                foreach ($x509data[0]->data as $thing) {
                    if ($thing instanceof \SAML2\XML\ds\X509Certificate) {
                        $x509cert[] = $thing;
                    }
                }
                if (count($x509cert) != 1) {
                    $lastError = 'Error validating Holder-of-Key assertion: Only one <ds:X509Certificate> element in '.
                        '<ds:X509Data> within <SubjectConfirmationData> allowed';
                    continue;
                }

                $HoKCertificate = $x509cert[0]->certificate;
                if ($HoKCertificate !== $clientCert) {
                    $lastError = 'Provided client certificate does not match the certificate bound to the '.
                        'Holder-of-Key assertion';
                    continue;
                }
            }

            // if no SubjectConfirmationData then don't do anything.
            if ($scd === null) {
                $lastError = 'No SubjectConfirmationData provided';
                continue;
            }

            if ($scd->NotBefore && $scd->NotBefore > time() + 60) {
                $lastError = 'NotBefore in SubjectConfirmationData is in the future: '.$scd->NotBefore;
                continue;
            }
            if ($scd->NotOnOrAfter && $scd->NotOnOrAfter <= time() - 60) {
                $lastError = 'NotOnOrAfter in SubjectConfirmationData is in the past: '.$scd->NotOnOrAfter;
                continue;
            }
            if ($scd->Recipient !== null && $scd->Recipient !== $currentURL) {
                $lastError = 'Recipient in SubjectConfirmationData does not match the current URL. Recipient is '.
                    var_export($scd->Recipient, true).', current URL is '.var_export($currentURL, true).'.';
                continue;
            }
            if ($scd->InResponseTo !== null && $response->getInResponseTo() !== null &&
                $scd->InResponseTo !== $response->getInResponseTo()
            ) {
                $lastError = 'InResponseTo in SubjectConfirmationData does not match the Response. Response has '.
                    var_export($response->getInResponseTo(), true).
                    ', SubjectConfirmationData has '.var_export($scd->InResponseTo, true).'.';
                continue;
            }
            $found = true;
            break;
        }
        if (!$found) {
            throw new SimpleSAML_Error_Exception('Error validating SubjectConfirmation in Assertion: '.$lastError);
        } // as far as we can tell, the assertion is valid

        // maybe we need to base64 decode the attributes in the assertion?
        if ($idpMetadata->getBoolean('base64attributes', false)) {
            $attributes = $assertion->getAttributes();
            $newAttributes = array();
            foreach ($attributes as $name => $values) {
                $newAttributes[$name] = array();
                foreach ($values as $value) {
                    foreach (explode('_', $value) as $v) {
                        $newAttributes[$name][] = base64_decode($v);
                    }
                }
            }
            $assertion->setAttributes($newAttributes);
        }

        // decrypt the NameID element if it is encrypted
        if ($assertion->isNameIdEncrypted()) {
            try {
                $keys = self::getDecryptionKeys($idpMetadata, $spMetadata);
            } catch (Exception $e) {
                throw new SimpleSAML_Error_Exception('Error decrypting NameID: '.$e->getMessage());
            }

            $blacklist = self::getBlacklistedAlgorithms($idpMetadata, $spMetadata);

            $lastException = null;
            foreach ($keys as $i => $key) {
                try {
                    $assertion->decryptNameId($key, $blacklist);
                    SimpleSAML\Logger::debug('Decryption with key #'.$i.' succeeded.');
                    $lastException = null;
                    break;
                } catch (Exception $e) {
                    SimpleSAML\Logger::debug('Decryption with key #'.$i.' failed with exception: '.$e->getMessage());
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
     * Retrieve the encryption key for the given entity.
     *
     * @param SimpleSAML_Configuration $metadata The metadata of the entity.
     *
     * @return \RobRichards\XMLSecLibs\XMLSecurityKey  The encryption key.
     *
     * @throws \SimpleSAML_Error_Exception if there is no supported encryption key in the metadata of this entity.
     */
    public static function getEncryptionKey(SimpleSAML_Configuration $metadata)
    {

        $sharedKey = $metadata->getString('sharedkey', null);
        if ($sharedKey !== null) {
            $key = new XMLSecurityKey(XMLSecurityKey::AES128_CBC);
            $key->loadKey($sharedKey);
            return $key;
        }

        $keys = $metadata->getPublicKeys('encryption', true);
        foreach ($keys as $key) {
            switch ($key['type']) {
                case 'X509Certificate':
                    $pemKey = "-----BEGIN CERTIFICATE-----\n".
                        chunk_split($key['X509Certificate'], 64).
                        "-----END CERTIFICATE-----\n";
                    $key = new XMLSecurityKey(XMLSecurityKey::RSA_OAEP_MGF1P, array('type' => 'public'));
                    $key->loadKey($pemKey);
                    return $key;
            }
        }

        throw new SimpleSAML_Error_Exception('No supported encryption key in '.
            var_export($metadata->getString('entityid'), true));
    }
}
