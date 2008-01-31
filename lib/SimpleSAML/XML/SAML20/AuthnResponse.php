<?php

require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XML/AuthnResponse.php');
require_once('SimpleSAML/XML/Validator.php');

require_once('xmlseclibs.php');
 
/**
 * An SAML 2.0 Authentication Response
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_XML_SAML20_AuthnResponse extends SimpleSAML_XML_AuthnResponse {

	
	const PROTOCOL = 'urn:oasis:names:tc:SAML:2.0';
	
	const TRANSIENT 	= 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient';
	const EMAIL 		= 'urn:oasis:names:tc:SAML:2.0:nameid-format:email';

	/* Namespaces used in the XML representation of this object.
	 * TODO: Move these constants into a generic SAML2-class?
	 */
	const SAML2_ASSERT_NS = 'urn:oasis:names:tc:SAML:2.0:assertion';
	const SAML2_PROTOCOL_NS = 'urn:oasis:names:tc:SAML:2.0:protocol';


	/**
	 * This variable contains an XML validator for this message.
	 */
	private $validator = null;
	

	function __construct(SimpleSAML_Configuration $configuration, SimpleSAML_Metadata_MetaDataStorageHandler $metadatastore) {
		$this->configuration = $configuration;
		$this->metadata = $metadatastore;
	}

	
	public function validate() {
	
		$dom = $this->getDOM();

		/* Validate the signature. */
		$this->validator = new SimpleSAML_XML_Validator($dom, 'ID');

		// Get the issuer of the response.
		$issuer = $this->getIssuer();

		/* Get the metadata of the issuer. */
		$md = $this->metadata->getMetaData($issuer, 'saml20-idp-remote');

		/* Get fingerprint for the certificate of the issuer. */
		$issuerFingerprint = $md['certFingerprint'];

		/* Validate the fingerprint. */
		$this->validator->validateFingerprint($issuerFingerprint);

		return true;
	}


	/**
	 * This function runs an xPath query on this authentication response.
	 *
	 * @param $query  The query which should be run.
	 * @param $node   The node which this query is relative to. If this node is NULL (the default)
	 *                then the query will be relative to the root of the response.
	 * @return Whatever DOMXPath::query returns.
	 */
	private function doXPathQuery($query, $node = NULL) {
		assert('is_string($query)');

		$dom = $this->getDOM();
		assert('$dom instanceof DOMDocument');

		if($node === NULL) {
			$node = $dom->documentElement;
		}

		assert('$node instanceof DOMNode');

		$xPath = new DOMXpath($dom);
		$xPath->registerNamespace("saml", self::SAML2_ASSERT_NS);
		$xPath->registerNamespace("samlp", self::SAML2_PROTOCOL_NS);

		return $xPath->query($query, $node);
	}


	public function createSession() {
	
		SimpleSAML_Session::init(true, 'saml2');
		$session = SimpleSAML_Session::getInstance();
		$session->setAttributes($this->getAttributes());
			
		$session->setNameID($this->getNameID());
		$session->setSessionIndex($this->getSessionIndex());
		$session->setIdP($this->getIssuer());
		
		return $session;
	}
	
	
	// TODO: Not tested, but neigther is it used.
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

		$attributes = array();
		$token = $this->getDOM();

		if($this->validator === NULL) {
			throw new Exception('Called getAttributes on a SAML2 AuthnResponse which hasn\'t been validated.');
		}


		if ( !($token instanceof DOMDocument)) {
			throw new Exception('Called getAttributes on a SAML2 AuthnResponse which doesn\'t contain a message.');
		}

		$assertions = $this->doXPathQuery('/samlp:Response/saml:Assertion');
		foreach($assertions as $assertion) {

			if(!$this->validator->isNodeValidated($assertion)) {
				throw new Exception('A SAML2 AuthnResponse contained an Assertion which isn\'t verified by the signature.');
			}

			foreach($this->doXPathQuery('saml:Conditions', $assertion) as $condition) {

				$start = $condition->getAttribute("NotBefore");
				$end = $condition->getAttribute("NotOnOrAfter");

				if (! SimpleSAML_Utilities::checkDateConditions($start, $end)) {
					throw new Exception("Date check failed (between $start and $end). Check if the clocks on the SP and IdP are synchronized. Alternatively you can get this message, when you move back in history or refresh an old page.");
				}
			}

			foreach($this->doXPathQuery('saml:AttributeStatement/saml:Attribute/saml:AttributeValue', $assertion) as $attribute) {

				$name = $attribute->parentNode->getAttribute('Name');
				$value = $attribute->textContent;

				if(!array_key_exists($name, $attributes)) {
					$attributes[$name] = array();
				}

				if ($base64) {
					foreach(explode('_', $value) AS $v) {
						$attributes[$name][] = base64_decode($v);
					}
				} else {
					$attributes[$name][] = $value;
				}
			}
		}

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

				$nameID["value"] = $node->nodeValue;
				//$nameID["NameQualifier"] = $node->getAttribute('NameQualifier');
				//$nameID["SPNameQualifier"] = $node->getAttribute('SPNameQualifier');
				$nameID["Format"] = $node->getAttribute('Format');
			}
		}
		return $nameID;
	}


	/**
	 * This function retrieves the ID of the request this response is a
	 * response to. This ID is stored in the InResponseTo attribute of the
	 * top level DOM element.
	 *
	 * @return The ID of the request this response is a response to, or NULL if
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

		$query = 'string(/samlp:Response/@InResponseTo)';
		$result = $xPath->evaluate($query);
		if($result === '') {
			return NULL;
		}

		return $result;
	}		
			

	/**
	 * This function generates an AuthenticationResponse
	 *
	 *  @param $idpentityid   entityid of IdP
	 *  @param $spentityid    entityid of SP
	 *  @param $inresponseto  the ID of the request, that these message is an response to.
	 *  @param $nameid        the NameID of the user (an array)
	 *  @param $attributes    A two level array of multivalued attributes, where the first level
	 *   index is the attribute name.
	 *
	 *  @return AuthenticationResponse as string
	 */
	public function generate($idpentityid, $spentityid, $inresponseto, $nameid, $attributes) {
		
		/**
		 * Retrieving metadata for the two specific entity IDs.
		 */
		$idpmd 	= $this->metadata->getMetaData($idpentityid, 'saml20-idp-hosted');
		$spmd 	= $this->metadata->getMetaData($spentityid, 'saml20-sp-remote');
		
		$issuer = $idpentityid;
		$destination = $spmd['AssertionConsumerService'];
		
		/**
		 * Generating IDs and timestamps.
		 */
		$id = self::generateID();
		$issueInstant = self::generateIssueInstant();
		$assertionExpire = self::generateIssueInstant(60 * 5); # 5 minutes
		$notBefore = self::generateIssueInstant(-30);
		
		$assertionid = self::generateID();
		$sessionindex = self::generateID();

		
		/**
		 * Handling attributes.
		 */
		$base64 = isset($spmd['base64attributes']) ? $spmd['base64attributes'] : false;
		$encodedattributes = '';
		foreach ($attributes AS $name => $values) {
			$encodedattributes .= self::enc_attribute($name, $values, $base64);
		}
		$attributestatement = '<saml:AttributeStatement>' . $encodedattributes . '</saml:AttributeStatement>';
		if (!$spmd['simplesaml.attributes']) 
			$attributestatement = '';
		
		
		/**
		 * Handling NameID
		 */
		$nameid = null;
		if ($spmd['NameIDFormat'] == self::EMAIL) {
			$nameid = $this->generateNameID($spmd['NameIDFormat'], $attributes[$spmd['simplesaml.nameidattribute']][0]);
		} else {
			$nameid = $this->generateNameID($spmd['NameIDFormat'], self::generateID());
		}
		
		/**
		 * Generating the response.
		 */
		$authnResponse = '<samlp:Response 
			xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" 
			xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" 
			ID="' . $id . '"
			InResponseTo="' . htmlspecialchars($inresponseto) . '" Version="2.0"
			IssueInstant="' . $issueInstant . '"
			Destination="' . htmlspecialchars($destination) . '">
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
					Recipient="' . htmlspecialchars($destination) . '"/>
			</saml:SubjectConfirmation>
		</saml:Subject>
		<saml:Conditions NotBefore="' . $notBefore. '" NotOnOrAfter="' . $assertionExpire. '">
            <saml:AudienceRestriction>
                <saml:Audience>' . htmlspecialchars($spentityid) . '</saml:Audience>
            </saml:AudienceRestriction>
		</saml:Conditions> 
		<saml:AuthnStatement AuthnInstant="' . $issueInstant . '"
			SessionIndex="' . htmlspecialchars($sessionindex) . '">
			<saml:AuthnContext>
				<saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef>
			</saml:AuthnContext>
        </saml:AuthnStatement>
        ' . $attributestatement. '
    </saml:Assertion>
</samlp:Response>
';

		return $authnResponse;
	}


	private function generateNameID($type = 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient', 
			$value = 'anonymous') {
			
		if ($type == self::EMAIL) {
			return '<saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress">' . htmlspecialchars($value) . '</saml:NameID>';

		} else {
			return '<saml:NameID Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient">' . htmlspecialchars($value). '</saml:NameID>';
		}
		
	}

	
	/**
	 * This function converts an array of attribute values into an
	 * encoded saml:Attribute element which should go into the
	 * AuthnResponse. The data can optionally be base64 encoded.
	 *
	 *  @param $name      Name of this attribute.
	 *  @param $values    Array with the values of this attribute.
	 *  @param $base64    Enable base64 encoding of attribute values.
	 *
	 *  @return String containing the encoded saml:attribute value for this
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