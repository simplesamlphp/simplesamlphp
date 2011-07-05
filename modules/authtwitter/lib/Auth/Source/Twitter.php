<?php

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/oauth/libextinc/OAuth.php');

/**
 * Authenticate using Twitter.
 *
 * @author Andreas Åkre Solberg, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_authtwitter_Auth_Source_Twitter extends SimpleSAML_Auth_Source {

	/**
	 * The string used to identify our states.
	 */
	const STAGE_INIT = 'twitter:init';

	/**
	 * The key of the AuthId field in the state.
	 */
	const AUTHID = 'twitter:AuthId';

	private $key;
	private $secret;


	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		if (!array_key_exists('key', $config))
			throw new Exception('Twitter authentication source is not properly configured: missing [key]');
		
		$this->key = $config['key'];
		
		if (!array_key_exists('secret', $config))
			throw new Exception('Twitter authentication source is not properly configured: missing [secret]');

		$this->secret = $config['secret'];

		// require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/extlibinc/facebook.php');

	}


	/**
	 * Log-in using Twitter platform
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		/* We are going to need the authId in order to retrieve this authentication source later. */
		$state[self::AUTHID] = $this->authId;
		
		$stateID = SimpleSAML_Auth_State::saveState($state, self::STAGE_INIT);
		
		$consumer = new sspmod_oauth_Consumer($this->key, $this->secret);
		// Get the request token
		$linkback = SimpleSAML_Module::getModuleURL('authtwitter/linkback.php', array('AuthState' => $stateID));
		$requestToken = $consumer->getRequestToken('https://api.twitter.com/oauth/request_token', array('oauth_callback' => $linkback));
		SimpleSAML_Logger::debug("Got a request token from the OAuth service provider [" . 
			$requestToken->key . "] with the secret [" . $requestToken->secret . "]");

		$state['authtwitter:authdata:requestToken'] = $requestToken;
		SimpleSAML_Auth_State::saveState($state, self::STAGE_INIT);

		// Authorize the request token
		$consumer->getAuthorizeRequest('https://api.twitter.com/oauth/authenticate', $requestToken);
	}
	
	
	public function finalStep(&$state) {
		$requestToken = $state['authtwitter:authdata:requestToken'];
		
		$consumer = new sspmod_oauth_Consumer($this->key, $this->secret);
		
		SimpleSAML_Logger::debug("oauth: Using this request token [" . 
			$requestToken->key . "] with the secret [" . $requestToken->secret . "]");

		// Replace the request token with an access token
		$accessToken = $consumer->getAccessToken('https://api.twitter.com/oauth/access_token', $requestToken);
		SimpleSAML_Logger::debug("Got an access token from the OAuth service provider [" . 
			$accessToken->key . "] with the secret [" . $accessToken->secret . "]");
			
		$userdata = $consumer->getUserInfo('https://api.twitter.com/account/verify_credentials.json', $accessToken);
		
		if (!isset($userdata['id_str']) || !isset($userdata['screen_name'])) {
			throw new SimpleSAML_Error_AuthSource($this->authId, 'Authentication error: id_str and screen_name not set.');
		}

		$attributes = array();
		foreach($userdata AS $key => $value) {
			if (is_string($value))
				$attributes['twitter.' . $key] = array((string)$value);
		}
		
		$attributes['twitter_at_screen_name'] = array('@' . $userdata['screen_name']);
		$attributes['twitter_screen_n_realm'] = array($userdata['screen_name'] . '@twitter.com');
		$attributes['twitter_targetedID'] = array('http://twitter.com!' . $userdata['id_str']);
			
		$state['Attributes'] = $attributes;
	}

}

?>