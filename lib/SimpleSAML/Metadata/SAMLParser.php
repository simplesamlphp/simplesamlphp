<?php

require_once('SimpleSAML/Utilities.php');

/**
 * This is class for parsing of SAML 1.x and SAML 2.0 metadata.
 *
 * Metadata is loaded by calling the static methods parseFile, parseString or parseElement.
 * These functions returns an instance of SimpleSAML_Metadata_SAMLParser. To get metadata
 * from this object, use the methods getMetadata1xSP or getMetadata20SP.
 *
 * To parse a file which can contain a collection of EntityDescriptor or EntitiesDescriptor elements, use the
 * parseDescriptorsFile, parseDescriptorsString or parseDescriptorsElement methods. These functions will return
 * an array of SAMLParser elements where each element represents an EntityDescriptor-element.
 */
class SimpleSAML_Metadata_SAMLParser {

	/**
	 * This is the list of SAML 1.x protocols.
	 */
	private static $SAML1xProtocols = array(
		'urn:oasis:names:tc:SAML:1.0:protocol',
		'urn:oasis:names:tc:SAML:1.1:protocol',
		);


	/**
	 * This is the list with the SAML 2.0 protocol.
	 */
	private static $SAML20Protocols = array(
		'urn:oasis:names:tc:SAML:2.0:protocol',
		);


	/**
	 * This is the binding used to send authentication requests in SAML 1.x.
	 */
	const SAML_1x_AUTHN_REQUEST = 'urn:mace:shibboleth:1.0:profiles:AuthnRequest';

	/**
	 * This is the binding used for browser post in SAML 1.x.
	 */
	const SAML_1X_POST_BINDING = 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post';


	/**
	 * This is the binding used for HTTP-POST in SAML 2.0.
	 */
	const SAML_20_POST_BINDING = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST';


	/**
	 * This is the binding used for HTTP-REDIRECT in SAML 2.0.
	 */
	const SAML_20_REDIRECT_BINDING = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect';


	/**
	 * This is the entity id we find in the metadata.
	 */
	private $entityId;


	/**
	 * This is an array with the processed SPSSODescriptor elements we have found in this
	 * metadata file.
	 * Each element in the array is an associative array with the elements from parseSSODescriptor and:
	 * - 'assertionConsumerServices': Array with the SP's assertion consumer services.
	 *   Each assertion consumer service is stored as an associative array with the
	 *   elements that parseGenericEndpoint returns.
	 */
	private $spDescriptors;


	/**
	 * This is an array with the processed IDPSSODescriptor elements we have found.
	 * Each element in the array is an associative array with the elements from parseSSODescriptor and:
	 * - 'singleSignOnServices': Array with the IdP's single signon service endpoints. Each endpoint is stored
	 *   as an associative array with the elements that parseGenericEndpoint returns.
	 */
	private $idpDescriptors;


	/**
	 * This is the constructor for the SAMLParser class.
	 *
	 * @param $entityElement The DOMElement which represents the EntityDescriptor-element.
	 */
	private function __construct($entityElement) {
		$this->spDescriptors = array();
		$this->idpDescriptors = array();


		assert('$entityElement instanceof DOMElement');

		/* Extract the entityID from the EntityDescriptor element. This is a required
		 * attribute, so we throw an exception if it isn't found.
		 */
		if(!$entityElement->hasAttribute('entityID')) {
			throw new Exception('EntityDescriptor missing required entityID attribute.');
		}
		$this->entityID = $entityElement->getAttribute('entityID');


		/* Look over the child nodes for any known element types. */
		for($i = 0; $i < $entityElement->childNodes->length; $i++) {
			$child = $entityElement->childNodes->item($i);

			/* Skip text nodes. */
			if($child instanceof DOMText) {
				continue;
			}

			if(SimpleSAML_Utilities::isDOMElementOfType($child, 'SPSSODescriptor', '@md') === TRUE) {
				$this->processSPSSODescriptor($child);
			}

			if(SimpleSAML_Utilities::isDOMElementOfType($child, 'IDPSSODescriptor', '@md') === TRUE) {
				$this->processIDPSSODescriptor($child);
			}
		}
	}


