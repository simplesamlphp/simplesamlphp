<?php 

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Event\Listener;

use Exception;
use SimpleSAML\Configuration;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\admin\Event\SanityCheckEvent;
use SimpleSAML\Utils;

class SamlSanityCheckListener
{
    public function __invoke(SanityCheckEvent $event): void
    {
        define('MODID', '[saml] ');
        $config = Configuration::getInstance();
        $cryptoUtils = new Utils\Crypto();

        // perform some sanity checks on the configured certificates
        if ($config->getOptionalBoolean('enable.saml20-idp', false) !== false) {
            $handler = MetaDataStorageHandler::getMetadataHandler($config);
            try {
                $metadata = $handler->getMetaDataCurrent('saml20-idp-hosted');
            } catch (Exception $e) {
                $event->addError(MODID . Translate::noop('Hosted IdP metadata present'));
            }

            if (isset($metadata)) {
                $metadata_config = Configuration::loadfromArray($metadata);
                $private = $cryptoUtils->loadPrivateKey($metadata_config, false);
                $public = $cryptoUtils->loadPublicKey($metadata_config, false);

                $matches = $this->matchingKeyPair($public['PEM'], $private['PEM'], $private['password']);
                $message = MODID .
                    Translate::noop('Matching key-pair for signing assertions');
                if ($matches) {
                    $event->addInfo($message);
                } else {
                    $event->addError($message);
                }

                $private = $cryptoUtils->loadPrivateKey($metadata_config, false, 'new_');
                if ($private !== null) {
                    $public = $cryptoUtils->loadPublicKey($metadata_config, false, 'new_');
                    $matches = $this->matchingKeyPair($public['PEM'], $private['PEM'], $private['password']);
                    $message = MODID .
                        Translate::noop('Matching key-pair for signing assertions (rollover key)');
                    if ($matches) {
                        $event->addInfo($message);
                    } else {
                        $event->addError($message);
                    }
                }
            }
        }

        if ($config->getOptionalBoolean('metadata.sign.enable', false) !== false) {
            $private = $cryptoUtils->loadPrivateKey($config, true, 'metadata.sign.');
            $public = $cryptoUtils->loadPublicKey($config, true, 'metadata.sign.');
            $matches = $this->matchingKeyPair($public['PEM'], $private['PEM'], $private['password']);
            $message = MODID . Translate::noop('Matching key-pair for signing metadata');
            if ($matches) {
                $event->addInfo($message);
            } else {
                $event->addError($message);
            }
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
}