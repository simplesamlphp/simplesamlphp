<?php

/**
 * A-Select authentication source.
 *
 * Based on www/aselect/handler.php by Hans Zandbelt, SURFnet BV. <hans.zandbelt@surfnet.nl>
 *
 * @author Patrick Honing, Hogeschool van Arnhem en Nijmegen. <Patrick.Honing@han.nl>
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_aselect_Auth_Source_aselect extends SimpleSAML_Auth_Source {

	/**
	 * The string used to identify our states.
	 */
	const STAGE_INIT = 'aselect:init';

	/**
	 * The key of the AuthId field in the state.
	 */
	const AUTHID = 'aselect:AuthId';

	/**
	 * @var array with aselect configuration
	 */
	private $asconfig;

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

		if (!array_key_exists('serverurl', $config)) throw new Exception('aselect serverurl not specified');
		$this->asconfig['serverurl'] = $config['serverurl'];

		if (!array_key_exists('serverid', $config)) throw new Exception('aselect serverid not specified');
		$this->asconfig['serverid'] = $config['serverid'];

		if (!array_key_exists('type', $config)) throw new Exception('aselect type not specified');
		$this->asconfig['type'] = $config['type'];

		if ($this->asconfig['type'] == 'app') {
			if (!array_key_exists('app_id', $config)) throw new Exception('aselect app_id not specified');
			$this->asconfig['app_id'] = $config['app_id'];
		} elseif($this->asconfig['type'] == 'cross') {
			if (!array_key_exists('local_organization', $config)) throw new Exception('aselect local_organization not specified');
			$this->asconfig['local_organization'] = $config['local_organization'];

			$this->asconfig['required_level'] = (array_key_exists('required_level', $config)) ? $config['required_level'] : 10;
		} else {
			throw new Exception('aselect type need to be either app or cross');
		}

	}


	// helper function for sending a non-browser request to a remote server
	function as_call($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$result = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);
		if ($result == FALSE) {
			throw new Exception('Request on remote server failed: ' . $error);
		}
		$parms = array();
		foreach (explode('&', $result) as $parm) {
			$tuple = explode('=', $parm);
			$parms[urldecode($tuple[0])] = urldecode($tuple[1]);
		}
		if ($parms['result_code'] != '0000') {
			throw new Exception('Request on remote server returned error: ' . $result);
		}
		return $parms;
	}


	/**
	 * Log-in using A-Select
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		/* We are going to need the authId in order to retrieve this authentication source later. */
		$state[self::AUTHID] = $this->authId;

		$stateID = SimpleSAML_Auth_State::saveState($state, self::STAGE_INIT);

		$serviceUrl = SimpleSAML_Module::getModuleURL('aselect/linkback.php', array('stateID' => $stateID));

		if ($this->asconfig['type'] == 'app') {
			$params = array(
				'request'               => 'authenticate',
				'a-select-server'       => $this->asconfig['serverid'],
				'app_id'                => $this->asconfig['app_id'],
				'app_url'               => $serviceUrl,
			);
		} else { // type = cross
			$params = array(
				'request'               => 'authenticate',
				'a-select-server'       => $this->asconfig['serverid'],
				'local_organization'    => $this->asconfig['local_organization'],
				'required_level'        => $this->asconfig['required_level'],
				'local_as_url'          => $serviceUrl,

			);
		}
		$url = SimpleSAML_Utilities::addURLparameter($this->asconfig['serverurl'],$params);

		$parm = $this->as_call($url);

		SimpleSAML_Utilities::redirect(
			$parm['as_url'],
			array(
				'rid'               => $parm['rid'],
				'a-select-server'   => $this->asconfig['serverid'],
			)
		);
	}

	public function finalStep(&$state) {
		$credentials = $state['aselect:credentials'];
		$rid = $state['aselect:rid'];
		assert('isset($credentials)');
		assert('isset($rid)');

		$params = array(
			'request'               => 'verify_credentials',
			'rid'                   => $rid,
			'a-select-server'       => $this->asconfig['serverid'],
			'aselect_credentials'   => $credentials,
		);
		if ($this->asconfig['type'] == 'cross') {
			$params['local_organization'] = $this->asconfig['local_organization'];
		}

		$url = SimpleSAML_Utilities::addURLparameter($this->asconfig['serverurl'], $params);

		$parms = $this->as_call($url);
		$attributes = array('uid' => array($parms['uid']));

		if (array_key_exists('attributes', $parms)) {
			$decoded = base64_decode($parms['attributes']);
			foreach (explode('&', $decoded) as $parm) {
				$tuple = explode('=', $parm);
				$name = urldecode($tuple[0]);
				if (preg_match('/\[\]$/',$name)) {
					$name = substr($name, 0 ,-2);
				}
				if (!array_key_exists($name, $attributes)) {
					$attributes[$name] = array();
				}
				$attributes[$name][] = urldecode($tuple[1]);
			}
		}
		$state['Attributes'] = $attributes;

		SimpleSAML_Auth_Source::completeAuth($state);
	}
}
