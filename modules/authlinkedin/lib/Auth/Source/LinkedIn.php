<?php

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/oauth/libextinc/OAuth.php');

/**
 * Authenticate using LinkedIn.
 *
 * @author Brook Schofield, TERENA.
 * @package SimpleSAMLphp
 */
class sspmod_authlinkedin_Auth_Source_LinkedIn extends SimpleSAML_Auth_Source {

	/**
	 * The string used to identify our states.
	 */
	const STAGE_INIT = 'authlinkedin:init';

	/**
	 * The key of the AuthId field in the state.
	 */
	const AUTHID = 'authlinkedin:AuthId';

	private $key;
	private $secret;
	private $attributes;


	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');

		// Call the parent constructor first, as required by the interface
		parent::__construct($info, $config);

		if (!array_key_exists('key', $config))
			throw new Exception('LinkedIn authentication source is not properly configured: missing [key]');

		$this->key = $config['key'];

		if (!array_key_exists('secret', $config))
			throw new Exception('LinkedIn authentication source is not properly configured: missing [secret]');

		$this->secret = $config['secret'];

		if (array_key_exists('attributes', $config)) {
			$this->attributes = $config['attributes'];
		} else {
			// Default values if the attributes are not set in config (ref https://developer.linkedin.com/docs/fields)
			$this->attributes = 'id,first-name,last-name,headline,summary,specialties,picture-url,email-address';
		}
	}


	/**
	 * Log-in using LinkedIn platform
	 * Documentation at: http://developer.linkedin.com/docs/DOC-1008
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		// We are going to need the authId in order to retrieve this authentication source later
		$state[self::AUTHID] = $this->authId;

		$stateID = SimpleSAML_Auth_State::getStateId($state);
		SimpleSAML\Logger::debug('authlinkedin auth state id = ' . $stateID);

		$consumer = new sspmod_oauth_Consumer($this->key, $this->secret);

		// Get the request token
		$requestToken = $consumer->getRequestToken('https://api.linkedin.com/uas/oauth/requestToken', array('oauth_callback' => SimpleSAML\Module::getModuleUrl('authlinkedin') . '/linkback.php?stateid=' . $stateID));

		SimpleSAML\Logger::debug("Got a request token from the OAuth service provider [" .
			$requestToken->key . "] with the secret [" . $requestToken->secret . "]");

		$state['authlinkedin:requestToken'] = $requestToken;

		// Update the state
		SimpleSAML_Auth_State::saveState($state, self::STAGE_INIT);

		// Authorize the request token
		$consumer->getAuthorizeRequest('https://www.linkedin.com/uas/oauth/authenticate', $requestToken);
	}


	public function finalStep(&$state) {
		$requestToken = $state['authlinkedin:requestToken'];

		$consumer = new sspmod_oauth_Consumer($this->key, $this->secret);

		SimpleSAML\Logger::debug("oauth: Using this request token [" .
			$requestToken->key . "] with the secret [" . $requestToken->secret . "]");

		// Replace the request token with an access token (via GET method)
		$accessToken = $consumer->getAccessToken('https://api.linkedin.com/uas/oauth/accessToken', $requestToken,
			array('oauth_verifier' => $state['authlinkedin:oauth_verifier']));

		SimpleSAML\Logger::debug("Got an access token from the OAuth service provider [" .
			$accessToken->key . "] with the secret [" . $accessToken->secret . "]");

		$userdata = $consumer->getUserInfo('https://api.linkedin.com/v1/people/~:(' . $this->attributes . ')', $accessToken, array('http' => array('header' => 'x-li-format: json')));

        $attributes = $this->flatten($userdata, 'linkedin.');

		// TODO: pass accessToken: key, secret + expiry as attributes?

		if (array_key_exists('id', $userdata) ) {
			$attributes['linkedin_targetedID'] = array('http://linkedin.com!' . $userdata['id']);
			$attributes['linkedin_user'] = array($userdata['id'] . '@linkedin.com');
		}

		SimpleSAML\Logger::debug('LinkedIn Returned Attributes: '. implode(", ",array_keys($attributes)));

		$state['Attributes'] = $attributes;
	}

    /**
     * takes an associative array, traverses it and returns the keys concatenated with a dot
     *
     * e.g.:
     *
     * [
     *   'linkedin' => [
     *     'location' =>  [
     *       'id' => '123456'
     *       'country' => [
     *          'code' => 'de'
     *       ]
     *   ]
     * ]
     *
     * become:
     *
     * [
     *   'linkedin.location.id' => [0 => '123456'],
     *   'linkedin.location.country.code' => [0 => 'de']
     * ]
     *
     * @param array $array
     * @param string $prefix
     *
     * @return array the array with the new concatenated keys
     */
    protected function flatten($array, $prefix = '') {
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = $result + $this->flatten($value, $prefix . $key . '.');
            } else {
                $result[$prefix . $key] = array($value);
            }
        }
        return $result;
    }
}
