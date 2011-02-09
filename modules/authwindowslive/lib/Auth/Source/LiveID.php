<?php

/**
 * Authenticate using LiveID.
 *
 * @author Brook Schofield, TERENA.
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_authwindowslive_Auth_Source_LiveID extends SimpleSAML_Auth_Source {

	/**
	 * The string used to identify our states.
	 */
	const STAGE_INIT = 'authwindowslive:init';

	/**
	 * The key of the AuthId field in the state.
	 */
	const AUTHID = 'authwindowslive:AuthId';

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
			throw new Exception('LiveID authentication source is not properly configured: missing [key]');

		$this->key = $config['key'];

		if (!array_key_exists('secret', $config))
			throw new Exception('LiveID authentication source is not properly configured: missing [secret]');

		$this->secret = $config['secret'];
	}


	/**
	 * Log-in using LiveID platform
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		/* We are going to need the authId in order to retrieve this authentication source later. */
		$state[self::AUTHID] = $this->authId;

		$stateID = SimpleSAML_Auth_State::saveState($state, self::STAGE_INIT);

		SimpleSAML_Logger::debug('authwindowslive auth state id = ' . $stateID);

		// Authenticate the user
		// Documentation at: http://msdn.microsoft.com/en-us/library/ff749771.aspx
		$authorizeURL = 'https://consent.live.com/Connect.aspx'
				. '?wrap_client_id=' . $this->key
				. '&wrap_callback=' . urlencode(SimpleSAML_Module::getModuleUrl('authwindowslive') . '/linkback.php')
				. '&wrap_client_state=' . urlencode($stateID)
				. '&wrap_scope=WL_Profiles.View,Messenger.SignIn'
		;

                SimpleSAML_Utilities::redirect($authorizeURL);
	}



	public function finalStep(&$state) {

		SimpleSAML_Logger::debug("oauth wrap:  Using this verification code [" .
			$state['authwindowslive:wrap_verification_code'] . "]");

		// Retrieve Access Token
		// Documentation at: http://msdn.microsoft.com/en-us/library/ff749686.aspx
		$postData = 'wrap_client_id=' . urlencode($this->key)
				. '&wrap_client_secret=' . urlencode($this->secret)
				. '&wrap_callback=' . urlencode(SimpleSAML_Module::getModuleUrl('authwindowslive') . '/linkback.php')
				. '&wrap_verification_code=' . urlencode($state['authwindowslive:wrap_verification_code']);

		$context = array(
			'http' => array(
				'method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => $postData,
			),
		);

		$result = SimpleSAML_Utilities::fetch('https://consent.live.com/AccessToken.aspx', $context);

		parse_str($result, $response);

		// error checking of $response to make sure we can proceed
		if (!array_key_exists('wrap_access_token',$response))
			throw new Exception('[' . $response['error_code'] . '] ' . $response['wrap_error_reason'] .
				"\r\nNo wrap_access_token returned - cannot proceed\r\n" . $response['internal_info']);

		SimpleSAML_Logger::debug("Got an access token from the OAuth WRAP service provider [" .
			$response['wrap_access_token'] . "] for user [" . $response['uid'] . "]");

		// Documentation at: http://msdn.microsoft.com/en-us/library/ff751708.aspx
		$opts = array('http' => array('header' => "Accept: application/json\r\nAuthorization: WRAP access_token=" .
						$response['wrap_access_token'] . "\r\n"));
		$data = SimpleSAML_Utilities::fetch('https://apis.live.net/V4.1/cid-'. $response['uid'] . '/Profiles',$opts);
                $userdata = json_decode($data, TRUE);

		$attributes = array();
		$attributes['windowslive_uid'] = array($response['uid']);
		$attributes['windowslive_targetedID'] = array('http://windowslive.com!' . $response['uid']);
		$attributes['windowslive_user'] = array($response['uid'] . '@windowslive.com');

		if (array_key_exists('Entries',$userdata)) {
			foreach($userdata['Entries'][0] AS $key => $value) {
				if (is_string($value))
					$attributes['windowslive.' . $key] = array((string)$value);
			}

			if (array_key_exists('Emails', $userdata['Entries'][0]))
				$attributes['windowslive_mail'] = array($userdata['Entries'][0]['Emails'][0]['Address']);

		}


		SimpleSAML_Logger::debug('LiveID Returned Attributes: '. implode(", ",array_keys($attributes)));

		$state['Attributes'] = $attributes;
	}

}
