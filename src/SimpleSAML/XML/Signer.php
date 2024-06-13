<?php

/**
 * A helper class for signing XML.
 *
 * This is a helper class for signing XML documents.
 *
 * @package simplesamlphp/simplesamlphp
 */

declare(strict_types=1);

namespace SimpleSAML\XML;

use DOMComment;
use DOMElement;
use DOMText;
use Exception;
use RobRichards\XMLSecLibs\{XMLSecurityDSig, XMLSecurityKey};
use SimpleSAML\Assert\Assert;
use SimpleSAML\Utils;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

use function array_key_exists;

class Signer
{
    /**
     * @var string The name of the ID attribute.
     */
    private string $idAttrName = '';

    /**
     * @var \RobRichards\XMLSecLibs\XMLSecurityKey|false  The private key (as an XMLSecurityKey).
     */
    private XMLSecurityKey|false $privateKey = false;

    /**
     * @var string The certificate (as text).
     */
    private string $certificate = '';

    /**
     * @var array Extra certificates which should be included in the response.
     */
    private array $extraCertificates = [];

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private Filesystem $fileSystem;


    /**
     * Constructor for the metadata signer.
     *
     * You can pass an list of options as key-value pairs in the array. This allows you to initialize
     * a metadata signer in one call.
     *
     * The following keys are recognized:
     *  - privatekey       The file with the private key, relative to the cert-directory.
     *  - privatekey_pass  The passphrase for the private key.
     *  - certificate      The file with the certificate, relative to the cert-directory.
     *  - privatekey_array The private key, as an array returned from \SimpleSAML\Utils\Crypto::loadPrivateKey.
     *  - publickey_array  The public key, as an array returned from \SimpleSAML\Utils\Crypto::loadPublicKey.
     *  - id               The name of the ID attribute.
     *
     * @param array $options  Associative array with options for the constructor. Defaults to an empty array.
     */
    public function __construct(array $options = [])
    {
        $this->fileSystem = new Filesystem();

        if (array_key_exists('privatekey', $options)) {
            $pass = null;
            if (array_key_exists('privatekey_pass', $options)) {
                $pass = $options['privatekey_pass'];
            }

            $this->loadPrivateKey($options['privatekey'], $pass);
        }

        if (array_key_exists('certificate', $options)) {
            $this->loadCertificate($options['certificate']);
        }

        if (array_key_exists('privatekey_array', $options)) {
            $this->loadPrivateKeyArray($options['privatekey_array']);
        }

        if (array_key_exists('publickey_array', $options)) {
            $this->loadPublicKeyArray($options['publickey_array']);
        }

        if (array_key_exists('id', $options)) {
            $this->setIDAttribute($options['id']);
        }
    }


    /**
     * Set the private key from an array.
     *
     * This function loads the private key from an array matching what is returned
     * by \SimpleSAML\Utils\Crypto::loadPrivateKey(...).
     *
     * @param array $privatekey  The private key.
     */
    public function loadPrivateKeyArray(array $privatekey): void
    {
        Assert::keyExists($privatekey, 'PEM');

        $this->privateKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        if (array_key_exists('password', $privatekey)) {
            $this->privateKey->passphrase = $privatekey['password'];
        }
        $this->privateKey->loadKey($privatekey['PEM'], false);
    }


    /**
     * Set the private key.
     *
     * Will throw an exception if unable to load the private key.
     *
     * @param string $location  The location which contains the private key
     * @param string|null $pass  The passphrase on the private key. Pass no value or NULL if the private
     *                           key is unencrypted.
     * @param bool $full_path  Whether the location found in the configuration contains the
     *                         full path to the private key or not (only relevant to file locations).
     *                         Default to false.
     * @throws \Exception
     */
    public function loadPrivateKey(
        string $location,
        #[\SensitiveParameter]
        ?string $pass,
        bool $full_path = false,
    ): void {
        $cryptoUtils = new Utils\Crypto();
        $keyData = $cryptoUtils->retrieveKey($location, $full_path);

        if ($keyData === null) {
            throw new Exception('Could not find private key location "' . $location . '".');
        }


        $privatekey = ['PEM' => $keyData];
        if ($pass !== null) {
            $privatekey['password'] = $pass;
        }
        $this->loadPrivateKeyArray($privatekey);
    }


