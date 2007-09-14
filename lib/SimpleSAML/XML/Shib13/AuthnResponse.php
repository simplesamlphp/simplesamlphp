<?php


/**
 * SimpleSAMLphp
 *
 * LICENSE: See the COPYING file included in this distribution.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 */
 
require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/XML/MetaDataStore.php');
require_once('SimpleSAML/XML/AuthnResponse.php');

require_once('xmlseclibs.php');
 
/**
 * Configuration of SimpleSAMLphp
 */
class SimpleSAML_XML_Shib13_AuthnResponse extends SimpleSAML_XML_AuthnResponse {

	private $configuration = null;
	private $metadata = 'default.php';
	
	private $message = null;
	private $dom;
	private $relayState = null;
	
	private $validIDs = null;
	
	const PROTOCOL = 'urn:oasis:names:tc:SAML:2.0';
	const SHIB_PROTOCOL_NS = 'urn:oasis:names:tc:SAML:1.0:protocol';
	const SHIB_ASSERT_NS = 'urn:oasis:names:tc:SAML:1.0:assertion';

	function __construct(SimpleSAML_Configuration $configuration, SimpleSAML_XML_MetaDataStore $metadatastore) {
		$this->configuration = $configuration;
		$this->metadata = $metadatastore;
	}
	
	// Inhereted public function setXML($xml) {
	// Inhereted public function getXML() {
	// Inhereted public function setRelayState($relayState) {
	// Inhereted public function getRelayState() {

	
	public function validate() {
	
		$dom = $this->getDOM();
		
		/* Create an XML security object, and register ID as the id attribute for sig references. */
		$objXMLSecDSig = new XMLSecurityDSig();
		$objXMLSecDSig->idKeys[] = 'ResponseID';
		
		/* Locate the signature element to be used. */
		$objDSig = $objXMLSecDSig->locateSignature($dom);
		

		/* If no signature element was found, throw an error */
		if (!$objDSig) {
			throw new Exception("Could not locate XML Signature element in Authentication Response");
		}
		
		
		/* Must check certificate fingerprint now - validateReference removes it */        
		// TODO FIX"!!!
		if ( ! $this->validateCertFingerprint($objDSig) ) {
			throw new Exception("Fingerprint Validation Failed");
		}

		/* Get information about canoncalization in to the xmlsec library. Read from the siginfo part. */
		$objXMLSecDSig->canonicalizeSignedInfo();
		
		$refids = $objXMLSecDSig->getRefIDs();
		
		
		
		/* Validate refrences */
		$retVal = $objXMLSecDSig->validateReference();
		if (! $retVal) {
			throw new Exception("XMLsec: digest validation failed");
		}

		$key = NULL;
		$objKey = $objXMLSecDSig->locateKey();
	
		if ($objKey) {
			if ($objKeyInfo = XMLSecEnc::staticLocateKeyInfo($objKey, $objDSig)) {
				/* Handle any additional key processing such as encrypted keys here */
			}
		}
	
		if (empty($objKey)) {
			throw new Exception("Error loading key to handle Signature");
		}

		if (! $objXMLSecDSig->verify($objKey)) {
			throw new Exception("Unable to validate Signature");
		}
		
		$this->validIDs = $refids;
		return true;
	}
	
	
	
	
	function validateCertFingerprint($dom) {
//		$dom = $this->getDOM();
		$fingerprint = "";
		
		
		// Find the certificate in the document.
		if ($x509certNodes = $dom->getElementsByTagName('X509Certificate')) {
			if ($x509certNodes->length > 0) {
				$x509cert = $x509certNodes->item(0)->textContent;
				$x509data = base64_decode( $x509cert );
				$fingerprint = strtolower( sha1( $x509data ) );
			}
		}
	
		// Get the issuer of the assertion.
		$issuer = $this->getIssuer();
		
		//echo 'found issuer: ' . $this->getIssuer();
		$md = $this->metadata->getMetaData($issuer, 'shib13-idp-remote');
		
		/*
		 * Get fingerprint from saml20-idp-remote metadata...
		 * 
		 * Accept fingerprints with or without colons, case insensitive
		 */
		$issuerFingerprint = strtolower( str_replace(":", "", $md['certFingerprint']) );
	
		//echo 'issuer fingerprint: ' . $issuerFingerprint;
		
		if (empty($issuerFingerprint)) {
			throw new Exception("Certificate finger print for entity ID [" . $issuer . "] in metadata was empty.");
		}
		if (empty($fingerprint)) {
			throw new Exception("Certificate finger print in message was empty.");
		}

		if ($fingerprint != $issuerFingerprint) {
			throw new Exception("Expecting certificate fingerprint [$issuerFingerprint] but got [$fingerprint]");
		}
	
		return ($fingerprint == $issuerFingerprint);
	}
	
	
	public function createSession() {
	
		SimpleSAML_Session::init(self::PROTOCOL, $this, true);
		$session = SimpleSAML_Session::getInstance();
		$session->setAttributes($this->getAttributes());
		
		$nameid = $this->getNameID();
		
		$session->setNameID($nameid['NameID']);
		$session->setNameIDFormat($nameid['Format']);
		$session->setSessionIndex($this->getSessionIndex());
		/*
		$nameID["NameID"] = $node->nodeValue;
		
				$nameID["NameQualifier"] = $node->getAttribute('NameQualifier');
				$nameID["SPNameQualifier"] = $node->getAttribute('SPNameQualifier');
		*/
		return $session;
	}
	
