<?php

/**
 * Authenticate using Facebook Platform.
 *
 * @author Andreas Åkre Solberg, UNINETT AS.
 * @package SimpleSAMLphp
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


	/**
	 * Facebook App ID or API Key
	 */
	private $api_key;


	/**
	 * Facebook App Secret
	 */
	private $secret;


	/**
	 * Which additional data permissions to request from user
	 */
	private $req_perms;


	/**
	 * A comma-separated list of user profile fields to request.
	 *
	 * Note that some user fields require appropriate permissions. For
	 * example, to retrieve the user's primary email address, "email" must
	 * be specified in both the req_perms and the user_fields parameter.
	 *
	 * When empty, only the app-specific user id and name will be returned.
	 *
	 * See the Graph API specification for all available user fields:
	 * https://developers.facebook.com/docs/graph-api/reference/v2.6/user
	 */
	private $user_fields;


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

		$cfgParse = SimpleSAML_Configuration::loadFromArray($config, 'authsources[' . var_export($this->authId, TRUE) . ']');
		
		$this->api_key = $cfgParse->getString('api_key');
		$this->secret = $cfgParse->getString('secret');
		$this->req_perms = $cfgParse->getString('req_perms', NULL);
		$this->user_fields = $cfgParse->getString('user_fields', NULL);
	}


	/**
	 * Log-in using Facebook platform
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		// We are going to need the authId in order to retrieve this authentication source later
		$state[self::AUTHID] = $this->authId;
		SimpleSAML_Auth_State::saveState($state, self::STAGE_INIT);
		
		$facebook = new sspmod_authfacebook_Facebook(array('appId' => $this->api_key, 'secret' => $this->secret), $state);
		$facebook->destroySession();

		$linkback = SimpleSAML\Module::getModuleURL('authfacebook/linkback.php');
		$url = $facebook->getLoginUrl(array('redirect_uri' => $linkback, 'scope' => $this->req_perms));
		SimpleSAML_Auth_State::saveState($state, self::STAGE_INIT);

		\SimpleSAML\Utils\HTTP::redirectTrustedURL($url);
	}
		

	public function finalStep(&$state) {
		assert('is_array($state)');

		$facebook = new sspmod_authfacebook_Facebook(array('appId' => $this->api_key, 'secret' => $this->secret), $state);
		$uid = $facebook->getUser();

		if (isset($uid) && $uid) {
			try {
				$info = $facebook->api("/" . $uid . ($this->user_fields ? "?fields=" . $this->user_fields : ""));
			} catch (FacebookApiException $e) {
				throw new SimpleSAML_Error_AuthSource($this->authId, 'Error getting user profile.', $e);
			}
		}

		if (!isset($info)) {
			throw new SimpleSAML_Error_AuthSource($this->authId, 'Error getting user profile.');
		}
		
		$attributes = array();
		foreach($info AS $key => $value) {
			if (is_string($value) && !empty($value)) {
				$attributes['facebook.' . $key] = array((string)$value);
			}
		}

		if (array_key_exists('third_party_id', $info)) {
			$attributes['facebook_user'] = array($info['third_party_id'] . '@facebook.com');
		} else {
			$attributes['facebook_user'] = array($uid . '@facebook.com');
		}

		$attributes['facebook_targetedID'] = array('http://facebook.com!' . $uid);
		$attributes['facebook_cn'] = array($info['name']);

		SimpleSAML\Logger::debug('Facebook Returned Attributes: '. implode(", ", array_keys($attributes)));

		$state['Attributes'] = $attributes;
	
		$facebook->destroySession();
	}

}
