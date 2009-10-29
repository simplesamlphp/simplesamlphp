<?php

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
	 * This is the SAML 1.0 SOAP binding.
	 */
	const SAML_1X_SOAP_BINDING = 'urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding';


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
	 * - 'AssertionConsumerService': Array with the SP's assertion consumer services.
	 *   Each assertion consumer service is stored as an associative array with the
	 *   elements that parseGenericEndpoint returns.
	 */
	private $spDescriptors;


	/**
	 * This is an array with the processed IDPSSODescriptor elements we have found.
	 * Each element in the array is an associative array with the elements from parseSSODescriptor and:
	 * - 'SingleSignOnService': Array with the IdP's single signon service endpoints. Each endpoint is stored
	 *   as an associative array with the elements that parseGenericEndpoint returns.
	 */
	private $idpDescriptors;


	/**
	 * This is an associative array with the organization name for this entity. The key of
	 * the associative array is the language code, while the value is a string with the
	 * organization name.
	 */
	private $organizationName = array();


	/**
	 * This is an associative array with the organization display name for this entity. The key of
	 * the associative array is the language code, while the value is a string with the
	 * organization display name.
	 */
	private $organizationDisplayName = array();


	/**
	 * This is an associative array with the organization URI for this entity. The key of
	 * the associative array is the language code, while the value is the URI.
	 */
	private $organizationURL = array();
	
	private $scopes;
	private $attributes;
	private $tags;


	/**
	 * This is an array of SimpleSAML_XML_Validator classes. If this EntityDescriptor is signed, one of the
	 * validators should be able to verify the fingerprint of the certificate which was used to sign
	 * this EntityDescriptor.
	 */
	private $validator = array();


	/**
	 * The original EntityDescriptor element for this entity, as a base64 encoded string.
	 */
	private $entityDescriptor;


	/**
	 * This is the constructor for the SAMLParser class.
	 *
	 * @param $entityElement The DOMElement which represents the EntityDescriptor-element.
	 * @param $entitiesValidator  A Validator instance for a signature element in the EntitiesDescriptor,
	 *                            or NULL if this EntityDescriptor isn't a child of an EntitiesDescriptor
	 *                            with a Signature element.
	 * @param int|NULL $expireTime  The unix timestamp for when this entity should expire, or NULL if unknwon.
	 */
	private function __construct(DOMElement $entityElement,	$entitiesValidator, $expireTime) {
		assert('is_null($entitiesValidator) || $entitiesValidator instanceof SimpleSAML_XML_Validator');
		assert('is_null($expireTime) || is_int($expireTime)');

		$this->spDescriptors = array();
		$this->idpDescriptors = array();

		$tmpDoc = new DOMDocument();
		$tmpDoc->appendChild($tmpDoc->importNode($entityElement, TRUE));
		$this->entityDescriptor = base64_encode($tmpDoc->saveXML());

		/* Extract the entity id from the EntityDescriptor element. This is a required
		 * attribute, so we throw an exception if it isn't found.
		 */
		if(!$entityElement->hasAttribute('entityID')) {
			throw new Exception('EntityDescriptor missing required entityID attribute.');
		}
		$this->entityId = $entityElement->getAttribute('entityID');

		if ($expireTime === NULL) {
			/* No expiry time defined by a parent element. Check if this element defines
			 * one.
			 */
			$expireTime = self::getExpireTime($entityElement);
		}


		/* Check if the Signature element from the EntitiesDescriptor can be used to verify this
		 * EntityDescriptor, and add it to the list of validators if it is.
		 */
		if($entitiesValidator !== NULL && $entitiesValidator->isNodeValidated($entityElement)) {
			$this->validator[] = $entitiesValidator;
		}

		/* Look over the child nodes for any known element types. */
		for($i = 0; $i < $entityElement->childNodes->length; $i++) {
			$child = $entityElement->childNodes->item($i);

			// Unless the child is a DOMElement, skip.
			if ( !($child instanceof DOMelement) ) continue;

			if(SimpleSAML_Utilities::isDOMElementOfType($child, 'Signature', '@ds') === TRUE) {
				$this->processSignature($child);
			}

			if(SimpleSAML_Utilities::isDOMElementOfType($child, 'SPSSODescriptor', '@md') === TRUE) {
				$this->processSPSSODescriptor($child, $expireTime);
			}

			if(SimpleSAML_Utilities::isDOMElementOfType($child, 'IDPSSODescriptor', '@md') === TRUE) {
				$this->processIDPSSODescriptor($child, $expireTime);
			}

			if(SimpleSAML_Utilities::isDOMElementOfType($child, 'Organization', '@md') === TRUE) {
				$this->processOrganization($child);
			}
			
			if(SimpleSAML_Utilities::isDOMElementOfType($child, 'Extensions', '@md') === TRUE) {
				$this->processExtensions($child);
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

		return new SimpleSAML_Metadata_SAMLParser($entityElement, NULL, NULL);
	}


	/**
	 * This function parses a file where the root node is either an EntityDescriptor element or an
	 * EntitiesDescriptor element. In both cases it will return an associative array of SAMLParser instances. If
	 * the file contains a single EntityDescriptorElement, then the array will contain a single SAMLParser
	 * instance.
	 *
	 * @param $file  The path to the file which contains the EntityDescriptor or EntitiesDescriptor element.
	 * @return An array of SAMLParser instances.
	 */
	public static function parseDescriptorsFile($file) {

		if ($file === NULL) throw new Exception('Cannot open file NULL. File name not specified.');

		$doc = new DOMDocument();

		$res = $doc->load($file);
		if($res !== TRUE) {
			throw new Exception('Failed to read XML from file: ' . $file);
		}
		if ($doc->documentElement === NULL) throw new Exception('Opened file is not an XML document: ' . $file);

		return self::parseDescriptorsElement($doc->documentElement);
	}


	/**
	 * This function parses a string with XML data. The root node of the XML data is expected to be either an
	 * EntityDescriptor element or an EntitiesDescriptor element. It will return an associative array of
	 * SAMLParser instances.
	 *
	 * @param $string  The string with XML data.
	 * @return An associative array of SAMLParser instances. The key of the array will be the entity id.
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
	 * EntitiesDescriptor element. It will return an associative array of SAMLParser instances in both cases.
	 *
	 * @param $element  The DOMElement which contains the EntityDescriptor element or the EntitiesDescriptor
	 *                  element.
	 * @return An associative array of SAMLParser instances. The key of the array will be the entity id.
	 */
	public static function parseDescriptorsElement($element) {

		if($element === NULL) {
			throw new Exception('Document was empty.');
		}

		assert('$element instanceof DOMElement');
		
		$entitiesValidator = NULL;

		if(SimpleSAML_Utilities::isDOMElementOfType($element, 'EntityDescriptor', '@md') === TRUE) {
			$elements = array($element);
			$expireTime = NULL;
		} elseif(SimpleSAML_Utilities::isDOMElementOfType($element, 'EntitiesDescriptor', '@md') === TRUE) {

			/* Check if there is a signature element in the EntitiesDescriptor. */
			if(count(SimpleSAML_Utilities::getDOMChildren($element, 'Signature', '@ds')) > 0) {
				try {
					$entitiesValidator = new SimpleSAML_XML_Validator($element, 'ID');
				} catch(Exception $e) {
					SimpleSAML_Logger::warning('SAMLParser: Error creating XML Signature validator for XML document: ' . 
						$e->getMessage());
					$entitiesValidator = NULL;
				}
			}

			$expireTime = self::getExpireTime($element);

			$elements = SimpleSAML_Utilities::getDOMChildren($element, 'EntityDescriptor', '@md');
		} else {
			throw new Exception('Unexpected root node: [' . $element->namespaceURI . ']:' .
				$element->localName);
		}

		$ret = array();
		foreach($elements as $e) {
			$entity = new SimpleSAML_Metadata_SAMLParser($e, $entitiesValidator, $expireTime);
			$ret[$entity->getEntityId()] = $entity;
		}

		return $ret;
	}


	/**
	 * Determine how long a given element can be cached.
	 *
	 * This function looks for the 'cacheDuration' and 'validUntil' attributes to determine
	 * how long a given XML-element is valid. It returns this as na unix timestamp.
	 *
	 * If both the 'cacheDuration' and 'validUntil' attributes are present, the shorter of them
	 * will be returned.
	 *
	 * @param DOMElement $element  The element we should determine the expiry time of.
	 * @return int  The unix timestamp for when the element should expire. Will be NULL if no
	 *              limit is set for the element.
	 */
	private static function getExpireTime(DOMElement $element) {

		if ($element->hasAttribute('cacheDuration')) {
			$cacheDuration = $element->getAttribute('cacheDuration');
			$cacheDuration = SimpleSAML_Utilities::parseDuration($cacheDuration, time());
		} else {
			$cacheDuration = NULL;
		}

		if ($element->hasAttribute('validUntil')) {
			$validUntil = $element->getAttribute('validUntil');
			$validUntil = SimpleSAML_Utilities::parseSAML2Time($validUntil);
		} else {
			$validUntil = NULL;
		}

		if ($cacheDuration !== NULL && $validUntil !== NULL) {
			/* Both are given. Return the shortest. */

			if($cacheDuration < $validUntil) {
				return $cacheDuration;
			} else {
				return $validUntil;
			}

		} elseif ($cacheDuration !== NULL) {
			return $cacheDuration;
		} elseif ($validUntil !== NULL) {
			return $validUntil;
		} else {
			return NULL;
		}
	}


	/**
	 * This function returns the entity id of this parsed entity.
	 *
	 * @return The entity id of this parsed entity.
	 */
	public function getEntityId() {
		return $this->entityId;
	}


	private function getMetadataCommon() {
		$ret = array();
		$ret['entityid'] = $this->entityId;
		$ret['entityDescriptor'] = $this->entityDescriptor;
		
		
		/*
		 * Add organizational metadata
		 */
		if (!empty($this->organizationName)) {
			$ret['name'] = $this->organizationName;
			$ret['description'] = $this->organizationName;
		}
		if (!empty($this->organizationDisplayName)) {
			$ret['name'] = $this->organizationDisplayName;
		}
		if (!empty($this->organizationURL)) {
			$ret['url'] = $this->organizationURL;
		}
		
		if (!empty($this->tags)) {
			$ret['tags'] = $this->tags;
		}
		return $ret;
	}


	/**
	 * This function returns the metadata for SAML 1.x SPs in the format simpleSAMLphp expects.
	 * This is an associative array with the following fields:
	 * - 'entityid': The entity id of the entity described in the metadata.
	 * - 'AssertionConsumerService': String with the url of the assertion consumer service which supports
	 *   the browser-post binding.
	 * - 'certData': X509Certificate for entity (if present).
	 *
	 * Metadata must be loaded with one of the parse functions before this function can be called.
	 *
	 * @return Associative array with metadata or NULL if we are unable to generate metadata for a SAML 1.x SP.
	 */
	public function getMetadata1xSP() {

		$ret = $this->getMetadataCommon();


		/* Find SP information which supports one of the SAML 1.x protocols. */
		$spd = $this->getSPDescriptors(self::$SAML1xProtocols);
		if(count($spd) === 0) {
			return NULL;
		}

		/* We currently only look at the first SPDescriptor which supports SAML 1.x. */
		$spd = $spd[0];

		/* Add expire time to metadata. */
		if (array_key_exists('expire', $spd)) {
			$ret['expire'] = $spd['expire'];
		}

		/* Find the assertion consumer service endpoint. */
		$acs = $this->getDefaultEndpoint($spd['AssertionConsumerService'], array(self::SAML_1X_POST_BINDING));
		if($acs === NULL) {
			SimpleSAML_Logger::warning('Could not find a supported SAML 1.x AssertionConsumerService endpoint for ' .
				var_export($ret['entityid'], TRUE) . '.');
			return;
		} else {
			$ret['AssertionConsumerService'] = $acs['Location'];
		}

		/* Add the list of attributes the SP should receive. */
		if (array_key_exists('attributes', $spd)) {
			$ret['attributes'] = $spd['attributes'];
		}

		/* Add certificate data. Only the first valid certificate will be added. */
		foreach($spd['keys'] as $key) {
			if($key['type'] !== 'X509Certificate') {
				continue;
			}

			$certData = base64_decode($key['X509Certificate']);
			if($certData === FALSE) {
				/* Empty/invalid certificate. */
				continue;
			}

			$ret['certData'] = preg_replace('/\s+/', '', str_replace(array("\r", "\n"), '', $key['X509Certificate']));
			break;
		}

		return $ret;
	}


	/**
	 * This function returns the metadata for SAML 2.0 IdPs in the format simpleSAMLphp expects.
	 * This is an associative array with the following fields:
	 * - 'entityid': The entity id of the entity described in the metadata.
	 * - 'name': Autogenerated name for this entity. Currently set to the entity id.
	 * - 'SingleSignOnService': String with the url of the SSO service which supports the redirect binding.
	 * - 'SingleLogoutService': String with the url where we should send logout requests/responses.
	 * - 'certData': X509Certificate for entity (if present).
	 * - 'certFingerprint': Fingerprint of the X509Certificate from the metadata.
	 *
	 * Metadata must be loaded with one of the parse functions before this function can be called.
	 *
	 * @return Associative array with metadata or NULL if we are unable to generate metadata for a SAML 1.x IdP.
	 */
	public function getMetadata1xIdP() {

		$ret = $this->getMetadataCommon();

		/* Find IdP information which supports the SAML 1.x protocol. */
		$idp = $this->getIdPDescriptors(self::$SAML1xProtocols);
		if(count($idp) === 0) {
			return NULL;
		}

		/* We currently only look at the first IDP descriptor which supports SAML 1.x. */
		$idp = $idp[0];

		/* Add expire time to metadata. */
		if (array_key_exists('expire', $idp)) {
			$ret['expire'] = $idp['expire'];
		}

		/* Find the SSO service endpoint. */
		$sso = $this->getDefaultEndpoint($idp['SingleSignOnService'], array(self::SAML_1x_AUTHN_REQUEST));
		if($sso === NULL) {
			SimpleSAML_Logger::warning('Could not find a supported SAML 1.x SingleSignOnService endpoint for ' .
				var_export($ret['entityid'], TRUE) . '.');
			return;
		} else {
			$ret['SingleSignOnService'] = $sso['Location'];
		}

		/* Find the ArtifactResolutionService endpoint. */
		$artifactResolutionService = $this->getDefaultEndpoint($idp['ArtifactResolutionService'], array(self::SAML_1X_SOAP_BINDING));
		if ($artifactResolutionService !== NULL) {
			$ret['ArtifactResolutionService'] = $artifactResolutionService['Location'];
		}

		/* Add certificate to metadata. Only the first valid certificate will be added. */
		$ret['certFingerprint'] = array();
		foreach($idp['keys'] as $key) {
			if($key['type'] !== 'X509Certificate') {
				continue;
			}

			$certData = base64_decode($key['X509Certificate']);
			if($certData === FALSE) {
				/* Empty/invalid certificate. */
				continue;
			}

			/* Add the certificate data to the metadata. Only the first certificate will be added. */
			$ret['certData'] = preg_replace('/\s+/', '', str_replace(array("\r", "\n"), '', $key['X509Certificate']));
			$ret['certFingerprint'][] = sha1($certData);
			break;
		}


		return $ret;
	}


	/**
	 * This function returns the metadata for SAML 2.0 SPs in the format simpleSAMLphp expects.
	 * This is an associative array with the following fields:
	 * - 'entityid': The entity id of the entity described in the metadata.
	 * - 'AssertionConsumerService': String with the url of the assertion consumer service which supports
	 *   the browser-post binding.
	 * - 'SingleLogoutService': String with the url where we should send logout requests/responses.
	 * - 'NameIDFormat': The name ID format this SP expects. This may be unset.
	 * - 'certData': X509Certificate for entity (if present).
	 *
	 * Metadata must be loaded with one of the parse functions before this function can be called.
	 *
	 * @return Associative array with metadata or NULL if we are unable to generate metadata for a SAML 2.x SP.
	 */
	public function getMetadata20SP() {

		$ret = $this->getMetadataCommon();


		/* Find SP information which supports the SAML 2.0 protocol. */
		$spd = $this->getSPDescriptors(self::$SAML20Protocols);
		if(count($spd) === 0) {
			return NULL;
		}

		/* We currently only look at the first SPDescriptor which supports SAML 2.0. */
		$spd = $spd[0];

		/* Add expire time to metadata. */
		if (array_key_exists('expire', $spd)) {
			$ret['expire'] = $spd['expire'];
		}

		/* Find the assertion consumer service endpoints. */
		$defaultACS = $this->getDefaultEndpoint($spd['AssertionConsumerService'], array(self::SAML_20_POST_BINDING));
		if($defaultACS === NULL) {
			SimpleSAML_Logger::warning('Could not find a supported SAML 2.0 AssertionConsumerService endpoint for ' .
				var_export($ret['entityid'], TRUE) . '.');
		} else {
			$defaultACS = $defaultACS['Location'];
			$retACS = array($defaultACS);

			$allACS = $this->getEndpoints($spd['AssertionConsumerService'], array(self::SAML_20_POST_BINDING));
			foreach ($allACS as $acs) {
				$acs = $acs['Location'];
				if ($acs !== $defaultACS) {
					$retACS[] = $acs;
				}
			}

			$ret['AssertionConsumerService'] = $retACS;
		}


		/* Find the single logout service endpoint. */
		$slo = $this->getDefaultEndpoint($spd['SingleLogoutService'], array(self::SAML_20_REDIRECT_BINDING));
		if($slo !== NULL) {
			$ret['SingleLogoutService'] = $slo['Location'];
			if (isset($slo['ResponseLocation']) && $slo['Location'] != $slo['ResponseLocation']) {
				$ret['SingleLogoutServiceResponse'] = $slo['ResponseLocation'];
			}
		}


		/* Find the NameIDFormat. This may not exists. */
		if(count($spd['nameIDFormats']) > 0) {
			/* simpleSAMLphp currently only supports a single NameIDFormat pr. SP. We use the first one. */
			$ret['NameIDFormat'] = $spd['nameIDFormats'][0];
		}

		/* Add the list of attributes the SP should receive. */
		if (array_key_exists('attributes', $spd)) {
			$ret['attributes'] = $spd['attributes'];
		}

		/* Add certificate data. Only the first valid certificate will be added. */
		foreach($spd['keys'] as $key) {
			if($key['type'] !== 'X509Certificate') {
				continue;
			}

			$certData = base64_decode($key['X509Certificate']);
			if($certData === FALSE) {
				/* Empty/invalid certificate. */
				continue;
			}

			$ret['certData'] = preg_replace('/\s+/', '', str_replace(array("\r", "\n"), '', $key['X509Certificate']));
			break;
		}



		return $ret;
	}


	/**
	 * This function returns the metadata for SAML 2.0 IdPs in the format simpleSAMLphp expects.
	 * This is an associative array with the following fields:
	 * - 'entityid': The entity id of the entity described in the metadata.
	 * - 'name': Autogenerated name for this entity. Currently set to the entity id.
	 * - 'SingleSignOnService': String with the url of the SSO service which supports the redirect binding.
	 * - 'SingleLogoutService': String with the url where we should send logout requests(/responses).
	 * - 'SingleLogoutServiceResponse': String where we should send logout responses (if this is different from
	 *   the 'SingleLogoutService' endpoint.
	 * - 'certData': X509Certificate for entity (if present).
	 * - 'certFingerprint': Fingerprint of the X509Certificate from the metadata.
	 *
	 * Metadata must be loaded with one of the parse functions before this function can be called.
	 *
	 * @return Associative array with metadata or NULL if we are unable to generate metadata for a SAML 2.0 IdP.
	 */
	public function getMetadata20IdP() {

		$ret = $this->getMetadataCommon();


		/* Find IdP information which supports the SAML 2.0 protocol. */
		$idp = $this->getIdPDescriptors(self::$SAML20Protocols);
		if(count($idp) === 0) {
			return NULL;
		}

		/* We currently only look at the first IDP descriptor which supports SAML 2.0. */
		$idp = $idp[0];

		/* Add expire time to metadata. */
		if (array_key_exists('expire', $idp)) {
			$ret['expire'] = $idp['expire'];
		}
		
		if (array_key_exists('scopes', $idp))
			$ret['scopes'] = $idp['scopes'];
		

		/* Enable redirect.sign if WantAuthnRequestsSigned is enabled. */
		if ($idp['WantAuthnRequestsSigned']) {
			$ret['redirect.sign'] = TRUE;
		}

		/* Find the SSO service endpoint. */
		$sso = $this->getDefaultEndpoint($idp['SingleSignOnService'], array(self::SAML_20_REDIRECT_BINDING));
		if($sso === NULL) {
			SimpleSAML_Logger::warning('Could not find a supported SAML 2.0 SingleSignOnService endpoint for ' .
				var_export($ret['entityid'], TRUE) . '.');
		} else {
			$ret['SingleSignOnService'] = $sso['Location'];
		}


		/* Find the single logout service endpoint. */
		$slo = $this->getDefaultEndpoint($idp['SingleLogoutService'], array(self::SAML_20_REDIRECT_BINDING));
		if($slo !== NULL) {
			$ret['SingleLogoutService'] = $slo['Location'];
			
			/* If the response location is set, include it in the returned metadata. */
			if(array_key_exists('ResponseLocation', $slo)) {
				$ret['SingleLogoutServiceResponse'] = $slo['ResponseLocation'];
			}
			
		}

		/* Find the ArtifactResolutionService endpoint. */
		$artifactResolutionService = $this->getDefaultEndpoint($idp['ArtifactResolutionService'], array(SAML2_Const::BINDING_SOAP));
		if ($artifactResolutionService !== NULL) {
			$ret['ArtifactResolutionService'] = $artifactResolutionService['Location'];
		}


		/* Add certificate to metadata. Only the first valid certificate will be added. */
		$ret['certFingerprint'] = array();
		foreach($idp['keys'] as $key) {
			if($key['type'] !== 'X509Certificate') {
				continue;
			}

			$certData = base64_decode($key['X509Certificate']);
			if($certData === FALSE) {
				/* Empty/invalid certificate. */
				continue;
			}

			/* Add the certificate data to the metadata. Only the first certificate will be added. */
			$ret['certData'] = preg_replace('/\s+/', '', str_replace(array("\r", "\n"), '', $key['X509Certificate']));
			$ret['certFingerprint'][] = sha1($certData);
			break;
		}

		return $ret;
	}



	/**
	 * This function extracts metadata from a SSODescriptor element.
	 *
	 * The returned associative array has the following elements:
	 * - 'protocols': Array with the protocols this SSODescriptor supports.
	 * - 'SingleLogoutService': Array with the single logout service endpoints. Each endpoint is stored
	 *   as an associative array with the elements that parseGenericEndpoint returns.
	 * - 'nameIDFormats': The NameIDFormats supported by this SSODescriptor. This may be an empty array.
	 * - 'keys': Array of associative arrays with the elements from parseKeyDescriptor:
	 *
	 * @param $element The element we should extract metadata from.
	 * @param int|NULL $expireTime  The unix timestamp for when this element should expire, or
	 *                              NULL if unknwon.
	 * @return Associative array with metadata we have extracted from this element.
	 */
	private static function parseSSODescriptor($element, $expireTime) {
		assert('$element instanceof DOMElement');
		assert('is_null($expireTime) || is_int($expireTime)');

		if ($expireTime === NULL) {
			/* No expiry time defined by a parent element. Check if this element defines
			 * one.
			 */
			$expireTime = self::getExpireTime($element);
		}


		$sd = array();

		if ($expireTime !== NULL) {
			/* We have got an expire timestamp, either from this element, or one of the
			 * parent elements.
			 */
			$sd['expire'] = $expireTime;
		}

		$sd['protocols'] = self::getSupportedProtocols($element);
		

		/* Find all SingleLogoutService elements. */
		$sd['SingleLogoutService'] = array();
		$sls = SimpleSAML_Utilities::getDOMChildren($element, 'SingleLogoutService', '@md');
		foreach($sls as $child) {
			$sd['SingleLogoutService'][] = self::parseSingleLogoutService($child);
		}

		/* Find all ArtifactResolutionService elements. */
		$sd['ArtifactResolutionService'] = array();
		$acs = SimpleSAML_Utilities::getDOMChildren($element, 'ArtifactResolutionService', '@md');
		foreach($acs as $child) {
			$sd['ArtifactResolutionService'][] = self::parseArtifactResolutionService($child);
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
	 * @param int|NULL $expireTime  The unix timestamp for when this element should expire, or
	 *                              NULL if unknwon.
	 */
	private function processSPSSODescriptor($element, $expireTime) {
		assert('$element instanceof DOMElement');
		assert('is_null($expireTime) || is_int($expireTime)');

		$sp = self::parseSSODescriptor($element, $expireTime);

		/* Find all AssertionConsumerService elements. */
		$sp['AssertionConsumerService'] = array();
		$acs = SimpleSAML_Utilities::getDOMChildren($element, 'AssertionConsumerService', '@md');
		foreach($acs as $child) {
			$sp['AssertionConsumerService'][] = self::parseAssertionConsumerService($child);
		}

		/* Find all the attributes and SP name... */
		#$sp['attributes'] = array();
		$attcs = SimpleSAML_Utilities::getDOMChildren($element, 'AttributeConsumingService', '@md');
		if (count($attcs) > 0) {
			self::parseAttributeConsumerService($attcs[0], $sp);
		}	

		$this->spDescriptors[] = $sp;
	}


	/**
	 * This function extracts metadata from a IDPSSODescriptor element.
	 *
	 * @param $element The element which should be parsed.
	 * @param int|NULL $expireTime  The unix timestamp for when this element should expire, or
	 *                              NULL if unknwon.
	 */
	private function processIDPSSODescriptor($element, $expireTime) {
		assert('$element instanceof DOMElement');
		assert('is_null($expireTime) || is_int($expireTime)');

		$idp = self::parseSSODescriptor($element, $expireTime);
		
		$extensions = SimpleSAML_Utilities::getDOMChildren($element, 'Extensions', '@md');
		if (!empty($extensions)) 
			$this->processExtensions($extensions[0]);

		if (!empty($this->scopes)) $idp['scopes'] = $this->scopes;
		

		/* Find all SingleSignOnService elements. */
		$idp['SingleSignOnService'] = array();
		$acs = SimpleSAML_Utilities::getDOMChildren($element, 'SingleSignOnService', '@md');
		foreach($acs as $child) {
			$idp['SingleSignOnService'][] = self::parseSingleSignOnService($child);
		}

		if ($element->getAttribute('WantAuthnRequestsSigned') === 'true') {
			$idp['WantAuthnRequestsSigned'] = TRUE;
		} else {
			$idp['WantAuthnRequestsSigned'] = FALSE;
		}

		$this->idpDescriptors[] = $idp;
	}


	/**
	 * Parse and process a Extensions element.
	 *
	 * @param $element  The DOMElement which represents the Organization element.
	 */
	private function processExtensions($element) {
		assert('$element instanceof DOMElement');
		
		
		for($i = 0; $i < $element->childNodes->length; $i++) {
			$child = $element->childNodes->item($i);

			/* Skip text nodes. */
			if(!$child instanceof DOMElement) continue;
			
			if(SimpleSAML_Utilities::isDOMElementOfType($child, 'Scope', '@shibmd')) {
				$text = SimpleSAML_Utilities::getDOMText($child);
				if (!empty($text)) $this->scopes[] = $text;
			}
			
			if(SimpleSAML_Utilities::isDOMElementOfType($child, 'Attribute', '@saml2')) {

				if ($child->getAttribute('Name') === 'tags') {

					for($j = 0; $j < $child->childNodes->length; $j++) {

						$attributevalue = $child->childNodes->item($j);
						if(SimpleSAML_Utilities::isDOMElementOfType($attributevalue, 'AttributeValue', '@saml2')) {

							$tagname = SimpleSAML_Utilities::getDOMText($attributevalue);
#														echo 'attribute tags: ' . $tagname; exit;
							if (!empty($tagname)) $this->tags[] = $tagname;
						}
					}

				}
			
			}
			
			
		}
	}


	/**
	 * Parse and process a Organization element.
	 *
	 * @param $element  The DOMElement which represents the Organization element.
	 */
	private function processOrganization($element) {
		assert('$element instanceof DOMElement');

		for($i = 0; $i < $element->childNodes->length; $i++) {
			$child = $element->childNodes->item($i);

			/* Skip text nodes. */
			if($child instanceof DOMText) {
				continue;
			}

			/* Determine the type. */
			if(SimpleSAML_Utilities::isDOMElementOfType($child, 'OrganizationName', '@md')) {
				$type = 'organizationName';
			} elseif(SimpleSAML_Utilities::isDOMElementOfType($child, 'OrganizationDisplayName', '@md')) {
				$type = 'organizationDisplayName';
			} elseif(SimpleSAML_Utilities::isDOMElementOfType($child, 'OrganizationURL', '@md')) {
				$type = 'organizationURL';
			} else {
				/* Skip unknown/unhandled elements. */
				continue;
			}

			/* Extract the text. */
			$text = SimpleSAML_Utilities::getDOMText($child);

			/* Skip nodes without text. */
			if(empty($text)) {
				continue;
			}

			/* Find the language of the text. This should be stored in the xml:lang attribute. */
			$language = $child->getAttributeNS('http://www.w3.org/XML/1998/namespace', 'lang');
			//$language = $child->getAttributeNS('xml', 'lang');
			if(empty($language)) {
				/* No language given, assume 'en'. */
				$language = 'en';
			}

			/* Add the result to the appropriate list. */
			if($type === 'organizationName') {
				$this->organizationName[$language] = $text;
			} elseif($type === 'organizationDisplayName') {
				$this->organizationDisplayName[$language] = $text;
			} elseif($type === 'organizationURL') {
				$this->organizationURL[$language] = $text;
			}
		}
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
	 * This function parses AttributeConsumerService elements.
	 */
	private static function parseAttributeConsumerService($element, &$sp) {
		assert('$element instanceof DOMElement');
		assert('is_array($sp)');
				
		$elements = SimpleSAML_Utilities::getDOMChildren($element, 'ServiceName', '@md');
		foreach($elements AS $child) {
			$language = $child->getAttributeNS('http://www.w3.org/XML/1998/namespace', 'lang');
			if(empty($language)) $language = 'en';
			$sp['name'][$language] = SimpleSAML_Utilities::getDOMText($child);
		}
		
		$elements = SimpleSAML_Utilities::getDOMChildren($element, 'ServiceDescription', '@md');
		foreach($elements AS $child) {
			$language = $child->getAttributeNS('http://www.w3.org/XML/1998/namespace', 'lang');
			if(empty($language)) $language = 'en';
			$sp['description'][$language] = SimpleSAML_Utilities::getDOMText($child);
		}
		
		$elements = SimpleSAML_Utilities::getDOMChildren($element, 'RequestedAttribute', '@md');
		foreach($elements AS $child) {
			$attrname = $child->getAttribute('Name');
			if (!array_key_exists('attributes', $sp)) $sp['attributes'] = array();
			$sp['attributes'][] = $attrname;
		}	

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
	 * This function parses ArtifactResolutionService elements.
	 *
	 * @param $element The element which should be parsed.
	 * @return Associative array with the data we have extracted from the ArtifactResolutionService element.
	 */
	private static function parseArtifactResolutionService($element) {
		assert('$element instanceof DOMElement');

		return self::parseGenericEndpoint($element, TRUE);
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
	 * - 'Binding': The binding this endpoint uses.
	 * - 'Location': The URL to this endpoint.
	 * - 'ResponseLocation': The URL where responses should be sent. This may not exist.
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
		$ep['Binding'] = $element->getAttribute('Binding');

		if(!$element->hasAttribute('Location')) {
			throw new Exception($name . ' missing required Location attribute.');
		}
		$ep['Location'] = $element->getAttribute('Location');

		if($element->hasAttribute('ResponseLocation')) {
			$ep['ResponseLocation'] = $element->getAttribute('ResponseLocation');
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
	 * This function attempts to locate all endpoints which supports one of the given bindings.
	 *
	 */
	private function getEndpoints($endpoints, $acceptedBindings = NULL) {

		assert('$acceptedBindings === NULL || is_array($acceptedBindings)');

		/* Filter the list of endpoints if $acceptedBindings !== NULL. */
		if($acceptedBindings === NULL) return $endpoints;

		$newEndpoints = array();
		foreach($endpoints as $ep) {
			/* Add it to the list of valid ACSs if it has one of the supported bindings. */
			if(in_array($ep['Binding'], $acceptedBindings, TRUE)) {
				$newEndpoints[] = $ep;
			}
		}
		return $newEndpoints;
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

		$endpoints = $this->getEndpoints($endpoints, $acceptedBindings);

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


	/**
	 * This function processes a signature element in an EntityDescriptor element.
	 *
	 * It will attempt to validate the EntityDescriptor element using the signature. If the signature
	 * is good, it will and will store the fingerprint the certificate in the $validatedFingerprint variable.
	 *
	 * @param $element  The ds:Signature element.
	 */
	private function processSignature($element) {
		assert('$element instanceof DOMElement');

		/* We want to validate the EntityDescriptor which contains the signature. */
		$entityDescriptor = $element->parentNode;
		assert('$entityDescriptor instanceof DOMElement');

		/*
		 * Make a copy of the entity descriptor, so that the validator can
		 * change the DOM tree in any way it wants.
		 */
		$doc = new DOMDocument();
		$entityDescriptor = $doc->importNode($entityDescriptor, TRUE);
		$doc->appendChild($entityDescriptor);

		/* Attempt to check the signature. */
		try {
			$validator = new SimpleSAML_XML_Validator($entityDescriptor, 'ID');

			if($validator->isNodeValidated($entityDescriptor)) {
				/* The EntityDescriptor is signed. Store the validator in $this->validator, so
				 * that it can be used to verify the fingerprint of the certificate later.
				 */
				$this->validator[] = $validator;
			}
		} catch(Exception $e) {
			/* Ignore validation errors and pretend that this EntityDescriptor is unsigned. */
		}
	}


	/**
	 * This function checks if this EntityDescriptor was signed with a certificate with the
	 * given fingerprint.
	 *
	 * @param $fingerprint  Fingerprint of the certificate which should have been used to sign this
	 *                      EntityDescriptor.
	 * @return TRUE if it was signed with the certificate with the given fingerprint, FALSE otherwise.
	 */
	public function validateFingerprint($fingerprint) {

		foreach($this->validator as $validator) {
			try {
				$validator->validateFingerprint($fingerprint);
				return TRUE;
			} catch(Exception $e) {
				/* Validation with this validator failed. */
				SimpleSAML_Logger::debug('Validation of fingerprint failed: ' . $e->getMessage());
			}
		}

		return FALSE;
	}


	/**
	 * Retrieve the X509 certificate(s) which was used to sign the metadata.
	 *
	 * This function will return all X509 certificates which validates this entity.
	 * The certificates will be returned as an array with strings with PEM-encoded certificates.
	 *
	 * @return  Array with PEM-encoded certificates. This may be an empty array if no
	 *          certificates sign this entity.
	 */
	public function getX509Certificates() {
		$ret = array();

		foreach($this->validator as $validator) {
			$cert = $validator->getX509Certificate();
			if($cert !== NULL) {
				$ret[] = $cert;
			}
		}

		return $ret;
	}


	/**
	 * Validate the EntityDescriptor against a CA.
	 *
	 * @param $caFile  A file with trusted certificates, in PEM format.
	 * @return  TRUE if this CA can validate the EntityDescriptor, FALSE if not.
	 */
	public function validateCA($caFile) {

		foreach($this->validator as $validator) {
			try {
				$validator->validateCA($caFile);
				return TRUE;
			} catch(Exception $e) {
				/* Validation with this validator failed. */
			}
		}

		return FALSE;
	}

}

?>