    /**
     * Set the public key / certificate we should include in the signature.
     *
     * This function loads the public key from an array matching what is returned
     * by \SimpleSAML\Utils\Crypto::loadPublicKey(...).
     *
     * @param array $publickey The public key.
     * @throws \Exception
     */
    public function loadPublicKeyArray(array $publickey): void
    {
        if (!array_key_exists('PEM', $publickey)) {
            // We have a public key with only a fingerprint
            throw new Exception('Tried to add a certificate fingerprint in a signature.');
        }

        // For now, we only assume that the public key is an X509 certificate
        $this->certificate = $publickey['PEM'];
    }


    /**
     * Set the certificate we should include in the signature.
     *
     * If this function isn't called, no certificate will be included.
     * Will throw an exception if unable to load the certificate.
     *
     * @param string $file  The file which contains the certificate. The path is assumed to be relative to
     *                      the cert-directory.
     * @param bool $full_path  Whether the filename found in the configuration contains the
     *                         full path to the private key or not. Default to false.
     * @throws \Exception
     * @throws \Symfony\Component\HttpFoundation\File\Exception\FileException
     */
    public function loadCertificate(string $file, bool $full_path = false): void
    {
        if (!$full_path) {
            $configUtils = new Utils\Config();
            $certFile = $configUtils->getCertPath($file);
        } else {
            $certFile = $file;
        }

        if (!$this->fileSystem->exists($certFile)) {
            throw new Exception('Could not find certificate file "' . $certFile . '".');
        }

        $file = new File($certFile);
        $this->certificate = $file->getContent();
    }


    /**
     * Set the attribute name for the ID value.
     *
     * @param string $idAttrName  The name of the attribute which contains the id.
     */
    public function setIDAttribute(string $idAttrName): void
    {
        $this->idAttrName = $idAttrName;
    }


    /**
     * Add an extra certificate to the certificate chain in the signature.
     *
     * Extra certificates will be added to the certificate chain in the order they
     * are added.
     *
     * @param string $location The location which contains the certificate
     * @param bool $full_path  Whether the location found in the configuration contains the
     *                         full path to the private key or not (only relevant to file locations).
     *                         Default to false.
     * @throws \Exception
     */
    public function addCertificate(string $location, bool $full_path = false): void
    {
        $cryptoUtils = new Utils\Crypto();
        $certData = $cryptoUtils->retrieveCertificate($location, $full_path);

        if ($certData === null) {
            throw new Exception('Could not find extra certificate location "' . $location . '".');
        }

        $this->extraCertificates[] = $certData;
    }


    /**
     * Signs the given DOMElement and inserts the signature at the given position.
     *
     * The private key must be set before calling this function.
     *
     * @param \DOMElement $node  The DOMElement we should generate a signature for.
     * @param \DOMElement $insertInto  The DOMElement we should insert the signature element into.
     * @param \DOMElement|\DOMComment|\DOMText $insertBefore
     *  The element we should insert the signature element before. Defaults to NULL,
     *  in which case the signature will be appended to the element specified in $insertInto.
     * @throws \Exception
     */
    public function sign(
        DOMElement $node,
        DOMElement $insertInto,
        DOMElement|DOMComment|DOMText|null $insertBefore = null,
    ): void {
        $privateKey = $this->privateKey;
        if ($privateKey === false) {
            throw new Exception('Private key not set.');
        }


        $objXMLSecDSig = new XMLSecurityDSig();
        $objXMLSecDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);

        $options = [];
        if (!empty($this->idAttrName)) {
            $options['id_name'] = $this->idAttrName;
        }

        $objXMLSecDSig->addReferenceList(
            [$node],
            XMLSecurityDSig::SHA256,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N],
            $options,
        );

        $objXMLSecDSig->sign($privateKey);

        // Add the certificate to the signature
        $objXMLSecDSig->add509Cert($this->certificate, true);

        // Add extra certificates
        foreach ($this->extraCertificates as $certificate) {
            $objXMLSecDSig->add509Cert($certificate, true);
        }

        $objXMLSecDSig->insertSignature($insertInto, $insertBefore);
    }
}