	/**
	 * This function parses a file which contains XML encoded metadata.
	 *
	 * @param $file  The path to the file which contains the metadata.
	 * @return An instance of this class with the metadata loaded.
	 */
	public static function parseFile($file) {
		$doc = new DOMDocument();

		$res = $doc->load($file);
		if($res !== TRUE) {
			throw new Exception('Failed to read XML from file: ' . $file);
		}

		return self::parseDocument($doc);
	}


	/**
	 * This function parses a string which contains XML encoded metadata.
	 *
	 * @param $metadata  A string which contains XML encoded metadata.
	 * @return An instance of this class with the metadata loaded.
	 */
	public static function parseString($metadata) {
		$doc = new DOMDocument();

		$res = $doc->loadXML($metadata);
		if($res !== TRUE) {
			throw new Exception('Failed to parse XML string.');
		}

		return self::parseDocument($doc);
	}


	/**
	 * This function parses a DOMDocument which is assumed to contain a single EntityDescriptor element.
	 *
	 * @param $document  The DOMDocument which contains the EntityDescriptor element.
	 * @return An instance of this class with the metadata loaded.
	 */
	public static function parseDocument($document) {
		assert('$document instanceof DOMDocument');

		$entityElement = self::findEntityDescriptor($document);

		return self::parseElement($entityElement);
	}


	/**
	 * This function parses a DOMElement which represents a EntityDescriptor element.
	 *
	 * @param $entityElement  A DOMElement which represents a EntityDescriptor element.
	 * @return An instance of this class with the metadata loaded.
	 */
	public static function parseElement($entityElement) {
		assert('$entityElement instanceof DOMElement');

		return new SimpleSAML_Metadata_SAMLParser($entityElement);
	}


	/**
	 * This function parses a file where the root node is either an EntityDescriptor element or an
	 * EntitiesDescriptor element. In both cases it will return an array of SAMLParser instances. If
	 * the file contains a single EntityDescriptorElement, then the array will contain a single SAMLParser
	 * instance.
	 *
	 * @param $file  The path to the file which contains the EntityDescriptor or EntitiesDescriptor element.
	 * @return An array of SAMLParser instances.
	 */
	public static function parseDescriptorsFile($file) {

		$doc = new DOMDocument();

		$res = $doc->load($file);
		if($res !== TRUE) {
			throw new Exception('Failed to read XML from file: ' . $file);
		}

		return self::parseDescriptorsElement($doc->documentElement);
	}


	/**
	 * This function parses a string with XML data. The root node of the XML data is expected to be either an
	 * EntityDescriptor element or an EntitiesDescriptor element. It will return an array of SAMLParser instances.
	 *
	 * @param $string  The string with XML data.
	 * @return An array of SAMLParser instances.
	 */
	public static function parseDescriptorsString($string) {

		$doc = new DOMDocument();

		$res = $doc->loadXML($string);
		if($res !== TRUE) {
			throw new Exception('Failed to parse XML string.');
		}

		return self::parseDescriptorsElement($doc->documentElement);
	}


	/**
	 * This function parses a DOMElement which represents either an EntityDescriptor element or an
	 * EntitiesDescriptor element. It will return an array of SAMLParser instances in both cases.
	 *
	 * @param $element  The DOMElement which contains the EntityDescriptor element or the EntitiesDescriptor
	 *                  element.
	 * @return An array of SAMLParser instances.
	 */
	public static function parseDescriptorsElement($element) {

		if($element === NULL) {
			throw new Exception('Document was empty.');
		}

		assert('$element instanceof DOMElement');


		if(SimpleSAML_Utilities::isDOMElementOfType($element, 'EntityDescriptor', '@md') === TRUE) {
			$elements = array($element);
		} elseif(SimpleSAML_Utilities::isDOMElementOfType($element, 'EntitiesDescriptor', '@md') === TRUE) {
			$elements = SimpleSAML_Utilities::getDOMChildren($element, 'EntityDescriptor', '@md');
		} else {
			throw new Exception('Unexpected root node: [' . $element->namespaceURI . ']:' .
				$element->localName);
		}

		$ret = array();
		foreach($elements as $e) {
			$ret[] = self::parseElement($e);
		}

		return $ret;
	}


