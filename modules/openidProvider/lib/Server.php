<?php

/*
 * Disable strict error reporting, since the OpenID library
 * used is PHP4-compatible, and not PHP5 strict-standards compatible.
 */
SimpleSAML_Utilities::maskErrors(E_NOTICE | E_STRICT);
if (defined('E_DEPRECATED')) {
	/* PHP 5.3 also has E_DEPRECATED. */
	SimpleSAML_Utilities::maskErrors(constant('E_DEPRECATED'));
}

/* Add the OpenID library search path. */
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(dirname(dirname(dirname(__FILE__)))) . '/lib');

/**
 * Helper class for the OpenID provider code.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_openidProvider_Server {

	/**
	 * The authencication source for this provider.
	 *
	 * @var SimpleSAML_Auth_Simple
	 */
	private $authSource;


	/**
	 * The attribute name where the username is stored.
	 *
	 * @var string
	 */
	private $usernameAttribute;


	/**
	 * The OpenID server.
	 *
	 * @var Auth_OpenID_Server
	 */
	private $server;


	/**
	 * The directory which contains the trust roots for the users.
	 *
	 * @var string
	 */
	private $trustStoreDir;


	/**
	 * The instance of the OpenID provider class.
	 *
	 * @var sspmod_openidProvider_Server
	 */
	private static $instance;


	/**
	 * Retrieve the OpenID provider class.
	 *
	 * @return sspmod_openidProvider_Server  The OpenID Provider class.
	 */
	public static function getInstance() {

		if (self::$instance === NULL) {
			self::$instance = new sspmod_openidProvider_Server();
		}
		return self::$instance;
	}


	/**
	 * The constructor for the OpenID provider class.
	 *
	 * Initializes and validates the configuration.
	 */
	private function __construct() {

		$config = SimpleSAML_Configuration::getConfig('module_openidProvider.php');

		$this->authSource = new SimpleSAML_Auth_Simple($config->getString('auth'));
		$this->usernameAttribute = $config->getString('username_attribute');

		try {
			$store = new Auth_OpenID_FileStore($config->getString('filestore'));
			$this->server = new Auth_OpenID_Server($store, $this->getServerURL());
		} catch (Exception $e) {
			throw $e;
		}

		$this->trustStoreDir = realpath($config->getString('filestore')) . '/truststore';
		if (!is_dir($this->trustStoreDir)) {
			$res = mkdir($this->trustStoreDir, 0777, TRUE);
			if (!$res) {
				throw new SimpleSAML_Error_Exception('Failed to create directory: ' . $this->trustStoreDir);
			}
		}

	}


	/**
	 * Retrieve the authentication source used by the OpenID Provider.
	 *
	 * @return SimpleSAML_Auth_Simple  The authentication source.
	 */
	public function getAuthSource() {

		return $this->authSource;
	}


	/**
	 * Retrieve the current user ID.
	 *
	 * @return string  The current user ID, or NULL if the user isn't authenticated.
	 */
	public function getUserId() {

		if (!$this->authSource->isAuthenticated()) {
			return NULL;
		}

		$attributes = $this->authSource->getAttributes();
		if (!array_key_exists($this->usernameAttribute, $attributes)) {
			throw new SimpleSAML_Error_Exception('Missing username attribute ' .
				var_export($this->usernameAttribute, TRUE) . ' in the attributes of the user.');
		}

		$values = array_values($attributes[$this->usernameAttribute]);
		if (empty($values)) {
			throw new SimpleSAML_Error_Exception('Username attribute was empty.');
		}
		if (count($values) > 1) {
			throw new SimpleSAML_Error_Exception('More than one attribute value in username.');
		}

		$userId = $values[0];
		return $userId;
	}


	/**
	 * Retrieve the current identity.
	 *
	 * @return string  The current identity, or NULL if the user isn't authenticated.
	 */
	public function getIdentity() {

		$userId = $this->getUserId();
		if ($userId === NULL) {
			return NULL;
		}

		$identity = SimpleSAML_Module::getModuleURL('openidProvider/user.php/' . $userId);
		return $identity;
	}


	/**
	 * Retrieve the URL of the server.
	 *
	 * @return string  The URL of the OpenID server.
	 */
	public function getServerURL() {

		return SimpleSAML_Module::getModuleURL('openidProvider/server.php');
	}


	/**
	 * Get the file that contains the trust roots for the user.
	 *
	 * @param string $identity  The identity of the user.
	 * @return string  The file name.
	 */
	private function getTrustFile($identity) {
		assert('is_string($identity)');

		$path = $this->trustStoreDir . '/' . sha1($identity) . '.serialized';
		return $path;
	}


	/**
	 * Get the sites the user trusts.
	 *
	 * @param string $identity  The identity of the user.
	 * @param array $trustRoots  The trust roots the user trusts.
	 */
	public function saveTrustRoots($identity, array $trustRoots) {
		assert('is_string($identity)');

		$file = $this->getTrustFile($identity);
		$tmpFile = $file . '.new.' . getmypid();

		$data = serialize($trustRoots);

		$ok = file_put_contents($tmpFile, $data);
		if ($ok === FALSE) {
			throw new SimpleSAML_Error_Exception('Failed to save file ' . var_export($tmpFile, TRUE));
		}

		$ok = rename($tmpFile, $file);
		if ($ok === FALSE) {
			throw new SimpleSAML_Error_Exception('Failed rename ' . var_export($tmpFile, TRUE) .
				' to ' . var_export($file, TRUE) . '.');
		}
	}


	/**
	 * Get the sites the user trusts.
	 *
	 * @param string $identity  The identity of the user.
	 * @return array  The trust roots the user trusts.
	 */
	public function getTrustRoots($identity) {
		assert('is_string($identity)');

		$file = $this->getTrustFile($identity);

		if (!file_exists($file)) {
			return array();
		}

		$data = file_get_contents($file);
		if ($data === FALSE) {
			throw new SimpleSAML_Error_Exception('Failed to load file ' .
				var_export($file, TRUE). '.');
		}

		$data = unserialize($data);
		if ($data === FALSE) {
			throw new SimpleSAML_Error_Exception('Error unserializing trust roots from file ' .
				var_export($file, TRUE) . '.');
		}

		return $data;
	}


	/**
	 * Add the given trust root to the user.
	 *
	 * @param string $identity  The identity of the user.
	 * @param string $trustRoot  The trust root.
	 */
	public function addTrustRoot($identity, $trustRoot) {
		assert('is_string($identity)');
		assert('is_string($trustRoot)');

		$trs = $this->getTrustRoots($identity);
		if (!in_array($trustRoot, $trs, TRUE)) {
			$trs[] = $trustRoot;
		}

		$this->saveTrustRoots($identity, $trs);
	}


	/**
	 * Remove the given trust root from the trust list of the user.
	 *
	 * @param string $identity  The identity of the user.
	 * @param string $trustRoot  The trust root.
	 */
	public function removeTrustRoot($identity, $trustRoot) {
		assert('is_string($identity)');
		assert('is_string($trustRoot)');

		$trs = $this->getTrustRoots($identity);

		$i = array_search($trustRoot, $trs, TRUE);
		if ($i === FALSE) {
			return;
		}
		array_splice($trs, $i, 1, array());
		$this->saveTrustRoots($identity, $trs);
	}


	/**
	 * Is the given trust root trusted by the user?
	 *
	 * @param string $identity  The identity of the user.
	 * @param string $trustRoot  The trust root.
	 * @return TRUE if it is trusted, FALSE if not.
	 */
	private function isTrusted($identity, $trustRoot) {
		assert('is_string($identity)');
		assert('is_string($trustRoot)');

		$trs = $this->getTrustRoots($identity);
		return in_array($trustRoot, $trs, TRUE);
	}


	/**
	 * Save the state, and return an URL that can contain a reference to the state.
	 *
	 * @param string $page  The name of the page.
	 * @param array $state  The state array.
	 * @return string  An URL with the state ID as a parameter.
	 */
	private function getStateURL($page, array $state) {
		assert('is_string($page)');

		$stateId = SimpleSAML_Auth_State::saveState($state, 'openidProvider:resumeState');
		$stateURL = SimpleSAML_Module::getModuleURL('openidProvider/' . $page);
		$stateURL = SimpleSAML_Utilities::addURLparameter($stateURL, array('StateID' => $stateId));

		return $stateURL;
	}


	/**
	 * Retrieve state by ID.
	 *
	 * @param string $stateId  The state ID.
	 * @return array  The state array.
	 */
	public function loadState($stateId) {
		assert('is_string($stateId)');

		return SimpleSAML_Auth_State::loadState($stateId, 'openidProvider:resumeState');
	}


	/**
	 * Send an OpenID response.
	 *
	 * This function never returns.
	 *
	 * @param Auth_OpenID_ServerResponse $response  The response.
	 */
	private function sendResponse(Auth_OpenID_ServerResponse $response) {

		SimpleSAML_Logger::debug('openidProvider::sendResponse');

		$webresponse = $this->server->encodeResponse($response);

		if ($webresponse->code !== 200) {
			header('HTTP/1.1 ' . $webresponse->code, TRUE, $webresponse->code);
		}

		foreach ($webresponse->headers as $k => $v) {
			header($k . ': ' . $v);
		}
		header('Connection: Close');

		print($webresponse->body);
		exit(0);
	}


	/**
	 * Process a request.
	 *
	 * This function never returns.
	 *
	 * @param Auth_OpenID_Request $request  The request we are processing.
	 */
	public function processRequest(array $state) {
		assert('isset($state["request"])');

		$request = $state['request'];

		if (!$this->authSource->isAuthenticated()) {
			if ($request->immediate) {
				/* Not logged in, and we cannot show a login form. */
				$this->sendResponse($request->answer(FALSE));
			}

			$resumeURL = $this->getStateURL('resume.php', $state);
			$this->authSource->requireAuth(array('ReturnTo' => $resumeURL));
		}

		$identity = $this->getIdentity();
		assert('$identity !== FALSE'); /* Should always be logged in here. */

		if (!$request->idSelect() && $identity !== $request->identity) {
			/* The identity in the request doesn't match the one of the logged in user. */
			throw new SimpleSAML_Error_Exception('Logged in as different user than the one requested.');
		}

		if ($this->isTrusted($identity, $request->trust_root)) {
			$trusted = TRUE;
		} elseif (isset($state['TrustResponse'])) {
			$trusted = (bool)$state['TrustResponse'];
		} else {
			if ($request->immediate) {
				/* Not trusted, and we cannot show a trust-form. */
				$this->sendResponse($request->answer(FALSE));
			}

			$trustURL = $this->getStateURL('trust.php', $state);
			SimpleSAML_Utilities::redirect($trustURL);
		}

		if (!$trusted) {
			/* The user doesn't trust this site. */
			$this->sendResponse($request->answer(FALSE));
		}

		/* The user is authenticated, and trusts this site. */
		$this->sendResponse($request->answer(TRUE, NULL, $identity));
	}


	/**
	 * Receive an incoming request.
	 *
	 * This function never returns.
	 */
	public function receiveRequest() {

		$request = $this->server->decodeRequest();

		if (!in_array($request->mode, array('checkid_immediate', 'checkid_setup'), TRUE)) {
			$this->sendResponse($this->server->handleRequest($request));
		}

		$state = array(
			'request' => $request,
		);

		$this->processRequest($state);
	}

}
