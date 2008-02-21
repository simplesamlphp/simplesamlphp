<?php

require_once('SimpleSAML/Utilities.php');

/**
 * This is class for parsing of SAML 1.x and SAML 2.0 metadata.
 *
 * Metadata is loaded by calling SimpleSAML_Metadata_SAMLParser::parseFile or
 * SimpleSAML_Metadata_SAMLParser::parseString. These functions returns an instance of
 * SimpleSAML_Metadata_SAMLParser. To get metadata from this object, use the methods
 * getMetadata1xSP or getMetadata20SP.
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
	 * Each element in the array is an associative array with the following elements:
	 * - 'protocols': Array with the protocols this SPSSODescriptor supports.
	 * - 'assertionConsumerServices': Array with the SP's assertion consumer services.
	 *   Each assertion consumer service is stored as an associative array with the
	 *   elements that parseGenericEndpoint returns.
	 * - 'singleLogoutServices': Array with the SP's single logout service endpoints. Each endpoint is stored
	 *   as an associative array with the elements that parseGenericEndpoint returns.
	 * - 'nameIDFormats': The NameIDFormats that the SP accepts. This may be an empty array.
	 */
	private $spDescriptors;



	/**
	 * This is the constructor for the SAMLParser class.
	 *
	 * @param $doc The DOMDocument with the metadata.
	 */
	private function __construct($doc) {
		$this->spDescriptors = array();

		$this->processDOMDocument($doc);
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

		return new SimpleSAML_Metadata_SAMLParser($doc);
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

		return new SimpleSAML_Metadata_SAMLParser($doc);
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
		assert('$this->metadataLoaded == TRUE');

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
	 * @return Associative array with metadata or NULL if we are unable to generate metadata for a SAML 1.x SP.
	 */
	public function getMetadata20SP() {
		assert('$this->metadataLoaded == TRUE');

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
		$acs = $this->getDefaultEndpoint($spd['singleLogoutServices'], array(self::SAML_20_REDIRECT_BINDING));
		if($acs === NULL) {
			throw new Exception('Could not find any valid SingleLogoutService.' .
				' simpleSAMLphp currently supports only the http-redirect binding for SAML 1.0 logout.');
		}

		$ret['SingleLogoutService'] = $acs['location'];


		/* Find the NameIDFormat. This may not exists. */
		if(count($spd['nameIDFormats']) > 0) {
			/* simpleSAMLphp currently only supports a single NameIDFormat pr. SP. We use the first one. */
			$ret['NameIDFormat'] = $spd['nameIDFormats'][0];
		}

		return $ret;
	}


	/**
	 * This function parses a DOMDocument which contains a single EntityDescriptor element.
	 *
	 * @param $doc  The DOMDocument which should be parsed.
	 */
	private function processDOMDocument($doc) {

		$ed = self::findEntityDescriptor($doc);

		/* Extract the entityID from the EntityDescriptor element. This is a required
		 * attribute, so we throw an exception if it isn't found.
		 */
		if(!$ed->hasAttribute('entityID')) {
			throw new Exception('EntityDescriptor missing required entityID attribute.');
		}
		$this->entityID = $ed->getAttribute('entityID');


		/* Look over the child nodes for any known element types. */
		for($i = 0; $i < $ed->childNodes->length; $i++) {
			$child = $ed->childNodes->item($i);

			/* Skip text nodes. */
			if($child instanceof DOMText) {
				continue;
			}

			if(SimpleSAML_Utilities::isDOMElementOfType($child, 'SPSSODescriptor', '@md') === TRUE) {
				$this->processSPSSODescriptor($child);
			}
		}

		$this->metadataLoaded = TRUE;
	}


	/**
	 * This function extracts metadata from a SPSSODescriptor element.
	 *
	 * @param $element The element which should be parsed.
	 */
	private function processSPSSODescriptor($element) {
		assert('$element instanceof DOMElement');

		$sp = array();

		$sp['protocols'] = self::getSupportedProtocols($element);

		/* Find all AssertionConsumerService elements. */
		$sp['assertionConsumerServices'] = array();
		$acs = SimpleSAML_Utilities::getDOMChildren($element, 'AssertionConsumerService', '@md');
		foreach($acs as $child) {
			$sp['assertionConsumerServices'][] = self::parseAssertionConsumerService($child);
		}

		/* Find all SingleLogoutService elements. */
		$sp['singleLogoutServices'] = array();
		$sls = SimpleSAML_Utilities::getDOMChildren($element, 'SingleLogoutService', '@md');
		foreach($sls as $child) {
			$sp['singleLogoutServices'][] = self::parseSingleLogoutService($child);
		}

		/* Process NameIDFormat elements. */
		$sp['nameIDFormats'] = array();
		$nif = SimpleSAML_Utilities::getDOMChildren($element, 'NameIDFormat', '@md');
		if(count($nif) > 0) {
			$sp['nameIDFormats'][] = self::parseNameIDFormat($nif[0]);
		}


		$this->spDescriptors[] = $sp;
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
	 * This function parses NameIDFormat elements.
	 *
	 * @param $element The element which should be parsed.
	 * @return URN with the supported NameIDFormat.
	 */
	private static function parseNameIDFormat($element) {
		assert('$element instanceof DOMElement');

		$fmt = '';

		for($i = 0; $i < $element->childNodes->length; $i++) {
			$child = $element->childNodes->item($i);
			if(!($child instanceof DOMText)) {
				throw new Exception('NameIDFormat contained a non-text child node.');
			}

			$fmt .= $child->wholeText;
		}

		$fmt = trim($fmt);
		return $fmt;
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