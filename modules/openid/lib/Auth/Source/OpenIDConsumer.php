<?php

/**
 * Authentication module which acts as an OpenID Consumer
 *
 * @author Andreas Åkre Solberg, <andreas.solberg@uninett.no>, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_openid_Auth_Source_OpenIDConsumer extends SimpleSAML_Auth_Source {

	/**
	 * List of optional attributes.
	 */
	private $optionalAttributes;


	/**
	 * List of required attributes.
	 */
	private $requiredAttributes;


	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		$cfgParse = SimpleSAML_Configuration::loadFromArray($config,
			'Authentication source ' . var_export($this->authId, TRUE));

		$this->optionalAttributes = $cfgParse->getArray('attributes.optional', array());
		$this->requiredAttributes = $cfgParse->getArray('attributes.required', array());
	}


	/**
	 * Initiate authentication. Redirecting the user to the consumer endpoint 
	 * with a state Auth ID.
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		$state['openid:AuthId'] = $this->authId;
		$id = SimpleSAML_Auth_State::saveState($state, 'openid:state');

		$url = SimpleSAML_Module::getModuleURL('openid/consumer.php');
		SimpleSAML_Utilities::redirect($url, array('AuthState' => $id));
	}


	/**
	 * Retrieve required attributes.
	 *
	 * @return array  Required attributes.
	 */
	public function getRequiredAttributes() {
		return $this->requiredAttributes;
	}


	/**
	 * Retrieve optional attributes.
	 *
	 * @return array  Optional attributes.
	 */
	public function getOptionalAttributes() {
		return $this->optionalAttributes;
	}

}

?>