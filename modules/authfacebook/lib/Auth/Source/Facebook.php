<?php

/**
 * Authenticate using Facebook Platform.
 *
 * @author Andreas Åkre Solberg, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_authfacebook_Auth_Source_Facebook extends SimpleSAML_Auth_Source {


	/**
	 * The string used to identify our states.
	 */
	const STAGE_INIT = 'facebook:init';

	/**
	 * The key of the AuthId field in the state.
	 */
	const AUTHID = 'facebook:AuthId';


	private $api_key;
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

		if (!array_key_exists('api_key', $config))
			throw new Exception('Facebook authentication source is not properly configured: missing [api_key]');
		
		$this->api_key = $config['api_key'];
		
		if (!array_key_exists('secret', $config))
			throw new Exception('Facebook authentication source is not properly configured: missing [secret]');

		$this->secret = $config['secret'];

		require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/extlibinc/facebook.php');

	}


	/**
	 * Log-in using Facebook platform
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		/* We are going to need the authId in order to retrieve this authentication source later. */
		$state[self::AUTHID] = $this->authId;
		
		$stateID = SimpleSAML_Auth_State::saveState($state, self::STAGE_INIT);
		
		SimpleSAML_Logger::debug('facebook auth state id = ' . $stateID);
		
		$facebook = new Facebook($this->api_key, $this->secret);		
		$u = $facebook->require_login(SimpleSAML_Module::getModuleUrl('authfacebook') . '/linkback.php?next=' . $stateID);
		# http://developers.facebook.com/documentation.php?v=1.0&method=users.getInfo
		/* Causes an notice / warning...
		if ($facebook->api_client->error_code) {
			throw new Exception('Unable to load profile from facebook');
		}
		*/
		// http://developers.facebook.com/docs/reference/rest/users.getInfo
		$info = $facebook->api_client->users_getInfo($u, array('uid', 'first_name', 'middle_name', 'last_name', 'name', 'locale', 'current_location', 'affiliations', 'pic_square', 'profile_url', 'sex', 'email', 'pic', 'username', 'about_me', 'status', 'profile_blurb'));
		
		$attributes = array();
		foreach($info[0] AS $key => $value) {
			if (is_string($value) && !empty($value))
				$attributes['facebook.' . $key] = array((string)$value);
		}

		if (array_key_exists('username', $info[0]) )
			$attributes['facebook_user'] = array($info[0]['username'] . '@facebook.com');
		else
			$attributes['facebook_user'] = array($u . '@facebook.com');

		$attributes['facebook_targetedID'] = array('http://facebook.com!' . $u);
		$attributes['facebook_cn'] = array($info[0]['name']);

		SimpleSAML_Logger::debug('Facebook Returned Attributes: '. implode(", ",array_keys($attributes)));

		$state['Attributes'] = $attributes;
	}
	


}

?>