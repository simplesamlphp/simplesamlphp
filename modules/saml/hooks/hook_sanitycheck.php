<?php

declare(strict_types=1);

use Webmozart\Assert\Assert;
use SimpleSAML\{Configuration, Utils};
use SimpleSAML\Locale\Translate;
use SimpleSAML\Metadata\MetaDataStorageHandler;

function saml_hook_sanitycheck(array &$hookinfo): void
{
    Assert::keyExists($hookinfo, 'errors');
    Assert::keyExists($hookinfo, 'info');

    define('MODID', '[saml] ');
    $config = Configuration::getInstance();
    $cryptoUtils = new Utils\Crypto();

    // perform some sanity checks on the configured certificates
    if ($config->getOptionalBoolean('enable.saml20-idp', false) !== false) {
        $handler = MetaDataStorageHandler::getMetadataHandler($config);
        try {
            $metadata = $handler->getMetaDataCurrent('saml20-idp-hosted');
        } catch (Exception $e) {
            $hookinfo['errors'][] = MODID . Translate::noop('Hosted IdP metadata present');
        }

        if (isset($metadata)) {
            $metadata_config = Configuration::loadfromArray($metadata);
            $private = $cryptoUtils->loadPrivateKey($metadata_config, false);
            $public = $cryptoUtils->loadPublicKey($metadata_config, false);

            $matches = matchingKeyPair($public['PEM'], $private['PEM'], $private['password']);
            $hookinfo[$matches ? 'info' : 'errors'][] = MODID .
                Translate::noop('Matching key-pair for signing assertions');

            $private = $cryptoUtils->loadPrivateKey($metadata_config, false, 'new_');
            if ($private !== null) {
                $public = $cryptoUtils->loadPublicKey($metadata_config, false, 'new_');
                $matches = matchingKeyPair($public['PEM'], $private['PEM'], $private['password']);
                $hookinfo[$matches ? 'info' : 'errors'][] = MODID .
                    Translate::noop('Matching key-pair for signing assertions (rollover key)');
            }
        }
    }

    if ($config->getOptionalBoolean('metadata.sign.enable', false) !== false) {
        $private = $cryptoUtils->loadPrivateKey($config, true, 'metadata.sign.');
        $public = $cryptoUtils->loadPublicKey($config, true, 'metadata.sign.');
        $matches = matchingKeyPair($public['PEM'], $private['PEM'], $private['password']);
        $hookinfo[$matches ? 'info' : 'errors'][] =
            MODID . Translate::noop('Matching key-pair for signing metadata');
    }
}

function matchingKeyPair(
    string $publicKey,
    string $privateKey,
    #[\SensitiveParameter]
    ?string $password = null,
): bool {
    return openssl_x509_check_private_key($publicKey, [$privateKey, $password]);
}
