<?php
/*
* COAUTHOR: Samuel Muñoz Hidalgo
* EMAIL: samuel.mh@gmail.com
* LAST REVISION: 22-DEC-08
* DESCRIPTION: modified validatexmlsignature
*/

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_InfoCard
 * @subpackage Zend_InfoCard_Xml_Security
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Security.php 9094 2008-03-30 18:36:55Z thomas $
 */

/**
 * Zend_InfoCard_Xml_Security_Transform
 */
require_once 'Zend_InfoCard_Xml_Security_Transform.php';

/**
 *
 * @category   Zend
 * @package    Zend_InfoCard
 * @subpackage Zend_InfoCard_Xml_Security
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
 
/*
* ÚLTIMA REVISIÓN: 4-DEC-2008
*/

class Zend_InfoCard_Xml_Security
{
    /**
     * ASN.1 type INTEGER class
     */
    const ASN_TYPE_INTEGER = 0x02;

    /**
     * ASN.1 type BIT STRING class
     */
    const ASN_TYPE_BITSTRING = 0x03;

    /**
     * ASN.1 type SEQUENCE class
     */
    const ASN_TYPE_SEQUENCE = 0x30;

    /**
     * The URI for Canonical Method C14N Exclusive
     */
    const CANONICAL_METHOD_C14N_EXC = 'http://www.w3.org/2001/10/xml-exc-c14n#';

    /**
     * The URI for Signature Method SHA1
     */
    const SIGNATURE_METHOD_SHA1 = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';

    /**
     * The URI for Digest Method SHA1
     */
    const DIGEST_METHOD_SHA1 = 'http://www.w3.org/2000/09/xmldsig#sha1';

    /**
     * The Identifier for RSA Keys
     */
    const RSA_KEY_IDENTIFIER = '300D06092A864886F70D0101010500';

    /**
     * Constructor  (disabled)
     *
     * @return void
     */
    private function __construct()
    {
    }

