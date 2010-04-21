<?php

/**
 * Class representing SAML 2 EndpointType.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class SAML2_XML_md_EndpointType {

	/**
	 * The binding for this endpoint.
	 *
	 * @var string
	 */
	public $Binding;


	/**
	 * The URI to this endpoint.
	 *
	 * @var string
	 */
	public $Location;


	/**
	 * The URI where responses can be delivered.
	 *
	 * @var string|NULL
	 */
	public $ResponseLocation = NULL;


	/**
	 * Initialize an EndpointType.
	 *
	 * @param DOMElement|NULL $xml  The XML element we should load.
	 */
	public function __construct(DOMElement $xml = NULL) {

		if ($xml === NULL) {
			return;
		}

		if (!$xml->hasAttribute('Binding')) {
			throw new Exception('Missing Binding on ' . $xml->tagName);
		}
		$this->Binding = $xml->getAttribute('Binding');

		if (!$xml->hasAttribute('Location')) {
			throw new Exception('Missing Location on ' . $xml->tagName);
		}
		$this->Location = $xml->getAttribute('Location');

		if ($xml->hasAttribute('ResponseLocation')) {
			$this->ResponseLocation = $xml->getAttribute('ResponseLocation');
		}
	}


	/**
	 * Add this endpoint to an XML element.
	 *
	 * @param DOMElement $parent  The element we should append this endpoint to.
	 * @param string $name  The name of the element we should create.
	 */
	public function toXML(DOMElement $parent, $name) {
		assert('is_string($name)');
		assert('is_string($this->Binding)');
		assert('is_string($this->Location)');
		assert('is_null($this->ResponseLocation) || is_string($this->ResponseLocation)');

		$e = $parent->ownerDocument->createElementNS(SAML2_Const::NS_MD, $name);
		$parent->appendChild($e);

		$e->setAttribute('Binding', $this->Binding);
		$e->setAttribute('Location', $this->Location);

		if (isset($this->ResponseLocation)) {
			$e->setAttribute('ResponseLocation', $this->ResponseLocation);
		}

		return $e;
	}

}
