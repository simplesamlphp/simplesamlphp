<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml;

use Beste\Clock\LocalizedClock;
use Psr\Clock\ClockInterface;
use SimpleSAML\{Configuration, Error, Logger, Utils};
use SimpleSAML\Assert\Assert;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\XML\saml\{NameID, Subject}; // Subject
use SimpleSAML\SAML2\XML\saml\{Conditions, AudienceRestriction, Audience}; // Conditions
use SimpleSAML\SAML2\XML\samlp\{AuthnRequest, LogoutRequest}; // Messages
use SimpleSAML\SAML2\XML\samlp\{Extensions, SessionIndex};
use SimpleSAML\SAML2\XML\samlp\AbstractMessage as SAML2_Message;
use SimpleSAML\XMLSecurity\Alg\Encryption\EncryptionAlgorithmFactory;
use SimpleSAML\XMLSecurity\Alg\KeyTransport\KeyTransportAlgorithmFactory;
use SimpleSAML\XMLSecurity\Alg\Signature\SignatureAlgorithmFactory;
use SimpleSAML\XMLSecurity\Key\PrivateKey;
use SimpleSAML\XMLSecurity\Key\SymmetricKey;
use SimpleSAML\XMLSecurity\XML\ds\{KeyInfo, X509Certificate, X509Data};

use function array_map;
use function sprintf;
use function var_export;

/**
 * Common code for building SAML 2 messages based on the available metadata.
 *
 * @package SimpleSAMLphp
 */
abstract class AbstractMessage
{
    /** @var \Psr\Clock\ClockInterface */
    protected ClockInterface $clock;

    /**
     * Constructor.
     *
     * @param \SimpleSAML\Configuration $srcMetadata The source metadata
     * @param \SimpleSAML\Configuration $dstMetadata The destination metadata
     * @param array $state The current state
     */
    public function __construct(
        protected Configuration $srcMetadata,
        protected Configuration $dstMetadata,
        protected array $state
    ) {
        $this->clock = LocalizedClock::in('Z');
    }


    /**
     * @param \SimpleSAML\SAML2\XML\saml\IdentifierInterface $identifier
     * @return \SimpleSAML\SAML2\XML\saml\EncryptedID
     */
    protected function encryptIdentifier(IdentifierInterface $identifier): EncryptedID
    {
        if ($this->dstMetadata->hasValue('sharedkey')) {
            $encryptor = (new EncryptionAlgorithmFactory())->getAlgorithm(
                $this->dstMetadata->getOptionalString('sharedkey_algorithm', C::BLOCK_ENC_AES128_GCM),
                new SymmetricKey($this->dstMetadata->getString('sharedkey'))
            );
        } else {
            $keys = $metadata->getPublicKeys('encryption', true);
            $publicKey = null;

            foreach ($keys as $key) {
                switch ($key['type']) {
                    case 'X509Certificate':
                        $publicKey = PublicKey::fromFile($key['X509Certificate']);
                        break 2;
                }
            }

            if ($publicKey === null) {
                throw new Error\Exception(sprintf(
                    'No supported encryption key in %s',
                    var_export($metadata->getString('entityid'), true),
                ));
            }

            $encryptor = (new KeyTransportAlgorithmFactory())->getAlgorithm(
                C::KEY_TRANSPORT_OAEP_MGF1P, // @TODO: Configurable algo
                $publicKey,
            );
        }

        return $identifier->encrypt($encryptor);
    }


    /**
     * @param \SimpleSAML\SAML2\XML\samlp\AbstractMessage $message
     * @return \SimpleSAML\SAML2\XML\samlp\AbstractMessage
     */
    protected function signMessage(AbstractMessage $message): AbstractMessage
    {
        $dstPrivateKey = $this->dstMetadata->getOptionalString('signature.privatekey', null);
        $cryptoUtils = new Utils\Crypto();

        if ($dstPrivateKey !== null) {
            /** @var array $keyArray */
            $keyArray = $cryptoUtils->loadPrivateKey($this->dstMetadata, true, 'signature.');
            $certArray = $cryptoUtils->loadPublicKey($this->dstMetadata, false, 'signature.');
        } else {
            /** @var array $keyArray */
            $keyArray = $cryptoUtils->loadPrivateKey($this->srcMetadata, true);
            $certArray = $cryptoUtils->loadPublicKey($this->srcMetadata, false);
        }

        $algo = $dstMetadata->getOptionalString('signature.algorithm', null);
        if ($algo === null) {
            $algo = $srcMetadata->getOptionalString('signature.algorithm', C::SIG_RSA_SHA256);
        }

        $key = PrivateKey::fromFile($keyArray['PEM'], $keyArray['password'] ?? '');
        $signer = (new SignatureAlgorithmFactory())->getAlgorithm($algo, $key);

        $keyInfo = null;
        if ($certArray !== null) {
            $keyInfo = new KeyInfo(
                new X509Data([
                    new X509Certificate($certArray['PEM']),
                ]),
            );
        }

        return $message->sign($signer, $keyInfo);
    }


    /**
     * Whether or not nameid.encryption is set and true
     *
     * @return bool
     */
    protected function hasNameIDEncryption(): bool
    {
        $enabled = $this->dstMetadata->getOptionalBoolean('nameid.encryption', null);
        if ($enabled === null) {
            $enabled = $this->srcMetadata->getOptionalBoolean('nameid.encryption', false);
        }

        return $enabled;
    }


    /**
     * Whether or not redirect.sign is set and true
     *
     * @return bool
     */
    protected function hasRedirectSign(): bool
    {
        $enabled = $this->dstMetadata->getOptionalBoolean('redirect.sign', null);
        if ($enabled === null) {
            return $this->srcMetadata->getOptionalBoolean('redirect.sign', false);
        }

        return $enabled;
    }


    /**
     * This method builds the saml:Subject if any
     */
    protected function getSubject(): ?Subject
    {
        $identifier = null;

        if (isset($this->state['saml:NameID'])) {
            Assert::isInstanceOf($this->state['saml:NameID'], NameID::class);
            $identifier = $this->state['saml:NameID'];
        }

        if ($identifier !== null) {
            return new Subject($identifier);
        }

        return null;
    }


    /**
     * This method builds the saml:Conditions if any
     */
    protected function getConditions(): ?Conditions
    {
        $audienceRestriction = $this->getAudienceRestriction();

        if ($audienceRestriction !== null) {
            return new Conditions(
                audienceRestriction: $audienceRestriction,
            );
        }

        return null;
    }


    /**
     * This method parses the different possible config values of the saml:AudienceRestriction
     */
    protected function getAudienceRestriction(): ?AudienceRestriction
    {
        $audience = null;
        if (isset($this->state['saml:Audience'])) {
            Assert::allIsInstanceOf($this->state['saml:Audience'], Audience::class);
            $audience = $this->state['saml:Audience'];
        } elseif ($this->srcMetadata->hasValue('saml:Audience')) {
            $audience = $this->srcMetadata->getArrayizeString('saml:Audience');
            $audience = array_map(fn($value): Audience => new Audience($value), $audience);
        }

        if (!empty($audience)) { // Covers both null and the empty array
            return new AudienceRestriction($audience);
        }

        return null;
    }
}
