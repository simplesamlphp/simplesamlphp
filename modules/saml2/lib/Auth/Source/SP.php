<?php

/**
 * SAML 2.0 SP authentication client.
 *
 * Example:
 * 'example-openidp' => array(
 *   'saml2:SP',
 *   'idp' => 'https://openidp.feide.no',
 * ),
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_saml2_Auth_Source_SP extends SimpleSAML_Auth_Source {

	/**
	 * The string used to identify our states.
	 */
	const STAGE_SENT = 'saml2:SP-SSOSent';


	/**
	 * The key of the AuthId field in the state.
	 */
	const AUTHID = 'saml2:AuthId';


	/**
	 * The entity id of this SP.
	 */
	private $entityId;


	/**
	 * The entity id of the IdP we connect to.
	 */
	private $idp;


	/**
	 * Constructor for SAML 2.0 SP authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		if (array_key_exists('entityId', $config)) {
			$this->entityId = $config['entityId'];
		} else {
			$this->entityId = SimpleSAML_Module::getModuleURL('saml2/sp/metadata.php?source=' .
				urlencode($this->authId));
		}

		if (array_key_exists('idp', $config)) {
			$this->idp = $config['idp'];
		} else {
			throw new Exception('TODO: Add support for IdP discovery.');
		}
	}


	/**
	 * Start login.
	 *
	 * This function saves the information about the login, and redirects to  the IdP.
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		/* We are going to need the authId in order to retrieve this authentication source later. */
		$state[self::AUTHID] = $this->authId;

		$id = SimpleSAML_Auth_State::saveState($state, self::STAGE_SENT);

		$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
		$idpMetadata = $metadata->getMetaData($this->idp, 'saml20-idp-remote');

		$config = SimpleSAML_Configuration::getInstance();
		$sr = new SimpleSAML_XML_SAML20_AuthnRequest($config, $metadata);
		$req = $sr->generate($this->entityId, $idpMetadata['SingleSignOnService']);

		$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
		$httpredirect->sendMessage($req, $this->entityId, $this->idp, $id);
		exit(0);
	}


	/**
	 * Retrieve the entity id of this SP.
	 *
	 * @return string  Entity id of this SP.
	 */
	public function getEntityId() {

		return $this->entityId;
	}

}

?>