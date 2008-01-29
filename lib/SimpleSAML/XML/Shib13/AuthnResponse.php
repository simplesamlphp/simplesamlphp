<?php
 
require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XML/AuthnResponse.php');
require_once('SimpleSAML/XML/Validator.php');

require_once('xmlseclibs.php');
 
/**
 * A Shibboleth 1.3 authentication response.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 * @abstract
 */
class SimpleSAML_XML_Shib13_AuthnResponse extends SimpleSAML_XML_AuthnResponse {

	private $configuration = null;
	private $metadata = 'default.php';
	
	private $message = null;
	private $dom;
	private $relayState = null;
	
	/**
	 * This variable contains an XML validator for this message.
	 */
	private $validator = null;


	const PROTOCOL = 'urn:oasis:names:tc:SAML:2.0';
	const SHIB_PROTOCOL_NS = 'urn:oasis:names:tc:SAML:1.0:protocol';
	const SHIB_ASSERT_NS = 'urn:oasis:names:tc:SAML:1.0:assertion';

	function __construct(SimpleSAML_Configuration $configuration, SimpleSAML_Metadata_MetaDataStorageHandler $metadatastore) {
		$this->configuration = $configuration;
		$this->metadata = $metadatastore;
	}
	
	// Inhereted public function setXML($xml) {
	// Inhereted public function getXML() {
	// Inhereted public function setRelayState($relayState) {
	// Inhereted public function getRelayState() {

	
	public function validate() {
	
		$dom = $this->getDOM();

		/* Validate the signature. */
		$this->validator = new SimpleSAML_XML_Validator($dom, 'ResponseID');

		// Get the issuer of the response.
		$issuer = $this->getIssuer();

		/* Get the metadata of the issuer. */
		$md = $this->metadata->getMetaData($issuer, 'shib13-idp-remote');

		/* Get fingerprint for the certificate of the issuer. */
		$issuerFingerprint = $md['certFingerprint'];

		/* Validate the fingerprint. */
		$this->validator->validateFingerprint($issuerFingerprint);

		return true;
	}