    /**
     * Validates the signature of a provided XML block
     *
     * @param  string $strXMLInput An XML block containing a Signature
     * @return bool True if the signature validated, false otherwise
     * @throws Exception
     */


static public function validateXMLSignature($strXMLInput, $sts_crt=NULL){
	if(!extension_loaded('openssl')) {
		throw new Exception("You must have the openssl extension installed to use this class");
	}

	$sxe = simplexml_load_string($strXMLInput);

	if ($sts_crt != NULL){
		$sxe->registerXPathNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
		list($keyValue) = $sxe->xpath("//ds:Signature/ds:KeyInfo");
		$keyValue->registerXPathNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
		list($x509cert) = $keyValue->xpath("ds:X509Data/ds:X509Certificate");
		list($rsaKeyValue) = $keyValue->xpath("ds:KeyValue/ds:RSAKeyValue");
		//Extract the XMLToken issuer public key
		switch(true) {
			case isset($x509cert):
				SimpleSAML_Logger::debug("Public Key: x509cert");
				$certificate = (string)$x509cert;
				$cert_issuer = "-----BEGIN CERTIFICATE-----\n".wordwrap($certificate, 64, "\n", true)."\n-----END CERTIFICATE-----";
				if (!$t_key = openssl_pkey_get_public($cert_issuer)) {
					throw new Exception("Wrong token certificate");
				}
				$t_det = openssl_pkey_get_details($t_key);
				$pem_issuer = $t_det['key'];
				break;
			case isset($rsaKeyValue):
				$rsaKeyValue->registerXPathNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
				list($modulus) = $rsaKeyValue->xpath("ds:Modulus");
				list($exponent) = $rsaKeyValue->xpath("ds:Exponent");
				if(!isset($modulus) || !isset($exponent)) {
					throw new Exception("RSA Key Value not in Modulus/Exponent form");
				}
				$modulus = base64_decode((string)$modulus);
				$exponent = base64_decode((string)$exponent);
				$pem_issuer = self::_getPublicKeyFromModExp($modulus, $exponent);
				break;
			default:
				SimpleSAML_Logger::debug("Public Key: Unknown");
				throw new Exception("Unable to determine or unsupported representation of the KeyValue block");
		}
			
		//Check isuer public key against configured one
		$checkcert = file_get_contents($sts_crt);
		$check_key = openssl_pkey_get_public($checkcert);
		$checkData = openssl_pkey_get_details($check_key);
		$pem_local = $checkData['key'];
		
		if ( strcmp($pem_issuer,$pem_local)!=0 ) {
			SimpleSAML_Logger::debug("Configured STS cert and received STS cert mismatch");
			openssl_free_key($check_key);
			throw new Exception("Configured STS cert and received STS cert mismatch");			
		}	
		
		//Validate XML signature	
	
		$sxe->registerXPathNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
		
		list($canonMethod) = $sxe->xpath("//ds:Signature/ds:SignedInfo/ds:CanonicalizationMethod");
		switch((string)$canonMethod['Algorithm']) {
			case self::CANONICAL_METHOD_C14N_EXC:
				$cMethod = (string)$canonMethod['Algorithm'];
				break;
			default:
					throw new Exception("Unknown or unsupported CanonicalizationMethod Requested");
		}
		
		list($signatureMethod) = $sxe->xpath("//ds:Signature/ds:SignedInfo/ds:SignatureMethod");
		switch((string)$signatureMethod['Algorithm']) {
			case self::SIGNATURE_METHOD_SHA1:
				$sMethod = (string)$signatureMethod['Algorithm'];
				break;
			default:
				throw new Exception("Unknown or unsupported SignatureMethod Requested");
		}
		
		list($digestMethod) = $sxe->xpath("//ds:Signature/ds:SignedInfo/ds:Reference/ds:DigestMethod");
		switch((string)$digestMethod['Algorithm']) {
			case self::DIGEST_METHOD_SHA1:
				$dMethod = (string)$digestMethod['Algorithm'];
				break;
			default:
				throw new Exception("Unknown or unsupported DigestMethod Requested");
		}
		
		$base64DecodeSupportsStrictParam = version_compare(PHP_VERSION, '5.2.0', '>=');
		
		list($digestValue) = $sxe->xpath("//ds:Signature/ds:SignedInfo/ds:Reference/ds:DigestValue");
		if ($base64DecodeSupportsStrictParam) {
			$dValue = base64_decode((string)$digestValue, true);
		} else {
			$dValue = base64_decode((string)$digestValue);
		}
		
		
		list($signatureValueElem) = $sxe->xpath("//ds:Signature/ds:SignatureValue");
		if ($base64DecodeSupportsStrictParam) {
			$signatureValue = base64_decode((string)$signatureValueElem, true);
		} else {
			$signatureValue = base64_decode((string)$signatureValueElem);
		}
		
		$transformer = new Zend_InfoCard_Xml_Security_Transform();
	
		$transforms = $sxe->xpath("//ds:Signature/ds:SignedInfo/ds:Reference/ds:Transforms/ds:Transform");
		while(list( , $transform) = each($transforms)) {
			$transformer->addTransform((string)$transform['Algorithm']);
		}		
		$transformed_xml = $transformer->applyTransforms($strXMLInput);		
		$transformed_xml_binhash = pack("H*", sha1($transformed_xml));		
		if($transformed_xml_binhash != $dValue) {
			throw new Exception("Locally Transformed XML (".$transformed_xml_binhash.") does not match XML Document  (".$dValue."). Cannot Verify Signature");
		}		
	
		$transformer = new Zend_InfoCard_Xml_Security_Transform();
		$transformer->addTransform((string)$canonMethod['Algorithm']);
		list($signedInfo) = $sxe->xpath("//ds:Signature/ds:SignedInfo");
		//SimpleSAML_Logger::debug
		//print ("signedinfo ".$sxe->saveXML());
		$signedInfoXML = self::addNamespace($signedInfo, "http://www.w3.org/2000/09/xmldsig#");
		SimpleSAML_Logger::debug("canonicalizo ".$signedInfoXML);
		$canonical_signedinfo = $transformer->applyTransforms($signedInfoXML);
		if (openssl_verify($canonical_signedinfo,$signatureValue,$check_key)) {
			list($reference) = $sxe->xpath("//ds:Signature/ds:SignedInfo/ds:Reference");
			openssl_free_key($check_key);
			return (string)$reference['URI'];
		} else {
			openssl_free_key($check_key);
			throw new Exception("Could not validate the XML signature");
		}
	} else {
		$sxe->registerXPathNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
		list($reference) = $sxe->xpath("//ds:Signature/ds:SignedInfo/ds:Reference");
		return (string)$reference['URI'];
	}
	return false;
}


