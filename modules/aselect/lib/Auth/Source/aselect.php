<?php

/**
 * Authentication module which acts as an A-Select client
 *
 * @author Wessel Dankers, Tilburg University
 */
class sspmod_aselect_Auth_Source_aselect extends SimpleSAML_Auth_Source {
	private $app_id = 'simplesamlphp';
	private $server_id;
	private $server_url;
	private $private_key;

	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		$cfg = SimpleSAML_Configuration::loadFromArray($config,
			'Authentication source ' . var_export($this->authId, true));

		$cfg->getValueValidate('type', array('app'), 'app');
		$this->app_id = $cfg->getString('app_id');
		$this->private_key = $cfg->getString('private_key', null);

		// accept these arguments with '_' for consistency
		// accept these arguments without '_' for backwards compatibility
		$this->server_id = $cfg->getString('serverid', null);
		if($this->server_id === null)
			$this->server_id = $cfg->getString('server_id');

		$this->server_url = $cfg->getString('serverurl', null);
		if($this->server_url === null)
			$this->server_url = $cfg->getString('server_url');
	}

	/**
	 * Initiate authentication.
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		$state['aselect::authid'] = $this->authId;
		$id = SimpleSAML_Auth_State::saveState($state, 'aselect:login', true);

		try {
			$app_url = SimpleSAML_Module::getModuleURL('aselect/credentials.php', array('ssp_state' => $id));
			$as_url = $this->request_authentication($app_url);

			SimpleSAML_Utilities::redirect($as_url);
		} catch(Exception $e) {
			// attach the exception to the state
			SimpleSAML_Auth_State::throwException($state, $e);
		}
	}

	/**
	 * Sign a string using the configured private key
	 *
	 * @param string $str  The string to calculate a signature for
	 */
	private function base64_signature($str) {
		$key = openssl_pkey_get_private($this->private_key);
		if($key === false)
			throw new SimpleSAML_Error_Exception("Unable to load private key: ".openssl_error_string());
		if(!openssl_sign($str, $sig, $key))
			throw new SimpleSAML_Error_Exception("Unable to create signature: ".openssl_error_string());
		openssl_pkey_free($key);
		return base64_encode($sig);
	}

	/**
	 * Parse a base64 encoded attribute blob. Can't use parse_str() because it
	 * may contain multi-valued attributes.
	 *
	 * @param string $base64  The base64 string to decode.
	 */
	private static function decode_attributes($base64) {
		$blob = base64_decode($base64, true);
		if($blob === false)
			throw new SimpleSAML_Error_Exception("Attributes parameter base64 malformed");
		$pairs = explode('&', $blob);
		$ret = array();
		foreach($pairs as $pair) {
			$keyval = explode('=', $pair, 2);
			if(count($keyval) < 2)
				throw new SimpleSAML_Error_Exception("Missing value in attributes parameter");
			$key = urldecode($keyval[0]);
			$val = urldecode($keyval[1]);
			$ret[$key][] = $val;
		}
		return $ret;
	}

	/**
	 * Default options for curl invocations.
	 */
	private static $curl_options = array(
		CURLOPT_BINARYTRANSFER => true,
		CURLOPT_FAILONERROR => true,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CONNECTTIMEOUT => 1,
		CURLOPT_TIMEOUT => 5,
		CURLOPT_USERAGENT => "simpleSAMLphp",
	);

	/**
	 * Create a (possibly signed) URL to contact the A-Select server.
	 *
	 * @param string $request    The name of the request (authenticate / verify_credentials).
	 * @param array $parameters  The parameters to pass for this request.
	 */
	private function create_aselect_url($request, $parameters) {
		$parameters['request'] = $request;
		$parameters['a-select-server'] = $this->server_id;
		if(!is_null($this->private_key)) {
			$signable = '';
			foreach(array('a-select-server', 'app_id', 'app_url', 'aselect_credentials', 'rid') as $p)
				if(array_key_exists($p, $parameters))
					$signable .= $parameters[$p];
			$parameters['signature'] = $this->base64_signature($signable);
		}
		return SimpleSAML_Utilities::addURLparameter($this->server_url, $parameters);
	}

	/**
	 * Contact the A-Select server and return the result as an associative array.
	 *
	 * @param string $request    The name of the request (authenticate / verify_credentials).
	 * @param array $parameters  The parameters to pass for this request.
	 */
	private function call_aselect($request, $parameters) {
		$url = $this->create_aselect_url($request, $parameters);

		$curl = curl_init($url);
		if($curl === false)
			throw new SimpleSAML_Error_Exception("Unable to create CURL handle");

		if(!curl_setopt_array($curl, self::$curl_options))
			throw new SimpleSAML_Error_Exception("Unable to set CURL options: ".curl_error($curl));

		$str = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if($str === false)
			throw new SimpleSAML_Error_Exception("Unable to retrieve URL: $error");

		parse_str($str, $res);

		// message is only available with some A-Select server implementations
		if($res['result_code'] != '0000')
			if(array_key_exists('message', $res))
				throw new SimpleSAML_Error_Exception("Unable to contact SSO service: result_code=".$res['result_code']." message=".$res['message']);
			else
				throw new SimpleSAML_Error_Exception("Unable to contact SSO service: result_code=".$res['result_code']);
		unset($res['result_code']);

		return $res;
	}

	/**
	 * Initiate authentication. Returns a URL to redirect the user to.
	 *
	 * @param string $app_url  The SSP URL to return to after authenticating (similar to an ACS).
	 */
	public function request_authentication($app_url) {
		$res = $this->call_aselect('authenticate',
			array('app_id' => $this->app_id, 'app_url' => $app_url));

		$as_url = $res['as_url'];
		unset($res['as_url']);

		return SimpleSAML_Utilities::addURLparameter($as_url, $res);
	}

	/**
	 * Verify the credentials upon return from the A-Select server. Returns an associative array
	 * with the information given by the A-Select server. Any attributes are pre-parsed.
	 *
	 * @param string $server_id    The A-Select server ID as passed by the client
	 * @param string $credentials  The credentials as passed by the client
	 * @param string $rid          The request ID as passed by the client
	 */
	public function verify_credentials($server_id, $credentials, $rid) {
		if($server_id != $this->server_id)
			throw new SimpleSAML_Error_Exception("Acquired server ID ($server_id) does not match configured server ID ($this->server_id)");

		$res = $this->call_aselect('verify_credentials',
			array('aselect_credentials' => $credentials, 'rid' => $rid));

		if(array_key_exists('attributes', $res))
			$res['attributes'] = self::decode_attributes($res['attributes']);

		return $res;
	}
}