	/* Checks if the given node is validated by the signatore on this response.
	 *
	 * Returns:
	 *  TRUE if the node is validated or FALSE if not.
	 */
	private function isNodeValidated($node) {

		if($this->validator === NULL) {
			return FALSE;
		}

		/* Convert the node to a DOM node if it is an element from SimpleXML. */
		if($node instanceof SimpleXMLElement) {
			$node = dom_import_simplexml($node);
		}

		assert('$node instanceof DOMNode');

		return $this->validator->isNodeValidated($node);
	}
	
	
	public function createSession() {
	
		SimpleSAML_Session::init(true, 'shib13');
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
		$base64 = isset($md['base64attributes']) ? $md['base64attributes'] : false;
		
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
			

			
			$assertions = $sxml->xpath('/samlp:Response/saml:Assertion');

			foreach ($assertions AS $assertion) {				
				if(!$this->isNodeValidated($assertion)) {
					throw new Exception('Shib13 AuthResponse contained an unsigned assertion.');
				}

				if ($assertion->Conditions) {

					if (($start = (string)$assertion->Conditions['NotBefore']) &&
						($end = (string)$assertion->Conditions['NotOnOrAfter'])) {
	
						if (! SimpleSAML_Utilities::checkDateConditions($start, $end)) {
							error_log( " Date check failed ... (from $start to $end)");
							continue;
						} 

					}

				}
				
				
				// Traverse AttributeStatements
				foreach ($assertion->AttributeStatement AS $attributestatement) {
				
					// Traverse Attributes
					foreach ($attributestatement->Attribute AS $attribute) {
						$values = array();
						
						// Traverse Values
						foreach ($attribute->AttributeValue AS $newvalue) {
						
							$newvalue = (string)$newvalue;

							if ($base64) {
								$encodedvalues = explode('_', $newvalue);
								foreach($encodedvalues AS $v) {
									$values[] = base64_decode($v);
								}
							} else {

								$values[] = $newvalue;
							}
						}
						
						$attributes[(string)$attribute['AttributeName']] = $values;
					}

				}
				
			}
				
			/*
			echo "<PRE>token:";
			echo htmlentities($token->saveXML());
			echo ":</PRE>";

		
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
	
		$idpmd 	= $this->metadata->getMetaData($idpentityid, 'shib13-idp-hosted');
		$spmd 	= $this->metadata->getMetaData($spentityid, 'shib13-sp-remote');
		
		$id = self::generateID();
		$issueInstant = self::generateIssueInstant();
		$assertionExpire = self::generateIssueInstant(60 * 5); # 5 minutes
		
		$assertionid = self::generateID();
		
		
		if (is_null($nameid)) {
			$nameid = self::generateID();
		}

		$issuer = $idpentityid;

		$shire = $spmd['shire'];
		$audience = $spmd['audience'];
		$base64 = $idpmd['base64'];
		
		$encodedattributes = '';
		
		if (is_array($attributes)) {

			$encodedattributes .= '<AttributeStatement>
				<Subject>
					<NameIdentifier Format="urn:mace:shibboleth:1.0:nameIdentifier">' . htmlspecialchars($nameid) . '</NameIdentifier>
				</Subject>';
				
			foreach ($attributes AS $name => $value) {
				$encodedattributes .= $this->enc_attribute($name, $value[0], $base64);
			}
			
			$encodedattributes .= '</AttributeStatement>';
		}
		
		
		
		/*
		 * The SAML 1.1 response message
		 */
		$response = '<Response xmlns="urn:oasis:names:tc:SAML:1.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:1.0:assertion"
    xmlns:samlp="urn:oasis:names:tc:SAML:1.0:protocol" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" IssueInstant="' . $issueInstant. '"
    MajorVersion="1" MinorVersion="1"
    Recipient="' . htmlspecialchars($shire) . '"
    ResponseID="' . $id . '">

<Status>
        <StatusCode Value="samlp:Success">
            <StatusCode xmlns:code="urn:geant2:edugain:protocol" Value="code:Accepted"/>
        </StatusCode>
    </Status>    
    <Assertion xmlns="urn:oasis:names:tc:SAML:1.0:assertion"
        AssertionID="' . $assertionid . '" IssueInstant="' . $issueInstant. '"
        Issuer="' . htmlspecialchars($issuer) . '" MajorVersion="1" MinorVersion="1">
        <Conditions NotBefore="' . $issueInstant. '" NotOnOrAfter="'. $assertionExpire . '">
            <AudienceRestrictionCondition>
                <Audience>' . htmlspecialchars($audience) . '</Audience>
            </AudienceRestrictionCondition>
        </Conditions>
        <AuthenticationStatement AuthenticationInstant="' . $issueInstant. '"
            AuthenticationMethod="urn:oasis:names:tc:SAML:1.0:am:unspecified">
            <Subject>
                <NameIdentifier Format="urn:mace:shibboleth:1.0:nameIdentifier">' . htmlspecialchars($nameid) . '</NameIdentifier>
                <SubjectConfirmation>
                    <ConfirmationMethod>urn:oasis:names:tc:SAML:1.0:cm:bearer</ConfirmationMethod>
                </SubjectConfirmation>
            </Subject>
        </AuthenticationStatement>
        
                ' . $encodedattributes . '
    </Assertion>
</Response>';
		  
		return $response;
	}


	


	private function enc_attribute($name, $value, $base64 = false) {
		return '<Attribute AttributeName="' . htmlspecialchars($name) . '"
			AttributeNamespace="urn:mace:shibboleth:1.0:attributeNamespace:uri">
		<AttributeValue>' . ($base64 ? base64_encode($value) : htmlspecialchars($value) ) . '</AttributeValue>
	</Attribute>';
	}	
	
}

?>