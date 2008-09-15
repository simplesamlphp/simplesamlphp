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


	/**
	 * Initialize the builder.
	 *
	 * @param string $entityId  The entity id of the entity.
	 */
	public function __construct($entityId) {
		assert('is_string($entityId)');

		$this->document = new DOMDocument();
		$this->entityDescriptor = $this->createElement('EntityDescriptor');

		$this->entityDescriptor->setAttribute('entityID', $entityId);
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

		if (array_key_exists('AssertionConsumerService', $metadata)) {
			$t = $this->createElement('AssertionConsumerService');
			$t->setAttribute('Binding', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST');
			$t->setAttribute('Location', $metadata['AssertionConsumerService']);
			$e->appendChild($t);
		}

		$this->entityDescriptor->appendChild($e);
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

		if (array_key_exists('AssertionConsumerService', $metadata)) {
			$t = $this->createElement('AssertionConsumerService');
			$t->setAttribute('Binding', 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post');
			$t->setAttribute('Location', $metadata['AssertionConsumerService']);
			$e->appendChild($t);
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
	 * Create DOMElement in metadata namespace.
	 *
	 * Helper function for creating DOMElements with the metadata namespace.
	 *
	 * @param string $name  The name of the DOMElement.
	 * @return DOMElement  The new DOMElement.
	 */
	private function createElement($name) {
		assert('is_string($name)');

		return $this->document->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', $name);
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

		if (!array_key_exists('certificate', $metadata)) {
			/* No certificate to add. */
			return;
		}

		$globalConfig = SimpleSAML_Configuration::getInstance();

		$certFile = $globalConfig->getPathValue('certdir') . $metadata['certificate'];
		if (!file_exists($certFile)) {
			throw new Exception('Could not find certificate file: ' . $certFile);
		}

		$certData = file_get_contents($certFile);
		$certData = XMLSecurityDSig::get509XCert($certData, TRUE);


		$keyDescriptor = $this->createElement('KeyDescriptor');
		$ssoDesc->appendChild($keyDescriptor);

		$keyInfo = $this->document->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:KeyInfo');
		$keyDescriptor->appendChild($keyInfo);

		$x509Data = $this->document->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:X509Data');
		$keyInfo->appendChild($x509Data);

		$x509Certificate = $this->document->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:X509Certificate');
		$x509Data->appendChild($x509Certificate);

		$x509Certificate->appendChild($this->document->createTextNode($certData));
	}

}

?>