	//TODO
	function getSessionIndex() {
		$token = $this->getDOM();
		if ($token instanceof DOMDocument) {
			$xPath = new DOMXpath($token);
			$xPath->registerNamespace('mysamlp', self::SHIB_PROTOCOL_NS);
			$xPath->registerNamespace('mysaml', self::SHIB_ASSERT_NS);
			
			$query = '/mysamlp:Response/mysaml:Assertion/mysaml:AuthnStatement';
			$nodelist = $xPath->query($query);
			if ($node = $nodelist->item(0)) {
				return $node->getAttribute('SessionIndex');
			}
		}
		return NULL;
	}

	
	public function getAttributes() {


		$md = $this->metadata->getMetadata($this->getIssuer(), 'shib13-idp-remote');
		
		//$base64 = isset($md['base64attributes']) ? $md['base64attributes'] : false;
		
		/*
		define('SAML2_BINDINGS_POST', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST');
		define('SAML2_STATUS_SUCCESS', 'urn:oasis:names:tc:SAML:2.0:status:Success');
		*/
		
		/*
		echo 'Validids<pre>';
		print_r($this->validIDs);
		echo '</pre>';
		*/

		$attributes = array();
		$token = $this->getDOM();
			
		
		//echo $this->getXML();
		
		$attributes = array();
		
		if ($token instanceof DOMDocument) {
		
			$sxml = simplexml_import_dom($token);
			
			$sxml->registerXPathNamespace('samlp', self::SHIB_PROTOCOL_NS);
			$sxml->registerXPathNamespace('saml', self::SHIB_ASSERT_NS);
			

			
			$assertions = $sxml->xpath('/samlp:Response[@ResponseID="' . $this->validIDs[0] . '"]/saml:Assertion');

			foreach ($assertions AS $assertion) {				

				if ($assertion->Conditions) {

					if (($start = (string)$assertion->Conditions['NotBefore']) &&
						($end = (string)$assertion->Conditions['NotOnOrAfter'])) {
	
						if (! SimpleSAML_Utilities::checkDateConditions($start, $end)) {
							error_log( " Date check failed ... (from $start to $end)");
							next;
						} 

					}

				}
				
				if (isset($assertion->AttributeStatement->Attribute)) {
					foreach ($assertion->AttributeStatement->Attribute AS $attribute) {
						$values = array();
						foreach ($attribute->AttributeValue AS $val) {
							$values[] = (string) $val;
						}
						
						$attributes[(string)$attribute['AttributeName']] = $values;
					}
				}
				
			}
		
		
			/*
			echo "<PRE>token:";
			echo htmlentities($token->saveXML());
			echo ":</PRE>";
			*/
			/*
			echo '<pre>Attributes: ';
			print_r($attributes);
			echo '</pre>';
	*/
		}
		return $attributes;
		
		
	}

	
	public function getIssuer() {
	
		$token = $this->getDOM();
		$xPath = new DOMXpath($token);
		$xPath->registerNamespace('mysamlp', self::SHIB_PROTOCOL_NS);
		$xPath->registerNamespace('mysaml', self::SHIB_ASSERT_NS);

		$query = '/mysamlp:Response/mysaml:Assertion/@Issuer';
		$nodelist = $xPath->query($query);

		if ($attr = $nodelist->item(0)) {
			return $attr->value;
		} else {
			throw Exception('Could not find Issuer field in Authentication response');
		}

	}
	
