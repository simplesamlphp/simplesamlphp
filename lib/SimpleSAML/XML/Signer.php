<?php

/**
 * A helper class for signing XML.
 *
 * This is a helper class for signing XML documents.
 *
 * @author Olav Morken, UNINETT AS.
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\XML;

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SimpleSAML\Utils\Config;

class Signer
{


    /**
     * @var string The name of the ID attribute.
     */
    private $idAttrName;

    /**
     * @var XMLSecurityKey|bool  The private key (as an XMLSecurityKey).
     */
    private $privateKey;

    /**
     * @var string The certificate (as text).
     */
    private $certificate;


    /**
     * @var string Extra certificates which should be included in the response.
     */
    private $extraCertificates;


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
     *  - privatekey_array The private key, as an array returned from SimpleSAML_Utilities::loadPrivateKey.
     *  - publickey_array  The public key, as an array returned from SimpleSAML_Utilities::loadPublicKey.
     *  - id               The name of the ID attribute.
     *
     * @param array $options  Associative array with options for the constructor. Defaults to an empty array.
     */
    public function __construct($options = array())
    {
        assert('is_array($options)');

        $this->idAttrName = false;
        $this->privateKey = false;
        $this->certificate = false;
        $this->extraCertificates = array();

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
            $this->setIdAttribute($options['id']);
        }
    }


    /**
     * Set the private key from an array.
     *
     * This function loads the private key from an array matching what is returned
     * by SimpleSAML_Utilities::loadPrivateKey(...).
     *
     * @param array $privatekey  The private key.
     */
    public function loadPrivateKeyArray($privatekey)
    {
        assert('is_array($privatekey)');
        assert('array_key_exists("PEM", $privatekey)');

        $this->privateKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type' => 'private'));
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
     * @param string $file  The file which contains the private key. The path is assumed to be relative
     *                      to the cert-directory.
     * @param string|null $pass  The passphrase on the private key. Pass no value or NULL if the private
     *                           key is unencrypted.
     * @param bool $full_path  Whether the filename found in the configuration contains the
     *                         full path to the private key or not. Default to false.
     * @throws \Exception
     */
    public function loadPrivateKey($file, $pass = null, $full_path = false)
    {
        assert('is_string($file)');
        assert('is_string($pass) || is_null($pass)');
        assert('is_bool($full_path)');

        if (!$full_path) {
            $keyFile = Config::getCertPath($file);
        } else {
            $keyFile = $file;
        }

        if (!file_exists($keyFile)) {
            throw new \Exception('Could not find private key file "' . $keyFile . '".');
        }
        $keyData = file_get_contents($keyFile);
        if ($keyData === false) {
            throw new \Exception('Unable to read private key file "' . $keyFile . '".');
        }

        $privatekey = array('PEM' => $keyData);
        if ($pass !== null) {
            $privatekey['password'] = $pass;
        }
        $this->loadPrivateKeyArray($privatekey);
    }


    /**
     * Set the public key / certificate we should include in the signature.
     *
     * This function loads the public key from an array matching what is returned
     * by SimpleSAML_Utilities::loadPublicKey(...).
     *
     * @param array $publickey The public key.
     * @throws \Exception
     */
    public function loadPublicKeyArray($publickey)
    {
        assert('is_array($publickey)');

        if (!array_key_exists('PEM', $publickey)) {
            // We have a public key with only a fingerprint
            throw new \Exception('Tried to add a certificate fingerprint in a signature.');
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
     */
    public function loadCertificate($file, $full_path = false)
    {
        assert('is_string($file)');
        assert('is_bool($full_path)');

        if (!$full_path) {
            $certFile = Config::getCertPath($file);
        } else {
            $certFile = $file;
        }

        if (!file_exists($certFile)) {
            throw new \Exception('Could not find certificate file "' . $certFile . '".');
        }

        $this->certificate = file_get_contents($certFile);
        if ($this->certificate === false) {
            throw new \Exception('Unable to read certificate file "' . $certFile . '".');
        }
    }


    /**
     * Set the attribute name for the ID value.
     *
     * @param string $idAttrName  The name of the attribute which contains the id.
     */
    public function setIDAttribute($idAttrName)
    {
        assert('is_string($idAttrName)');

        $this->idAttrName = $idAttrName;
    }


    /**
     * Add an extra certificate to the certificate chain in the signature.
     *
     * Extra certificates will be added to the certificate chain in the order they
     * are added.
     *
     * @param string $file  The file which contains the certificate, relative to the cert-directory.
     * @param bool $full_path  Whether the filename found in the configuration contains the
     *                         full path to the private key or not. Default to false.
     * @throws \Exception
     */
    public function addCertificate($file, $full_path = false)
    {
        assert('is_string($file)');
        assert('is_bool($full_path)');

        if (!$full_path) {
            $certFile = Config::getCertPath($file);
        } else {
            $certFile = $file;
        }

        if (!file_exists($certFile)) {
            throw new \Exception('Could not find extra certificate file "' . $certFile . '".');
        }

        $certificate = file_get_contents($certFile);
        if ($certificate === false) {
            throw new \Exception('Unable to read extra certificate file "' . $certFile . '".');
        }

        $this->extraCertificates[] = $certificate;
    }


    /**
     * Signs the given DOMElement and inserts the signature at the given position.
     *
     * The private key must be set before calling this function.
     *
     * @param \DOMElement $node  The DOMElement we should generate a signature for.
     * @param \DOMElement $insertInto  The DOMElement we should insert the signature element into.
     * @param \DOMElement $insertBefore  The element we should insert the signature element before. Defaults to NULL,
     *                                   in which case the signature will be appended to the element spesified in
     *                                   $insertInto.
     * @throws \Exception
     */
    public function sign($node, $insertInto, $insertBefore = null)
    {
        assert('$node instanceof DOMElement');
        assert('$insertInto instanceof DOMElement');
        assert('is_null($insertBefore) || $insertBefore instanceof DOMElement ' .
            '|| $insertBefore instanceof DOMComment || $insertBefore instanceof DOMText');

        if ($this->privateKey === false) {
            throw new \Exception('Private key not set.');
        }


        $objXMLSecDSig = new XMLSecurityDSig();
        $objXMLSecDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);

        $options = array();
        if ($this->idAttrName !== false) {
            $options['id_name'] = $this->idAttrName;
        }

        $objXMLSecDSig->addReferenceList(
            array($node),
            XMLSecurityDSig::SHA1,
            array('http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N),
            $options
        );

        $objXMLSecDSig->sign($this->privateKey);


        if ($this->certificate !== false) {
            // Add the certificate to the signature
            $objXMLSecDSig->add509Cert($this->certificate, true);
        }

        // Add extra certificates
        foreach ($this->extraCertificates as $certificate) {
            $objXMLSecDSig->add509Cert($certificate, true);
        }

        $objXMLSecDSig->insertSignature($insertInto, $insertBefore);
    }
}
