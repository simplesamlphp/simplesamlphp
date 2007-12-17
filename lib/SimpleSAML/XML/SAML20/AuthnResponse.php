<?php


/**
 * SimpleSAMLphp
 *
 * PHP versions 4 and 5
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
class SimpleSAML_XML_SAML20_AuthnResponse extends SimpleSAML_XML_AuthnResponse {

	private $configuration = null;
	private $metadata = 'default.php';
	
	private $message = null;
	private $dom;
	private $relayState = null;
	
	private $validIDs = null;
	
	const PROTOCOL = 'urn:oasis:names:tc:SAML:2.0';
	
	const TRANSIENT 	= 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient';
	const EMAIL 		= 'urn:oasis:names:tc:SAML:2.0:nameid-format:email';

	/* Namespaces used in the XML representation of this object.
	 * TODO: Move these constants into a generic SAML2-class?
	 */
	const SAML2_ASSERT_NS = 'urn:oasis:names:tc:SAML:2.0:assertion';
	const SAML2_PROTOCOL_NS = 'urn:oasis:names:tc:SAML:2.0:protocol';
	
	

	function __construct(SimpleSAML_Configuration $configuration, SimpleSAML_XML_MetaDataStore $metadatastore) {
		$this->configuration = $configuration;
		$this->metadata = $metadatastore;
	}

	
	public function validate() {
	
		$dom = $this->getDOM();
		
		/* Create an XML security object, and register ID as the id attribute for sig references. */
		$objXMLSecDSig = new XMLSecurityDSig();
		$objXMLSecDSig->idKeys[] = 'ID';
		
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
		$md = $this->metadata->getMetaData($issuer, 'saml20-idp-remote');
		
		/*
		 * Get fingerprint from saml20-idp-remote metadata...
		 * 
		 * Accept fingerprints with or without colons, case insensitive
		 */
		$issuerFingerprint = strtolower( str_replace(":", "", $md['certFingerprint']) );
	

		
		if (empty($issuerFingerprint)) {
			throw new Exception("Certificate finger print for entity ID [" . $issuer . "] in metadata was empty.");
		}
		if (empty($fingerprint)) {
			throw new Exception("Certificate finger print in message was empty.");
		}

		if ($fingerprint != $issuerFingerprint) {
			throw new Exception("Expecting fingerprint $issuerFingerprint but got fingerprint $fingerprint .");
		}
	
		return ($fingerprint == $issuerFingerprint);
	}
	
	
	public function createSession() {
	
	//($protocol, $message = null, $authenticated = true) {
		SimpleSAML_Session::init(self::PROTOCOL, $this, true);
		$session = SimpleSAML_Session::getInstance();
		$session->setAttributes($this->getAttributes());
		
		
		$nameid = $this->getNameID();
		
		$session->setNameID($nameid['NameID']);
		$session->setNameIDFormat($nameid['Format']);
		$session->setSessionIndex($this->getSessionIndex());
		$session->setIdP($this->getIssuer());
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
			$xPath->registerNamespace('mysaml', self::SAML2_ASSERT_NS);
			$xPath->registerNamespace('mysamlp', self::SAML2_PROTOCOL_NS);
	
			$query = '/mysamlp:Response/mysaml:Assertion/mysaml:AuthnStatement';
			$nodelist = $xPath->query($query);
			if ($node = $nodelist->item(0)) {
				return $node->getAttribute('SessionIndex');
			}
		}
		return NULL;
	}

	
	public function getAttributes() {


		$md = $this->metadata->getMetadata($this->getIssuer(), 'saml20-idp-remote');
		
		$base64 = isset($md['base64attributes']) ? $md['base64attributes'] : false;
		
		/*
		echo 'Validids<pre>';
		print_r($this->validIDs);
		echo '</pre>';
		*/

		$attributes = array();
		$token = $this->getDOM();
		
		
		//echo '<pre>' . $this->getXML() . '</pre>';
		
		
		if ($token instanceof DOMDocument) {
		
			/*
			echo "<PRE>token:";
			echo htmlentities($token->saveXML());
			echo ":</PRE>";
			*/
			
			$xPath = new DOMXpath($token);
			$xPath->registerNamespace("mysaml", self::SAML2_ASSERT_NS);
			$xPath->registerNamespace("mysamlp", self::SAML2_PROTOCOL_NS);
			$query = "/mysamlp:Response/mysaml:Assertion/mysaml:Conditions";
			$nodelist = $xPath->query($query);
		
			if ($node = $nodelist->item(0)) {
		
				$start = $node->getAttribute("NotBefore");
				$end = $node->getAttribute("NotOnOrAfter");
	
				if (! SimpleSAML_Utilities::checkDateConditions($start, $end)) {
					error_log( " Date check failed ... (from $start to $end)");
		
					return $attributes;
				}
			}
	
			$valididqueries = array();
			foreach ($this->validIDs AS $vid) {
				$valididqueries[] = "@ID='" . $vid . "'";
			}
			$valididquery = join(' or ', $valididqueries);
			
	
			foreach (
				array(
					"/mysamlp:Response[" . $valididquery . "]/mysaml:Assertion/mysaml:AttributeStatement/mysaml:Attribute",
					"/mysamlp:Response/mysaml:Assertion[" . $valididquery . "]/mysaml:AttributeStatement/mysaml:Attribute") AS $query) {
		
//				echo 'performing query : ' . $query;
		
//				$query = "/mysamlp:Response[" . $valididquery . "]/mysaml:Assertion/mysaml:AttributeStatement/mysaml:Attribute";
				$nodelist = $xPath->query($query);
				

				
//				if (is_array($nodelist)) {
					

					foreach ($nodelist AS $node) {

						if ($name = $node->getAttribute("Name")) {
//							echo "Name ";
							$value = array();
							foreach ($node->childNodes AS $child) {
								if ($child->localName == "AttributeValue") {
									$newvalue = $child->textContent;
									if ($base64) {
										$values = explode('_', $newvalue);
										foreach($values AS $v) {
											$value[] = base64_decode($v);
										}
									} else {
		
										$value[] = $newvalue;
									}
								}
							}
							$attributes[$name] = $value;
						}
					}
					
//				}
				
			}
			
			
			
		}
/*
		echo '<p>Attributes<pre>';
		print_r($attributes);
		echo '</pre>';
*/
		return $attributes;
	}

	
	public function getIssuer() {
		$dom = $this->getDOM();
		$issuer = null;
		if ($issuerNodes = $dom->getElementsByTagName('Issuer')) {
			if ($issuerNodes->length > 0) {
				$issuer = $issuerNodes->item(0)->textContent;
			}
		}
		return $issuer;
	}
	
	public function getNameID() {
		
		$dom = $this->getDOM();
		$nameID = array();
		
		if ($dom instanceof DOMDocument) {
			$xPath = new DOMXpath($dom);
			$xPath->registerNamespace('mysaml', self::SAML2_ASSERT_NS);
			$xPath->registerNamespace('mysamlp', self::SAML2_PROTOCOL_NS);
	
			$query = '/mysamlp:Response/mysaml:Assertion/mysaml:Subject/mysaml:NameID';
			$nodelist = $xPath->query($query);
			if ($node = $nodelist->item(0)) {

				$nameID["NameID"] = $node->nodeValue;
				$nameID["NameQualifier"] = $node->getAttribute('NameQualifier');
				$nameID["SPNameQualifier"] = $node->getAttribute('SPNameQualifier');
				$nameID["Format"] = $node->getAttribute('Format');
			}
		}
		//echo '<pre>'; print_r($nameID); echo '</pre>';
		return $nameID;
	}


	/* This function retrieves the ID of the request this response is a
	 * response to. This ID is stored in the InResponseTo attribute of the
	 * top level DOM element.
	 *
	 * Returns:
	 *  The ID of the request this response is a response to, or NULL if
	 *  we don't know.
	 */
	public function getInResponseTo() {
		$dom = $this->getDOM();
		if($dom === NULL) {
			return NULL;
		}

		assert('$dom instanceof DOMDocument');

		$xPath = new DOMXpath($dom);
		$xPath->registerNamespace('samlp', self::SAML2_PROTOCOL_NS);

		$result = $xPath->query('/samlp:Response/@InResponseTo');
		if($result->length === 0) {
			return NULL;
		}

		return $result->item(0)->value;
	}		
			

	// Not updated for response. from request.
	public function generate($idpentityid, $spentityid, $inresponseto, $nameid, $attributes) {
	
		//echo 'idp:' . $idpentityid . ' sp:' . $spentityid .' inresponseto:' .  $inresponseto . ' namid:' . $nameid;
	
		$idpmd 	= $this->metadata->getMetaData($idpentityid, 'saml20-idp-hosted');
		$spmd 	= $this->metadata->getMetaData($spentityid, 'saml20-sp-remote');
		
		$id = self::generateID();
		$issueInstant = self::generateIssueInstant();
		$assertionExpire = self::generateIssueInstant(60 * 5); # 5 minutes
		$notBefore = self::generateIssueInstant(-30);
		
		$assertionid = self::generateID();
		$sessionindex = self::generateID();
		

		$issuer = $idpentityid;

		$assertionConsumerServiceURL = $spmd['AssertionConsumerService'];
		$spNameQualifier = $spmd['spNameQualifier'];
		
		$destination = $spmd['AssertionConsumerService'];
		
		$base64 = isset($idpmd['base64attributes']) ? $idpmd['base64attributes'] : false;
		
		$encodedattributes = '';
		foreach ($attributes AS $name => $values) {
			$encodedattributes .= self::enc_attribute($name, $values, $base64);
		}
		$attributestatement = '<saml:AttributeStatement>' . $encodedattributes . '</saml:AttributeStatement>';
		
		if (!$spmd['simplesaml.attributes']) 
			$attributestatement = '';
		
		$namid = null;
		if ($spmd['NameIDFormat'] == self::EMAIL) {
			$nameid = $this->generateNameID($spmd['NameIDFormat'], $attributes[$spmd['simplesaml.nameidattribute']][0]);
		} else {
			$nameid = $this->generateNameID($spmd['NameIDFormat'], self::generateID(), $issuer, $spNameQualifier);
		}
		
		$authnResponse = '<samlp:Response 
			xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" 
			xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" 
			ID="' . $id . '"
			InResponseTo="' . htmlspecialchars($inresponseto) . '" Version="2.0"
			IssueInstant="' . $issueInstant . '"
			Destination="' . $destination . '">
	<saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">' . htmlspecialchars($issuer) . '</saml:Issuer>
	<samlp:Status xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol">
		<samlp:StatusCode xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
			Value="urn:oasis:names:tc:SAML:2.0:status:Success" />
	</samlp:Status>
	<saml:Assertion Version="2.0"
		ID="' . $assertionid . '" IssueInstant="' . $issueInstant . '">
		<saml:Issuer>' . htmlspecialchars($issuer) . '</saml:Issuer>
		<saml:Subject>
			' . $nameid . ' 
			<saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
				<saml:SubjectConfirmationData NotOnOrAfter="' . $assertionExpire . '"
					InResponseTo="' . htmlspecialchars($inresponseto). '"
					Recipient="' . $destination . '"/>
			</saml:SubjectConfirmation>
		</saml:Subject>
		<saml:Conditions NotBefore="' . $notBefore. '" NotOnOrAfter="' . $assertionExpire. '">
            <saml:AudienceRestriction>
                <saml:Audience>' . htmlspecialchars($spentityid) . '</saml:Audience>
            </saml:AudienceRestriction>
		</saml:Conditions> 
		<saml:AuthnStatement AuthnInstant="' . $issueInstant . '"
			SessionIndex="' . $sessionindex . '">
			<saml:AuthnContext>
				<saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef>
			</saml:AuthnContext>
        </saml:AuthnStatement>
        ' . $attributestatement. '
    </saml:Assertion>
</samlp:Response>
';


//echo $authnResponse;


		//  echo $authnResponse; exit(0);
		return $authnResponse;
	}


	private function generateNameID($type = 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient', 
			$value = 'anonymous', $namequalifier = null, $spnamequalifier = null) {
			
		if ($type == self::EMAIL) {
			return '<saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress">' . htmlspecialchars($value) . '</saml:NameID>';

		} else {
			return '<saml:NameID NameQualifier="' . htmlspecialchars($namequalifier) . '" SPNameQualifier="'. htmlspecialchars($spnamequalifier). '"
                Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient"
                >' . htmlspecialchars($value). '</saml:NameID>';
		}
		
	}

	
	/* This function converts an array of attribute values into an
	 * encoded saml:Attribute element which should go into the
	 * AuthnResponse. The data can optionally be base64 encoded.
	 *
	 * Parameters:
	 *  $name      Name of this attribute.
	 *  $values    Array with the values of this attribute.
	 *  $base64    Enable base64 encoding of attribute values.
	 *
	 * Returns:
	 *  String containing the encoded saml:attribute value for this
	 *  attribute.
	 */
	private static function enc_attribute($name, $values, $base64 = false) {
		assert(is_array($values));

		$ret = '<saml:Attribute Name="' . htmlspecialchars($name) . '">';

		foreach($values as $value) {
			if($base64) {
				$text = base64_encode($value);
			} else {
				$text = htmlspecialchars($value);
			}

			$ret .= '<saml:AttributeValue xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">' .
			        $text . '</saml:AttributeValue>';
		}


		$ret .= '</saml:Attribute>';

		return $ret;
	}
	
	
}

?>