	/**
	 * This function returns the metadata for SAML 1.x SPs in the format simpleSAMLphp expects.
	 * This is an associative array with the following fields:
	 * - 'entityID': The entity id of the entity described in the metadata.
	 * - 'AssertionConsumerService': String with the url of the assertion consumer service which supports
	 *   the browser-post binding.
	 *
	 * Metadata must be loaded with one of the parse functions before this function can be called.
	 *
	 * @return Associative array with metadata or NULL if we are unable to generate metadata for a SAML 1.x SP.
	 */
	public function getMetadata1xSP() {

		$ret = array();

		$ret['entityID'] = $this->entityID;


		/* Find SP information which supports one of the SAML 1.x protocols. */
		$spd = $this->getSPDescriptors(self::$SAML1xProtocols);
		if(count($spd) === 0) {
			return NULL;
		}

		/* We currently only look at the first SPDescriptor which supports SAML 1.x. */
		$spd = $spd[0];

		/* Find the assertion consumer service endpoint. */
		$acs = $this->getDefaultEndpoint($spd['assertionConsumerServices'], array(self::SAML_1X_POST_BINDING));
		if($acs === NULL) {
			throw new Exception('Could not find any valid AssertionConsumerService.' .
				' simpleSAMLphp currently supports only the browser-post binding for SAML 1.x.');
		}

		$ret['AssertionConsumerService'] = $acs['location'];


		return $ret;
	}


	/**
	 * This function returns the metadata for SAML 2.0 IdPs in the format simpleSAMLphp expects.
	 * This is an associative array with the following fields:
	 * - 'entityID': The entity id of the entity described in the metadata.
	 * - 'name': Autogenerated name for this entity. Currently set to the entityID.
	 * - 'SingleSignOnService': String with the url of the SSO service which supports the redirect binding.
	 * - 'SingleLogoutService': String with the url where we should send logout requests/responses.
	 * - 'certFingerprint': Fingerprint of the X509Certificate from the metadata.
	 *
	 * Metadata must be loaded with one of the parse functions before this function can be called.
	 *
	 * @return Associative array with metadata or NULL if we are unable to generate metadata for a SAML 1.x IdP.
	 */
	public function getMetadata1xIdP() {

		$ret = array();

		$ret['entityID'] = $this->entityID;

		$ret['name'] = $this->entityID;

		/* Find IdP information which supports the SAML 1.x protocol. */
		$idp = $this->getIdPDescriptors(self::$SAML1xProtocols);
		if(count($idp) === 0) {
			return NULL;
		}

		/* We currently only look at the first IDP descriptor which supports SAML 1.x. */
		$idp = $idp[0];

		/* Find the SSO service endpoint. */
		$sso = $this->getDefaultEndpoint($idp['singleSignOnServices'], array(self::SAML_1x_AUTHN_REQUEST));
		if($sso === NULL) {
			throw new Exception('Could not find any valid SingleSignOnService endpoint.');
		}
		$ret['SingleSignOnService'] = $sso['location'];

		/* Find the certificate fingerprint. */
		foreach($idp['keys'] as $key) {
			if($key['type'] !== 'X509Certificate') {
				continue;
			}

			$certData = base64_decode($key['X509Certificate']);
			if($certData === FALSE) {
				throw new Exception('Unable to parse base64 encoded certificate data.');
			}

			$ret['certFingerprint'] = sha1($certData);
			break;
		}

		return $ret;
	}