    private function addNamespace($xmlElem, $ns) {  
    		$schema = '.*<[^<]*SignedInfo[^>]*'.$ns.'.*>.*';
				$pattern='/\//';
				$replacement='\/';
				$nspattern= '/'.preg_replace($pattern,$replacement,$schema).'/';
    		if (preg_match($nspattern,$xmlElem->asXML())>0){ //M$ Cardspaces
					$xml = $xmlElem->asXML();
    		}
    		else { //Digitalme
					$xmlElem->addAttribute('DS_NS', $ns);
					$xml = $xmlElem->asXML();
					if(preg_match("/<(\w+)\:\w+/", $xml, $matches)) {
						$prefix = $matches[1];
						$xml = str_replace("DS_NS", "xmlns:" . $prefix, $xml);
					}
					else {
						$xml = str_replace("DS_NS", "xmlns", $xml);
					}
        }
        return $xml;
    }

    /**
     * Transform an RSA Key in Modulus/Exponent format into a PEM encoding and
     * return an openssl resource for it
     *
     * @param string $modulus The RSA Modulus in binary format
     * @param string $exponent The RSA exponent in binary format
     * @return string The PEM encoded version of the key
     */
    static protected function _getPublicKeyFromModExp($modulus, $exponent)
    {
        $modulusInteger  = self::_encodeValue($modulus, self::ASN_TYPE_INTEGER);
        $exponentInteger = self::_encodeValue($exponent, self::ASN_TYPE_INTEGER);
        $modExpSequence  = self::_encodeValue($modulusInteger . $exponentInteger, self::ASN_TYPE_SEQUENCE);
        $modExpBitString = self::_encodeValue($modExpSequence, self::ASN_TYPE_BITSTRING);

        $binRsaKeyIdentifier = pack( "H*", self::RSA_KEY_IDENTIFIER );

        $publicKeySequence = self::_encodeValue($binRsaKeyIdentifier . $modExpBitString, self::ASN_TYPE_SEQUENCE);

        $publicKeyInfoBase64 = base64_encode( $publicKeySequence );

        $publicKeyString = "-----BEGIN PUBLIC KEY-----\n";
        $publicKeyString .= wordwrap($publicKeyInfoBase64, 64, "\n", true);
        $publicKeyString .= "\n-----END PUBLIC KEY-----\n";

        return $publicKeyString;
    }

    /**
     * Encode a limited set of data types into ASN.1 encoding format
     * which is used in X.509 certificates
     *
     * @param string $data The data to encode
     * @param const $type The encoding format constant
     * @return string The encoded value
     * @throws Exception
     */
    static protected function _encodeValue($data, $type)
    {
        // Null pad some data when we get it (integer values > 128 and bitstrings)
        if( (($type == self::ASN_TYPE_INTEGER) && (ord($data) > 0x7f)) ||
            ($type == self::ASN_TYPE_BITSTRING)) {
                $data = "\0$data";
        }

        $len = strlen($data);

        // encode the value based on length of the string
        // I'm fairly confident that this is by no means a complete implementation
        // but it is enough for our purposes
        switch(true) {
            case ($len < 128):
                return sprintf("%c%c%s", $type, $len, $data);
            case ($len < 0x0100):
                return sprintf("%c%c%c%s", $type, 0x81, $len, $data);
            case ($len < 0x010000):
                return sprintf("%c%c%c%c%s", $type, 0x82, $len / 0x0100, $len % 0x0100, $data);
            default:
                throw new Exception("Could not encode value");
        }

        throw new Exception("Invalid code path");
    }
}