	public function getNameID() {
		
		
		$token = $this->getDOM();
		$nameID = array();
		if ($token instanceof DOMDocument) {
			$xPath = new DOMXpath($token);
			$xPath->registerNamespace('mysamlp', self::SHIB_PROTOCOL_NS);
			$xPath->registerNamespace('mysaml', self::SHIB_ASSERT_NS);
	
			$query = '/mysamlp:Response/mysaml:Assertion/mysaml:AuthenticationStatement/mysaml:Subject/mysaml:NameIdentifier';
			$nodelist = $xPath->query($query);
			if ($node = $nodelist->item(0)) {
				$nameID["NameID"] = $node->nodeValue;
				$nameID["Format"] = $node->getAttribute('Format');
				$nameID["NameQualifier"] = $node->getAttribute('NameQualifier');
			}
		}
		return $nameID;

	}
	

	// Not updated for response. from request.
	public function generate($idpentityid, $spentityid, $inresponseto, $nameid, $attributes) {
	
		//echo 'idp:' . $idpentityid . ' sp:' . $spentityid .' inresponseto:' .  $inresponseto . ' namid:' . $nameid;
	
		$idpmd 	= $this->metadata->getMetaData($idpentityid, 'saml20-idp-hosted');
		$spmd 	= $this->metadata->getMetaData($spentityid, 'saml20-sp-remote');
		
		$id = self::generateID();
		$issueInstant = self::generateIssueInstant();
		$assertionExpire = self::generateIssueInstant(60 * 5); # 5 minutes
		
		$assertionid = self::generateID();
		$sessionindex = self::generateID();
		
		if (is_null($nameid)) {
			$nameid = self::generateID();
		}

		$issuer = $idpentityid;

		$assertionConsumerServiceURL = $spmd['assertionConsumerServiceURL'];
		$spNameQualifier = $spmd['spNameQualifier'];
		
		$destination = $spmd['assertionConsumerServiceURL'];
		
		$encodedattributes = '';
		foreach ($attributes AS $name => $value) {
			$encodedattributes .= $this->enc_attribute($name, $value[0], true);
		}
		
		$authnResponse = '<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
    ID="' . $id . '"
    InResponseTo="' . $inresponseto. '" Version="2.0"
    IssueInstant="' . $issueInstant . '"
    Destination="' . $destination . '">
    <saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">' . $issuer . '</saml:Issuer>
    <samlp:Status xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol">
        <samlp:StatusCode xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
            Value="urn:oasis:names:tc:SAML:2.0:status:Success"> </samlp:StatusCode>
    </samlp:Status>
    <saml:Assertion xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" Version="2.0"
        ID="' . $assertionid . '" IssueInstant="' . $issueInstant . '">
        <saml:Issuer>' . $issuer . '</saml:Issuer>
        <saml:Subject>
            <saml:NameID NameQualifier="' . $issuer . '" SPNameQualifier="'. $spentityid. '"
                Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient"
                >' . $nameid. '</saml:NameID>
            <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
                <saml:SubjectConfirmationData NotOnOrAfter="' . $assertionExpire . '"
                    InResponseTo="' . $inresponseto. '"
                    Recipient="' . $destination . '"/>
            </saml:SubjectConfirmation>
        </saml:Subject>
        <saml:Conditions NotBefore="' . $issueInstant. '" NotOnOrAfter="' . $assertionExpire. '">
            <saml:AudienceRestriction>
                <saml:Audience>' . $spentityid . '</saml:Audience>
            </saml:AudienceRestriction>
        </saml:Conditions>
        <saml:AuthnStatement AuthnInstant="' . $issueInstant . '"
            SessionIndex="' . $sessionindex . '">
            <saml:AuthnContext>
                <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef>
            </saml:AuthnContext>
        </saml:AuthnStatement>
        <saml:AttributeStatement>
            ' . $encodedattributes . '
        </saml:AttributeStatement>
    </saml:Assertion>
</samlp:Response>
';
		  
		return $authnResponse;
	}


	

	
}

?>