	/**
	 * This function returns the metadata for SAML 2.0 SPs in the format simpleSAMLphp expects.
	 * This is an associative array with the following fields:
	 * - 'entityID': The entity id of the entity described in the metadata.
	 * - 'AssertionConsumerService': String with the url of the assertion consumer service which supports
	 *   the browser-post binding.
	 * - 'SingleLogoutService': String with the url where we should send logout requests/responses.
	 * - 'NameIDFormat': The name ID format this SP expects. This may be unset.
	 *
	 * Metadata must be loaded with one of the parse functions before this function can be called.
	 *
	 * @return Associative array with metadata or NULL if we are unable to generate metadata for a SAML 2.x SP.
	 */
	public function getMetadata20SP() {

		$ret = array();

		$ret['entityID'] = $this->entityID;


		/* Find SP information which supports the SAML 2.0 protocol. */
		$spd = $this->getSPDescriptors(self::$SAML20Protocols);
		if(count($spd) === 0) {
			return NULL;
		}

		/* We currently only look at the first SPDescriptor which supports SAML 2.0. */
		$spd = $spd[0];

		/* Find the assertion consumer service endpoint. */
		$acs = $this->getDefaultEndpoint($spd['assertionConsumerServices'], array(self::SAML_20_POST_BINDING));
		if($acs === NULL) {
			throw new Exception('Could not find any valid AssertionConsumerService.' .
				' simpleSAMLphp currently supports only the http-post binding for SAML 2.0 assertions.');
		}

		$ret['AssertionConsumerService'] = $acs['location'];


		/* Find the single logout service endpoint. */
		$slo = $this->getDefaultEndpoint($spd['singleLogoutServices'], array(self::SAML_20_REDIRECT_BINDING));
		if($slo === NULL) {
			throw new Exception('Could not find any valid SingleLogoutService.' .
				' simpleSAMLphp currently supports only the http-redirect binding for SAML 2.0 logout.');
		}

		$ret['SingleLogoutService'] = $slo['location'];


		/* Find the NameIDFormat. This may not exists. */
		if(count($spd['nameIDFormats']) > 0) {
			/* simpleSAMLphp currently only supports a single NameIDFormat pr. SP. We use the first one. */
			$ret['NameIDFormat'] = $spd['nameIDFormats'][0];
		}

		return $ret;
	}


	/**
	 * This function returns the metadata for SAML 2.0 IdPs in the format simpleSAMLphp expects.
	 * This is an associative array with the following fields:
	 * - 'entityID': The entity id of the entity described in the metadata.
	 * - 'name': Autogenerated name for this entity. Currently set to the entityID.
	 * - 'SingleSignOnService': String with the url of the SSO service which supports the redirect binding.
	 * - 'SingleLogoutService': String with the url where we should send logout requests/responses.
	 * - 'certFingerprint': Fingerprint of the X509Certificate from the metadata.
	 *
	 * Metadata must be loaded with one of the parse functions before this function can be called.
	 *
	 * @return Associative array with metadata or NULL if we are unable to generate metadata for a SAML 2.0 IdP.
	 */
	public function getMetadata20IdP() {

		$ret = array();

		$ret['entityID'] = $this->entityID;

		$ret['name'] = $this->entityID;

		/* Find IdP information which supports the SAML 2.0 protocol. */
		$idp = $this->getIdPDescriptors(self::$SAML20Protocols);
		if(count($idp) === 0) {
			return NULL;
		}

		/* We currently only look at the first IDP descriptor which supports SAML 2.0. */
		$idp = $idp[0];

		/* Find the SSO service endpoint. */
		$sso = $this->getDefaultEndpoint($idp['singleSignOnServices'], array(self::SAML_20_REDIRECT_BINDING));
		if($sso === NULL) {
			throw new Exception('Could not find any valid SingleSignOnService endpoint.');
		}
		$ret['SingleSignOnService'] = $sso['location'];


		/* Find the single logout service endpoint. */
		$slo = $this->getDefaultEndpoint($idp['singleLogoutServices'], array(self::SAML_20_REDIRECT_BINDING));
		if($slo === NULL) {
			throw new Exception('Could not find any valid SingleLogoutService.' .
				' simpleSAMLphp currently supports only the http-redirect binding for SAML 2.0 logout.');
		}
		$ret['SingleLogoutService'] = $slo['location'];


		/* Find the certificate fingerprint. */
		foreach($idp['keys'] as $key) {
			if($key['type'] !== 'X509Certificate') {
				continue;
			}

			$certData = base64_decode($key['X509Certificate']);
			if($certData === FALSE) {
				throw new Exception('Unable to parse base64 encoded certificate data.');
			}

			$ret['certFingerprint'] = sha1($certData);
			break;
		}

		return $ret;
	}


