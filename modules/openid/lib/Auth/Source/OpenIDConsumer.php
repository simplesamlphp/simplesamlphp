<?php

/*
 * Disable strict error reporting, since the OpenID library
 * used is PHP4-compatible, and not PHP5 strict-standards compatible.
 */
SimpleSAML_Utilities::maskErrors(E_STRICT);

/* Add the OpenID library search path. */
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/lib');

require_once('Auth/OpenID/SReg.php');
require_once('Auth/OpenID/Server.php');
require_once('Auth/OpenID/ServerRequest.php');


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
	private function getRequiredAttributes() {
		return $this->requiredAttributes;
	}


	/**
	 * Retrieve optional attributes.
	 *
	 * @return array  Optional attributes.
	 */
	private function getOptionalAttributes() {
		return $this->optionalAttributes;
	}


	/**
	 * Retrieve the Auth_OpenID_Consumer instance.
	 *
	 * @param array &$state  The state array we are currently working with.
	 * @return Auth_OpenID_Consumer  The Auth_OpenID_Consumer instance.
	 */
	private function getConsumer(array &$state) {
		$store = new sspmod_openid_StateStore($state);
		$session = new sspmod_openid_SessionStore();
		return new Auth_OpenID_Consumer($store, $session);
	}


	/**
	 * Retrieve the URL we should return to after successful authentication.
	 *
	 * @return string  The URL we should return to after successful authentication.
	 */
	private function getReturnTo($stateId) {
		assert('is_string($stateId)');

		return SimpleSAML_Module::getModuleURL('openid/consumer.php', array(
			'returned' => 1,
			'AuthState' => $stateId,
		));
	}


	/**
	 * Retrieve the trust root for this openid site.
	 *
	 * @return string  The trust root.
	 */
	private function getTrustRoot() {
		return SimpleSAML_Utilities::selfURLhost();
	}


	/**
	 * Send an authentication request to the OpenID provider.
	 *
	 * @param array &$state  The state array.
	 * @param string $openid  The OpenID we should try to authenticate with.
	 */
	public function doAuth(array &$state, $openid) {
		assert('is_string($openid)');

		$stateId = SimpleSAML_Auth_State::saveState($state, 'openid:state');

		$consumer = $this->getConsumer($state);

		// Begin the OpenID authentication process.
		$auth_request = $consumer->begin($openid);

		// No auth request means we can't begin OpenID.
		if (!$auth_request) {
			throw new Exception("Authentication error; not a valid OpenID.");
		}

		$sreg_request = Auth_OpenID_SRegRequest::build(
			$this->getRequiredAttributes(),
			$this->getOptionalAttributes()
		);

		if ($sreg_request) {
			$auth_request->addExtension($sreg_request);
		}

		// Redirect the user to the OpenID server for authentication.
		// Store the token for this authentication so we can verify the
		// response.

		// For OpenID 1, send a redirect.  For OpenID 2, use a Javascript
		// form to send a POST request to the server.
		if ($auth_request->shouldSendRedirect()) {
			$redirect_url = $auth_request->redirectURL($this->getTrustRoot(), $this->getReturnTo($stateId));

			// If the redirect URL can't be built, display an error message.
			if (Auth_OpenID::isFailure($redirect_url)) {
				throw new Exception("Could not redirect to server: " . $redirect_url->message);
			}

			SimpleSAML_Utilities::redirect($redirect_url);
		} else {
			// Generate form markup and render it.
			$form_id = 'openid_message';
			$form_html = $auth_request->formMarkup($this->getTrustRoot(), $this->getReturnTo($stateId), FALSE, array('id' => $form_id));

			// Display an error if the form markup couldn't be generated; otherwise, render the HTML.
			if (Auth_OpenID::isFailure($form_html)) {
				throw new Exception("Could not redirect to server: " . $form_html->message);
			} else {
				echo '<html><head><title>OpenID transaction in progress</title></head>
					<body onload=\'document.getElementById("' . $form_id . '").submit()\'>' .
					$form_html . '</body></html>';
			}
		}
	}


	/**
	 * Process an authentication response.
	 *
	 * @param array &$state  The state array.
	 */
	public function postAuth(array &$state) {

		$consumer = $this->getConsumer($state);

		$return_to = SimpleSAML_Utilities::selfURL();

		// Complete the authentication process using the server's
		// response.
		$response = $consumer->complete($return_to);

		// Check the response status.
		if ($response->status == Auth_OpenID_CANCEL) {
			// This means the authentication was cancelled.
			throw new Exception('Verification cancelled.');
		} else if ($response->status == Auth_OpenID_FAILURE) {
			// Authentication failed; display the error message.
			throw new Exception("OpenID authentication failed: " . $response->message);
		} else if ($response->status != Auth_OpenID_SUCCESS) {
			throw new Exceptioon('General error. Try again.');
		}

		// This means the authentication succeeded; extract the
		// identity URL and Simple Registration data (if it was
		// returned).
		$openid = $response->identity_url;

		$attributes = array('openid' => array($openid));

		if ($response->endpoint->canonicalID) {
			$attributes['openid.canonicalID'] = array($response->endpoint->canonicalID);
		}

		$sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);
		$sregresponse = $sreg_resp->contents();

		if (is_array($sregresponse) && count($sregresponse) > 0) {
			$attributes['openid.sregkeys'] = array_keys($sregresponse);
			foreach ($sregresponse AS $sregkey => $sregvalue) {
				$attributes['openid.sreg.' . $sregkey] = array($sregvalue);
			}
		}

		$state['Attributes'] = $attributes;
		SimpleSAML_Auth_Source::completeAuth($state);
	}

}
