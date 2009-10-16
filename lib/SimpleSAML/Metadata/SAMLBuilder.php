<?php

/**
 * Class for generating SAML 2.0 metadata from simpleSAMLphp metadata arrays.
 *
 * This class builds SAML 2.0 metadata for an entity by examining the metadata for the entity.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Metadata_SAMLBuilder {



	/**
	 * The DOMDocument we are working in.
	 */
	private $document;


	/**
	 * The EntityDescriptor we are building.
	 */
	private $entityDescriptor;


	private $maxCache = NULL;
	private $maxDuration = NULL;
	
	/**
	 * Initialize the builder.
	 *
	 * @param string $entityId  The entity id of the entity.
	 */
	public function __construct($entityId, $maxCache = NULL, $maxDuration = NULL) {
		assert('is_string($entityId)');

		$this->maxCache = $maxCache;
		$this->maxDuration = $maxDuration;

		$this->document = new DOMDocument();
		
		$this->entityDescriptor = $this->createElement('EntityDescriptor');
#		$this->entityDescriptor->setAttribute('xmlns:xml', 'http://www.w3.org/XML/1998/namespace');
		$this->entityDescriptor->setAttribute('entityID', $entityId);
		
		$this->document->appendChild($this->entityDescriptor);
	}

	private function setExpiration($metadata) {
	
		if (array_key_exists('expire', $metadata)) {
			if ($metadata['expire'] - time() < $this->maxDuration)
				$this->maxDuration = $metadata['expire'] - time();
		}
			
		if ($this->maxCache !== NULL) 
			$this->entityDescriptor->setAttribute('cacheDuration', 'PT' . $this->maxCache . 'S');
		if ($this->maxDuration !== NULL) 
			$this->entityDescriptor->setAttribute('validUntil', SimpleSAML_Utilities::generateTimestamp(time() + $this->maxDuration));
	}


	/**
	 * Retrieve the EntityDescriptor.
	 *
	 * Retrieve the EntityDescriptor element which is generated for this entity.
	 * @return DOMElement  The EntityDescriptor element for this entity.
	 */
	public function getEntityDescriptor() {
		return $this->entityDescriptor;
	}


	/**
	 * Retrieve the EntityDescriptor as text.
	 *
	 * This function serializes this EntityDescriptor, and returns it as text.
	 *
	 * @param bool $formatted  Whether the returned EntityDescriptor should be
	 *                         formatted first.
	 * @return string  The serialized EntityDescriptor.
	 */
	public function getEntityDescriptorText($formatted = TRUE) {
		assert('is_bool($formatted)');

		if ($formatted) {
			SimpleSAML_Utilities::formatDOMElement($this->entityDescriptor);
		}

		return $this->document->saveXML();
	}
	
	/**
	 * @param $metadata Metadata array
	 * @param $e Reference to the element where the Extensions element should be included.
	 */
	private function addExtensions($metadata, &$e = NULL) {
		$extensions = $this->createElement('Extensions'); 
		$includeExtensions = FALSE;
		
		if (array_key_exists('tags', $metadata)) {
			$includeExtensions = TRUE;
			$attr = $this->createElement('saml:Attribute', 'urn:oasis:names:tc:SAML:2.0:assertion');
			$attr->setAttribute('Name', 'tags');
			foreach ($metadata['tags'] AS $tag) {
				$attr->appendChild($this->createTextElement('saml:AttributeValue', $tag, 'urn:oasis:names:tc:SAML:2.0:assertion'));
			}
			$extensions->appendChild($attr);
		}

		if (array_key_exists('hint.cidr', $metadata)) {
			$includeExtensions = TRUE;
			$attr = $this->createElement('saml:Attribute', 'urn:oasis:names:tc:SAML:2.0:assertion');
			$attr->setAttribute('Name', 'hint.cidr');
			$hints = self::arrayize($metadata['hint.cidr']);
			foreach ($hints AS $hint) {
				$attr->appendChild($this->createTextElement('saml:AttributeValue', $hint, 'urn:oasis:names:tc:SAML:2.0:assertion'));
			}
			$extensions->appendChild($attr);
		}

		
		if (array_key_exists('scope', $metadata)) {
			$includeExtensions = TRUE;
			foreach ($metadata['scope'] AS $scopetext) {
				$scope = $this->createElement('shibmd:Scope', 'urn:mace:shibboleth:metadata:1.0');
				$scope->setAttribute('regexp', 'false');
				$scope->appendChild($this->document->createTextNode($scopetext));
				$extensions->appendChild($scope);
			}
		}
		if ($includeExtensions) {
			if (isset($e)) {
				$e->appendChild($extensions);
			} else {
				$this->entityDescriptor->appendChild($extensions);
			}
		}
	}
	
	public static function arrayize($data) {
		if (is_array($data)) {
			return $data;
		} else {
			return array($data);
		}
	}


	
	public function addOrganizationInfo($metadata) {
		if (array_key_exists('name', $metadata)) {
			$org = $this->createElement('Organization');

			if (is_array($metadata['name'])) {
				$name = $metadata['name'];
			} else {
				$name = array('en' => $metadata['name']);
			}

			foreach($name AS $lang => $localname) {
				$orgname = $this->createTextElement('OrganizationName', $localname);
				$orgname->setAttribute('xml:lang', $lang);
				$org->appendChild($orgname);
			}

			foreach($name AS $lang => $localname) {
				$orgname = $this->createTextElement('OrganizationDisplayName', $localname);
				$orgname->setAttribute('xml:lang', $lang);
				$org->appendChild($orgname);
			}

			if (!array_key_exists('url', $metadata)) {
				/*
				 * The specification requires an OrganizationURL element, but
				 * we haven't got an URL. Insert an empty element instead.
				 */
				$url = array('en' => '');
			} elseif (is_array($metadata['url'])) {
				$url = $metadata['url'];
			} else {
				$url = array('en' => $metadata['url']);
			}

			foreach($url AS $lang => $locallink) {
				$uel = $this->createTextElement('OrganizationURL', $locallink);
				$uel->setAttribute('xml:lang', $lang);
				$org->appendChild($uel);
			}

			$this->entityDescriptor->appendChild($org);
		}
	}
	


	/**
	 * Add metadata set for entity.
	 *
	 * This function is used to add a metadata array to the entity.
	 *
	 * @param string $set  The metadata set this metadata comes from.
	 * @param array $metadata  The metadata.
	 */
	public function addMetadata($set, $metadata) {
		assert('is_string($set)');
		assert('is_array($metadata)');
		
		$this->setExpiration($metadata);

		switch ($set) {
		case 'saml20-sp-remote':
			$this->addMetadataSP20($metadata);
			break;
		case 'saml20-idp-remote':
			$this->addMetadataIdP20($metadata);
			break;
		case 'shib13-sp-remote':
			$this->addMetadataSP11($metadata);
			break;
		case 'shib13-idp-remote':
			$this->addMetadataIdP11($metadata);
			break;
		default:
			SimpleSAML_Logger::warning('Unable to generate metadata for unknown type \'' . $set . '\'.');
		}
		
		// $this->addOrganizationInfo($metadata);
	}

	/**
	 * Add SAML 2.0 SP metadata.
	 *
	 * @param array $metadata  The metadata.
	 */
	public function addMetadataSP20($metadata) {
		assert('is_array($metadata)');

		$e = $this->createElement('SPSSODescriptor');
		$e->setAttribute('protocolSupportEnumeration', 'urn:oasis:names:tc:SAML:2.0:protocol');
		
		
		$this->addExtensions($metadata, $e);

		$this->addCertificate($e, $metadata);

		if (array_key_exists('SingleLogoutService', $metadata)) {
			$t = $this->createElement('SingleLogoutService');
			$t->setAttribute('Binding', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect');
			$t->setAttribute('Location', $metadata['SingleLogoutService']);

			if (array_key_exists('SingleLogoutServiceResponse', $metadata)) {
				$t->setAttribute('ResponseLocation', $metadata['SingleLogoutServiceResponse']);
			}

			$e->appendChild($t);
		}

		if (array_key_exists('NameIDFormat', $metadata)) {
			$t = $this->createElement('NameIDFormat');
			$t->appendChild($this->document->createTextNode($metadata['NameIDFormat']));
			$e->appendChild($t);
		}

		$acsIndex = 0;
		if (array_key_exists('AssertionConsumerService', $metadata)) {
			foreach (SimpleSAML_Utilities::arrayize($metadata['AssertionConsumerService']) as $acs) {
				$t = $this->createElement('AssertionConsumerService');
				$t->setAttribute('index', (string)$acsIndex);
				$t->setAttribute('Binding', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST');
				$t->setAttribute('Location', $acs);
				$e->appendChild($t);
				$acsIndex += 1;
			}
		}
		if (array_key_exists('AssertionConsumerService.artifact', $metadata)) {
			foreach (SimpleSAML_Utilities::arrayize($metadata['AssertionConsumerService.artifact']) as $acs) {
				$t = $this->createElement('AssertionConsumerService');
				$t->setAttribute('index', (string)$acsIndex);
				$t->setAttribute('Binding', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact');
				$t->setAttribute('Location', $acs);
				$e->appendChild($t);
				$acsIndex += 1;
			}

		}


		if ( array_key_exists('name', $metadata) && array_key_exists('attributes', $metadata)) {
			/**
			 * Add an AttributeConsumingService element with information as name and description and list
			 * of requested attributes
			 */
			$attributeconsumer = $this->createElement('AttributeConsumingService');
			$attributeconsumer->setAttribute('index', '0');
			
			if (array_key_exists('name', $metadata)) {	
				if (is_array($metadata['name'])) {
					foreach($metadata['name'] AS $lang => $localname) {
						$t = $this->createTextElement('ServiceName', $localname); 
						$t->setAttribute('xml:lang', $lang);					
						$attributeconsumer->appendChild($t);
					}
				} else {
					$t = $this->createTextElement('ServiceName', $metadata['name']); 
					$t->setAttribute('xml:lang', 'en');
					$attributeconsumer->appendChild($t);
				}
			}
			
			
			
			if (array_key_exists('description', $metadata)) {	
				if (is_array($metadata['description'])) {
					foreach($metadata['description'] AS $lang => $localname) {
						$t = $this->createTextElement('ServiceDescription', $localname); 
						$t->setAttribute('xml:lang', $lang);					
						$attributeconsumer->appendChild($t);
					}
				} else {
					$t = $this->createTextElement('ServiceDescription', $metadata['description']); 
					$t->setAttribute('xml:lang', 'en');
					$attributeconsumer->appendChild($t);
				}
			}
			
			if (array_key_exists('attributes', $metadata) && is_array($metadata['attributes'])) {
				foreach ($metadata['attributes'] AS $attribute) {
					$t = $this->createElement('RequestedAttribute'); 
					$t->setAttribute('Name', $attribute);
					$attributeconsumer->appendChild($t);
				}
			}
			$e->appendChild($attributeconsumer);
		}

		$this->entityDescriptor->appendChild($e);
		

		
		if (array_key_exists('contacts', $metadata) && is_array($metadata['contacts']) ) {
			foreach($metadata['contacts'] AS $contact) {
				if (array_key_exists('contactType', $contact) && array_key_exists('emailAddress', $contact)) {
                    $this->addContact($contact['contactType'], $contact);
				}
			}
		}
		
		
		
	}


	/**
	 * Add SAML 2.0 IdP metadata.
	 *
	 * @param array $metadata  The metadata.
	 */
	public function addMetadataIdP20($metadata) {
		assert('is_array($metadata)');

		$e = $this->createElement('IDPSSODescriptor');
		$e->setAttribute('protocolSupportEnumeration', 'urn:oasis:names:tc:SAML:2.0:protocol');

		if (array_key_exists('redirect.sign', $metadata) && $metadata['redirect.sign']) {
			$e->setAttribute('WantAuthnRequestSigned', 'true');
		}
		
		$this->addExtensions($metadata, $e);

		$this->addCertificate($e, $metadata);

		if (array_key_exists('SingleLogoutService', $metadata)) {
			$t = $this->createElement('SingleLogoutService');
			$t->setAttribute('Binding', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect');
			$t->setAttribute('Location', $metadata['SingleLogoutService']);

			if (array_key_exists('SingleLogoutServiceResponse', $metadata)) {
				$t->setAttribute('ResponseLocation', $metadata['SingleLogoutServiceResponse']);
			}

			$e->appendChild($t);
		}

		if (array_key_exists('NameIDFormat', $metadata)) {
			$t = $this->createElement('NameIDFormat');
			$t->appendChild($this->document->createTextNode($metadata['NameIDFormat']));
			$e->appendChild($t);
		}

		if (array_key_exists('SingleSignOnService', $metadata)) {
			$t = $this->createElement('SingleSignOnService');
			$t->setAttribute('Binding', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect');
			$t->setAttribute('Location', $metadata['SingleSignOnService']);
			$e->appendChild($t);
		}
		
		$this->entityDescriptor->appendChild($e);
		

		
		if (array_key_exists('contacts', $metadata) && is_array($metadata['contacts']) ) {
			foreach($metadata['contacts'] AS $contact) {
				if (array_key_exists('contactType', $contact) && array_key_exists('emailAddress', $contact)) {
                    $this->addContact($contact['contactType'], $contact);
				}
			}
		}

	}


	/**
	 * Add SAML 1.1 SP metadata.
	 *
	 * @param array $metadata  The metadata.
	 */
	public function addMetadataSP11($metadata) {
		assert('is_array($metadata)');

		$e = $this->createElement('SPSSODescriptor');
		$e->setAttribute('protocolSupportEnumeration', 'urn:oasis:names:tc:SAML:1.1:protocol');

		$this->addCertificate($e, $metadata);

		if (array_key_exists('NameIDFormat', $metadata)) {
			$t = $this->createElement('NameIDFormat');
			$t->appendChild($this->document->createTextNode($metadata['NameIDFormat']));
			$e->appendChild($t);
		}

		$acsIndex = 0;
		if (array_key_exists('AssertionConsumerService', $metadata)) {
			foreach (SimpleSAML_Utilities::arrayize($metadata['AssertionConsumerService']) as $acs) {
				$t = $this->createElement('AssertionConsumerService');
				$t->setAttribute('index', (string)$acsIndex);
				$t->setAttribute('Binding', 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post');
				$t->setAttribute('Location', $acs);
				$e->appendChild($t);
				$acsIndex += 1;
			}
		}
		if (array_key_exists('AssertionConsumerService.artifact', $metadata)) {
			foreach (SimpleSAML_Utilities::arrayize($metadata['AssertionConsumerService.artifact']) as $acs) {
				$t = $this->createElement('AssertionConsumerService');
				$t->setAttribute('index', (string)$acsIndex);
				$t->setAttribute('Binding', 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01');
				$t->setAttribute('Location', $acs);
				$e->appendChild($t);
				$acsIndex += 1;
			}

		}

		$this->entityDescriptor->appendChild($e);
	}


	/**
	 * Add SAML 1.1 IdP metadata.
	 *
	 * @param array $metadata  The metadata.
	 */
	public function addMetadataIdP11($metadata) {
		assert('is_array($metadata)');

		$e = $this->createElement('IDPSSODescriptor');
		$e->setAttribute('protocolSupportEnumeration', 'urn:oasis:names:tc:SAML:1.1:protocol');

		$this->addCertificate($e, $metadata);

		if (array_key_exists('NameIDFormat', $metadata)) {
			$t = $this->createElement('NameIDFormat');
			$t->appendChild($this->document->createTextNode($metadata['NameIDFormat']));
			$e->appendChild($t);
		}

		if (array_key_exists('SingleSignOnService', $metadata)) {
			$t = $this->createElement('SingleSignOnService');
			$t->setAttribute('Binding', 'urn:mace:shibboleth:1.0:profiles:AuthnRequest');
			$t->setAttribute('Location', $metadata['SingleSignOnService']);
			$e->appendChild($t);
		}

		$this->entityDescriptor->appendChild($e);
	}


	/**
	 * Add contact information.
	 *
	 * Accepts a contact type, and an array of the following elements (all are optional):
	 * - emailAddress     Email address (as string), or array of email addresses.
	 * - telephoneNumber  Telephone number of contact (as string), or array of telephone numbers.
	 * - name             Full name of contact, either as <GivenName> <SurName>, or as <SurName>, <GivenName>.
	 * - surName          Surname of contact.
	 * - givenName        Givenname of contact.
	 * - company          Company name of contact.
	 *
	 * 'name' will only be used if neither givenName nor surName is present.
	 *
	 * The following contact types are allowed:
	 * "technical", "support", "administrative", "billing", "other"
	 *
	 * @param string $type  The type of contact.
	 * @param array $details  The details about the contact.
	 */
	public function addContact($type, $details) {
		assert('is_string($type)');
		assert('is_array($details)');
		assert('in_array($type, array("technical", "support", "administrative", "billing", "other"), TRUE)');

		/* Parse name into givenName and surName. */
		if (isset($details['name']) && empty($details['surName']) && empty($details['givenName'])) {
			$names = explode(',', $details['name'], 2);
			if (count($names) === 2) {
				$details['surName'] = trim($names[0]);
				$details['givenName'] = trim($names[1]);
			} else {
				$names = explode(' ', $details['name'], 2);
				if (count($names) === 2) {
					$details['givenName'] = trim($names[0]);
					$details['surName'] = trim($names[1]);
				} else {
					$details['surName'] = trim($names[0]);
				}
			}
		}

		$e = $this->createElement('ContactPerson');
		$e->setAttribute('contactType', $type);

		if (isset($details['company'])) {
			$e->appendChild($this->createTextElement('Company', $details['company']));
		}
		if (isset($details['givenName'])) {
			$e->appendChild($this->createTextElement('GivenName', $details['givenName']));
		}
		if (isset($details['surName'])) {
			$e->appendChild($this->createTextElement('SurName', $details['surName']));
		}

		if (isset($details['emailAddress'])) {
			$eas = $details['emailAddress'];
			if (!is_array($eas)) {
				$eas = array($eas);
			}
			foreach ($eas as $ea) {
				$e->appendChild($this->createTextElement('EmailAddress', $ea));
			}
		}

		if (isset($details['telephoneNumber'])) {
			$tlfNrs = $details['telephoneNumber'];
			if (!is_array($tlfNrs)) {
				$tlfNrs = array($tlfNrs);
			}
			foreach ($tlfNrs as $tlfNr) {
				$e->appendChild($this->createTextElement('TelephoneNumber', $tlfNr));
			}
		}

		$this->entityDescriptor->appendChild($e);
	}


	/**
	 * Create DOMElement in metadata namespace.
	 *
	 * Helper function for creating DOMElements with the metadata namespace.
	 *
	 * @param string $name  The name of the DOMElement.
	 * @return DOMElement  The new DOMElement.
	 */
	private function createElement($name, $ns = 'urn:oasis:names:tc:SAML:2.0:metadata') {
		assert('is_string($name)');
		assert('is_string($ns)');
		return $this->document->createElementNS($ns, $name);
	}


	/**
	 * Create a DOMElement in metadata namespace with a single text node.
	 *
	 * @param string $name  The name of the DOMElement.
	 * @param string $text  The text contained in the element.
	 * @return DOMElement  The new DOMElement with a text node.
	 */
	private function createTextElement($name, $text, $ns = NULL) {
		assert('is_string($name)');
		assert('is_string($text)');

		if ($ns !== NULL) {
			$node = $this->createElement($name, $ns);
		} else {
			$node = $this->createElement($name);
		}
		$node->appendChild($this->document->createTextNode($text));

		return $node;
	}


	/**
	 * Add a KeyDescriptor with an X509 certificate.
	 *
	 * @param DOMElement $ssoDesc  The IDPSSODescroptor or SPSSODecriptor the certificate
	 *                             should be added to.
	 * @param string|NULL $use  The value of the use-attribute.
	 * @param string $x509data  The certificate data.
	 */
	private function addX509KeyDescriptor(DOMElement $ssoDesc, $use, $x509data) {
		assert('in_array($use, array(NULL, "encryption", "signing"), TRUE)');
		assert('is_string($x509data)');

		$keyDescriptor = $this->createElement('KeyDescriptor');
		if ($use !== NULL) {
			$keyDescriptor->setAttribute('use', $use);
		}
		$ssoDesc->appendChild($keyDescriptor);

		$keyInfo = $this->document->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:KeyInfo');
		$keyDescriptor->appendChild($keyInfo);

		$x509Data = $this->document->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:X509Data');
		$keyInfo->appendChild($x509Data);

		$x509Certificate = $this->document->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:X509Certificate');
		$x509Data->appendChild($x509Certificate);

		$x509Certificate->appendChild($this->document->createTextNode($x509data));
	}


	/**
	 * Add certificate.
	 *
	 * Helper function for adding a certificate to the metadata.
	 *
	 * @param DOMElement $ssoDesc  The IDPSSODescroptor or SPSSODecriptor the certificate
	 *                             should be added to.
	 * @param array $metadata  The metadata for the entity.
	 */
	private function addCertificate(DOMElement $ssoDesc, $metadata) {
		assert('is_array($metadata)');

		$certInfo = SimpleSAML_Utilities::loadPublicKey($metadata);
		if ($certInfo === NULL || !array_key_exists('certData', $certInfo)) {
			/* No certificate to add. */
			return;
		}

		$certData = $certInfo['certData'];

		$this->addX509KeyDescriptor($ssoDesc, 'signing', $certData);
		$this->addX509KeyDescriptor($ssoDesc, 'encryption', $certData);
	}

}

?>