	/**
	 * This function extracts metadata from a SSODescriptor element.
	 *
	 * The returned associative array has the following elements:
	 * - 'protocols': Array with the protocols this SSODescriptor supports.
	 * - 'singleLogoutServices': Array with the single logout service endpoints. Each endpoint is stored
	 *   as an associative array with the elements that parseGenericEndpoint returns.
	 * - 'nameIDFormats': The NameIDFormats supported by this SSODescriptor. This may be an empty array.
	 * - 'keys': Array of associative arrays with the elements from parseKeyDescriptor:
	 *
	 * @param $element The element we should extract metadata from.
	 * @return Associative array with metadata we have extracted from this element.
	 */
	private static function parseSSODescriptor($element) {

		assert('$element instanceof DOMElement');

		$sd = array();

		$sd['protocols'] = self::getSupportedProtocols($element);

		/* Find all SingleLogoutService elements. */
		$sd['singleLogoutServices'] = array();
		$sls = SimpleSAML_Utilities::getDOMChildren($element, 'SingleLogoutService', '@md');
		foreach($sls as $child) {
			$sd['singleLogoutServices'][] = self::parseSingleLogoutService($child);
		}

		/* Process NameIDFormat elements. */
		$sd['nameIDFormats'] = array();
		$nif = SimpleSAML_Utilities::getDOMChildren($element, 'NameIDFormat', '@md');
		if(count($nif) > 0) {
			$sd['nameIDFormats'][] = self::parseNameIDFormat($nif[0]);
		}

		/* Process KeyDescriptor elements. */
		$sd['keys'] = array();
		$keys = SimpleSAML_Utilities::getDOMChildren($element, 'KeyDescriptor', '@md');
		foreach($keys as $kd) {
			$key = self::parseKeyDescriptor($kd);
			if($key !== NULL) {
				$sd['keys'][] = $key;
			}
		}


		return $sd;
	}


	/**
	 * This function extracts metadata from a SPSSODescriptor element.
	 *
	 * @param $element The element which should be parsed.
	 */
	private function processSPSSODescriptor($element) {
		assert('$element instanceof DOMElement');

		$sp = self::parseSSODescriptor($element);

		/* Find all AssertionConsumerService elements. */
		$sp['assertionConsumerServices'] = array();
		$acs = SimpleSAML_Utilities::getDOMChildren($element, 'AssertionConsumerService', '@md');
		foreach($acs as $child) {
			$sp['assertionConsumerServices'][] = self::parseAssertionConsumerService($child);
		}


		$this->spDescriptors[] = $sp;
	}


	/**
	 * This function extracts metadata from a IDPSSODescriptor element.
	 *
	 * @param $element The element which should be parsed.
	 */
	private function processIDPSSODescriptor($element) {
		assert('$element instanceof DOMElement');

		$idp = self::parseSSODescriptor($element);

		/* Find all SingleSignOnService elements. */
		$idp['singleSignOnServices'] = array();
		$acs = SimpleSAML_Utilities::getDOMChildren($element, 'SingleSignOnService', '@md');
		foreach($acs as $child) {
			$idp['singleSignOnServices'][] = self::parseSingleSignOnService($child);
		}


		$this->idpDescriptors[] = $idp;
	}


	/**
	 * This function parses AssertionConsumerService elements.
	 *
	 * @param $element The element which should be parsed.
	 * @return Associative array with the data we have extracted from the AssertionConsumerService element.
	 */
	private static function parseAssertionConsumerService($element) {
		assert('$element instanceof DOMElement');

		return self::parseGenericEndpoint($element, TRUE);
	}


	/**
	 * This function parses SingleLogoutService elements.
	 *
	 * @param $element The element which should be parsed.
	 * @return Associative array with the data we have extracted from the SingleLogoutService element.
	 */
	private static function parseSingleLogoutService($element) {
		assert('$element instanceof DOMElement');

		return self::parseGenericEndpoint($element, FALSE);
	}


	/**
	 * This function parses SingleSignOnService elements.
	 *
	 * @param $element The element which should be parsed.
	 * @return Associative array with the data we have extracted from the SingleLogoutService element.
	 */
	private static function parseSingleSignOnService($element) {
		assert('$element instanceof DOMElement');

		return self::parseGenericEndpoint($element, FALSE);
	}


	/**
	 * This function parses NameIDFormat elements.
	 *
	 * @param $element The element which should be parsed.
	 * @return URN with the supported NameIDFormat.
	 */
	private static function parseNameIDFormat($element) {
		assert('$element instanceof DOMElement');

		return SimpleSAML_Utilities::getDOMText($element);
	}


