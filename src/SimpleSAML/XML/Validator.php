<?php

/**
 * This class implements helper functions for XML validation.
 *
 * @package SimpleSAMLphp
 */

declare(strict_types=1);

namespace SimpleSAML\XML;

use DOMNode;
use DOMDocument;
use Exception;
use RobRichards\XMLSecLibs\{XMLSecEnc, XMLSecurityDSig};

use function array_key_exists;
use function in_array;
use function is_string;

class Validator
{
    /**
     * @var string|null This variable contains the X509 certificate the XML document
     *             was signed with, or NULL if it wasn't signed with an X509 certificate.
     */
    private ?string $x509Certificate = null;

    /**
     * @var array|null This variable contains the nodes which are signed.
     */
    private ?array $validNodes = null;


    /**
     * This function initializes the validator.
     *
     * This function accepts an optional parameter $publickey, which is the public key
     * or certificate which should be used to validate the signature. This parameter can
     * take the following values:
     * - NULL/FALSE: No validation will be performed. This is the default.
     * - A string: Assumed to be a PEM-encoded certificate / public key.
     * - An array: Assumed to be an array returned by \SimpleSAML\Utils\Crypto::loadPublicKey.
     *
     * @param \DOMDocument $xmlNode The XML node which contains the Signature element.
     * @param string|array $idAttribute The ID attribute which is used in node references. If
     *          this attribute is NULL (the default), then we will use whatever is the default
     *          ID. Can be eigther a string with one value, or an array with multiple ID
     *          attrbute names.
     * @param array|false|null|string $publickey The public key / certificate which should be
     *          used to validate the XML node.
     * @throws \Exception
     */
    public function __construct(
        DOMDocument $xmlNode,
        string|array|null $idAttribute = null,
        array|false|null|string $publickey = false,
    ) {
        if ($publickey === null) {
            $publickey = false;
        } elseif (is_string($publickey)) {
            $publickey = [
                'PEM' => $publickey,
            ];
        }

        // Create an XML security object
        $objXMLSecDSig = new XMLSecurityDSig();

        // Add the id attribute if the user passed in an id attribute
        if ($idAttribute !== null) {
            if (is_string($idAttribute)) {
                $objXMLSecDSig->idKeys[] = $idAttribute;
            } else {
                foreach ($idAttribute as $ida) {
                    $objXMLSecDSig->idKeys[] = $ida;
                }
            }
        }

        // Locate the XMLDSig Signature element to be used
        $signatureElement = $objXMLSecDSig->locateSignature($xmlNode);
        if (!$signatureElement) {
            throw new Exception('Could not locate XML Signature element.');
        }

        // Canonicalize the XMLDSig SignedInfo element in the message
        $objXMLSecDSig->canonicalizeSignedInfo();

        // Validate referenced xml nodes
        if (!$objXMLSecDSig->validateReference()) {
            throw new Exception('XMLsec: digest validation failed');
        }


        // Find the key used to sign the document
        $objKey = $objXMLSecDSig->locateKey();
        if (empty($objKey)) {
            throw new Exception('Error loading key to handle XML signature');
        }

        // Load the key data
        if ($publickey !== false && array_key_exists('PEM', $publickey)) {
            // We have PEM data for the public key / certificate
            $objKey->loadKey($publickey['PEM']);
        } else {
            // No PEM data. Search for key in signature

            if (!XMLSecEnc::staticLocateKeyInfo($objKey, $signatureElement)) {
                throw new Exception('Error finding key data for XML signature validation.');
            }
        }

        // Check the signature
        if ($objXMLSecDSig->verify($objKey) !== 1) {
            throw new Exception("Unable to validate Signature");
        }

        // Extract the certificate
        $this->x509Certificate = $objKey->getX509Certificate();

        // Find the list of validated nodes
        $this->validNodes = $objXMLSecDSig->getValidatedNodes();
    }


    /**
     * Retrieve the X509 certificate which was used to sign the XML.
     *
     * This function will return the certificate as a PEM-encoded string. If the XML
     * wasn't signed by an X509 certificate, NULL will be returned.
     *
     * @return string|null  The certificate as a PEM-encoded string, or NULL if not signed with an X509 certificate.
     */
    public function getX509Certificate(): ?string
    {
        return $this->x509Certificate;
    }


    /**
     * This function checks if the given XML node was signed.
     *
     * @param \DOMNode $node  The XML node which we should verify that was signed.
     *
     * @return bool  TRUE if this node (or a parent node) was signed. FALSE if not.
     */
    public function isNodeValidated(DOMNode $node): bool
    {
        if ($this->validNodes !== null) {
            while ($node !== null) {
                if (in_array($node, $this->validNodes, true)) {
                    return true;
                }

                $node = $node->parentNode;
            }
        }

        /* Neither this node nor any of the parent nodes could be found in the list of
         * signed nodes.
         */
        return false;
    }
}