	/**
	 * This function is a generic endpoint element parser.
	 *
	 * The returned associative array has the following elements:
	 * - 'binding': The binding this endpoint uses.
	 * - 'location': The URL to this endpoint.
	 * - 'responseLocation': The URL where responses should be sent. This may not exist.
	 * - 'index': The index of this endpoint. This attribute is only for indexed endpoints.
	 * - 'isDefault': Whether this endpoint is the default endpoint for this type. This attribute may not exist.
	 *
	 * @param $element The element which should be parsed.
	 * @param $isIndexed Wheter the endpoint is an indexed endpoint (and may have the index and isDefault attributes.).
	 * @return Associative array with the data we have extracted from the element.
	 */
	private static function parseGenericEndpoint($element, $isIndexed) {
		assert('$element instanceof DOMElement');
		assert('is_bool($isIndexed)');

		$name = $element->localName;

		$ep = array();

		if(!$element->hasAttribute('Binding')) {
			throw new Exception($name . ' missing required Binding attribute.');
		}
		$ep['binding'] = $element->getAttribute('Binding');

		if(!$element->hasAttribute('Location')) {
			throw new Exception($name . ' missing required Location attribute.');
		}
		$ep['location'] = $element->getAttribute('Location');

		if($element->hasAttribute('ResponseLocation')) {
			$ep['responseLocation'] = $element->getAttribute('Location');
		}

		if($isIndexed) {
			if(!$element->hasAttribute('index')) {
				throw new Exception($name . ' missing required index attribute.');
			}
			$ep['index'] = $element->getAttribute('index');

			if($element->hasAttribute('isDefault')) {
				$t = $element->getAttribute('isDefault');
				if($t === 'false') {
					$ep['isDefault'] = FALSE;
				} elseif($t === 'true') {
					$ep['isDefault'] = TRUE;
				} else {
					throw new Exception('Invalid value for isDefault attribute on ' .
						$name . ' element: ' . $t);
				}
			}
		}

		return $ep;
	}


	/**
	 * This function parses a KeyDescriptor element. It currently only supports keys with a single
	 * X509 certificate.
	 *
	 * The associative array for a key can contain:
	 * - 'encryption': Indicates wheter this key can be used for encryption.
	 * - 'signing': Indicates wheter this key can be used for signing.
	 * - 'type: The type of the key. 'X509Certificate' is the only key type we support.
	 * - 'X509Certificate': The contents of the first X509Certificate element (if the type is 'X509Certificate ').
	 *
	 * @param $kd  The KeyDescriptor element.
	 * @return Associative array describing the key, or NULL if this is an unsupported key.
	 */
	private static function parseKeyDescriptor($kd) {
		assert('$kd instanceof DOMElement');

		$r = array();

		if($kd->hasAttribute('use')) {
			$use = $kd->getAttribute('use');
			if($use === 'encryption') {
				$r['encryption'] = TRUE;
				$r['signing'] = FALSE;
			} elseif($use === 'signing') {
				$r['encryption'] = FALSE;
				$r['signing'] = TRUE;
			} else {
				throw new Exception('Invalid use-value for KeyDescriptor: ' . $use);
			}
		} else {
			$r['encryption'] = TRUE;
			$r['signing'] = TRUE;
		}

		$keyInfo = SimpleSAML_Utilities::getDOMChildren($kd, 'KeyInfo', '@ds');
		if(count($keyInfo) === 0) {
			throw new Exception('Missing required KeyInfo field for KeyDescriptor.');
		}
		$keyInfo = $keyInfo[0];

		$X509Data = SimpleSAML_Utilities::getDOMChildren($keyInfo, 'X509Data', '@ds');
		if(count($X509Data) === 0) {
			return NULL;
		}
		$X509Data = $X509Data[0];

		$X509Certificate = SimpleSAML_Utilities::getDOMChildren($X509Data, 'X509Certificate', '@ds');
		if(count($X509Certificate) === 0) {
			return NULL;
		}
		$X509Certificate = $X509Certificate[0];

		$r['type'] = 'X509Certificate';
		$r['X509Certificate'] = SimpleSAML_Utilities::getDOMText($X509Certificate);

		return $r;
	}


	/**
	 * This function attempts to locate the default endpoint which supports one of the given bindings.
	 *
	 * @param $endpoints Array with endpoints in the format returned by parseGenericEndpoint.
	 * @param $acceptedBindings Array with the accepted bindings. If this is NULL, then we accept any binding.
	 * @return The default endpoint which supports one of the bindings, or NULL if no endpoints supports
	 *         one of the bindings.
	 */
	private function getDefaultEndpoint($endpoints, $acceptedBindings = NULL) {

		assert('$acceptedBindings === NULL || is_array($acceptedBindings)');

		/* Filter the list of endpoints if $acceptedBindings !== NULL. */
		if($acceptedBindings !== NULL) {
			$newEndpoints = array();

			foreach($endpoints as $ep) {
				/* Add it to the list of valid ACSs if it has one of the supported bindings. */
				if(in_array($ep['binding'], $acceptedBindings, TRUE)) {
					$newEndpoints[] = $ep;
				}
			}

			$endpoints = $newEndpoints;
		}


		/* First we look for the endpoint with isDefault set to true. */
		foreach($endpoints as $ep) {

			if(array_key_exists('isDefault', $ep) && $ep['isDefault'] === TRUE) {
				return $ep;
			}
		}

		/* Then we look for the first endpoint without isDefault set to FALSE. */
		foreach($endpoints as $ep) {

			if(!array_key_exists('isDefault', $ep)) {
				return $ep;
			}
		}

		/* Then we take the first endpoint we find. */
		if(count($endpoints) > 0) {
			return $endpoints[0];
		}

		/* If we reach this point, then we don't have any endpoints with the correct binding. */
		return NULL;
	}


	/**
	 * This function finds SP descriptors which supports one of the given protocols.
	 *
	 * @param $protocols Array with the protocols we accept.
	 * @return Array with SP descriptors which supports one of the given protocols.
	 */
	private function getSPDescriptors($protocols) {
		assert('is_array($protocols)');

		$ret = array();

		foreach($this->spDescriptors as $spd) {
			$sharedProtocols = array_intersect($protocols, $spd['protocols']);
			if(count($sharedProtocols) > 0) {
				$ret[] = $spd;
			}
		}

		return $ret;
	}


	/**
	 * This function finds IdP descriptors which supports one of the given protocols.
	 *
	 * @param $protocols Array with the protocols we accept.
	 * @return Array with IdP descriptors which supports one of the given protocols.
	 */
	private function getIdPDescriptors($protocols) {
		assert('is_array($protocols)');

		$ret = array();

		foreach($this->idpDescriptors as $idpd) {
			$sharedProtocols = array_intersect($protocols, $idpd['protocols']);
			if(count($sharedProtocols) > 0) {
				$ret[] = $idpd;
			}
		}

		return $ret;
	}


	/**
	 * This function locates the EntityDescriptor node in a DOMDocument. This node should
	 * be the first (and only) node in the document.
	 *
	 * This function will throw an exception if it is unable to locate the node.
	 *
	 * @param $doc The DOMDocument where we should find the EntityDescriptor node.
	 * @return The DOMEntity which represents the EntityDescriptor.
	 */
	private static function findEntityDescriptor($doc) {

		assert('$doc instanceof DOMDocument');

		/* Find the EntityDescriptor DOMElement. This should be the first (and only) child of the
		 * DOMDocument.
		 */
		$ed = $doc->documentElement;

		if($ed === NULL) {
			throw new Exception('Failed to load SAML metadata from empty XML document.');
		}

		if(SimpleSAML_Utilities::isDOMElementOfType($ed, 'EntityDescriptor', '@md') === FALSE) {
			throw new Exception('Expected first element in the metadata document to be an EntityDescriptor element.');
		}

		return $ed;
	}


	/**
	 * This function extracts a list of supported protocols from a SPSSODescriptor or IDPSSODescriptor element.
	 *
	 * @param $element The SPSSODescriptor or IDPSSODescriptor element.
	 * @return Array with the supported protocols.
	 */
	private static function getSupportedProtocols($element) {
		assert('$element instanceof DOMElement');

		/* The protocolSupportEnumeration is a required attribute. */
		if(!$element->hasAttribute('protocolSupportEnumeration')) {
			throw new Exception($element->tagName . ' is missing the required protocolSupportEnumeration attribute.');
		}

		/* The attribute is a space seperated list of supported protocols. */
		$supProt = $element->getAttribute('protocolSupportEnumeration');
		$supProt = explode(' ', $supProt);

		return $supProt;
	}

}

?>