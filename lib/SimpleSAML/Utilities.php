<?php

/**
 * Misc static functions that is used several places.in example parsing and id generation.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 */
class SimpleSAML_Utilities {

	/**
	 * List of log levels.
	 *
	 * This list is used to restore the log levels after some log levels are disabled.
	 *
	 * @var array
	 */
	private static $logLevelStack = array();


	/**
	 * The current mask of disabled log levels.
	 *
	 * Note: This mask is not directly related to the PHP error reporting level.
	 *
	 * @var int
	 */
	public static $logMask = 0;


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::getSelfHost() instead.
	 */
	public static function getSelfHost() {
		return \SimpleSAML\Utils\HTTP::getSelfHost();
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::getSelfURLHost() instead.
	 */
	public static function selfURLhost() {
		return \SimpleSAML\Utils\HTTP::getSelfURLHost();
	}

	
	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::isHTTPS() instead.
	 */
	public static function isHTTPS() {
		return \SimpleSAML\Utils\HTTP::isHTTPS();
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::getSelfURLNoQuery() instead.
	 */
	public static function selfURLNoQuery() {
		return \SimpleSAML\Utils\HTTP::getSelfURLNoQuery();
	
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::getSelfHostWithPath() instead.
	 */
	public static function getSelfHostWithPath() {
		return \SimpleSAML\Utils\HTTP::getSelfHostWithPath();
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::getFirstPathElement() instead.
	 */
	public static function getFirstPathElement($trailingslash = true) {
		return \SimpleSAML\Utils\HTTP::getFirstPathElement($trailingslash);
	}


    /**
     * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::getSelfURL() instead.
     */
	public static function selfURL() {
		return \SimpleSAML\Utils\HTTP::getSelfURL();
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::getBaseURL() instead.
	 */
	public static function getBaseURL() {
		return \SimpleSAML\Utils\HTTP::getBaseURL();
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::addURLParameters() instead.
	 */
	public static function addURLparameter($url, $parameters) {
		return \SimpleSAML\Utils\HTTP::addURLParameters($url, $parameters);
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use \SimpleSAML\Utils\HTTP::checkURLAllowed() instead.
	 */
	public static function checkURLAllowed($url, array $trustedSites = NULL) {
		return \SimpleSAML\Utils\HTTP::checkURLAllowed($url, $trustedSites);
	}


	/**
	 * Get the ID and (optionally) a URL embedded in a StateID,
	 * in the form 'id:url'.
	 *
	 * @param string $stateId The state ID to use.
	 * @return array A hashed array with the ID and the URL (if any),
	 * in the 'id' and 'url' keys, respectively. If there's no URL
	 * in the input parameter, NULL will be returned as the value for
	 * the 'url' key.
	 */
	public static function parseStateID($stateId) {
		$tmp = explode(':', $stateId, 2);
		$id = $tmp[0];
		$url = NULL;
		if (count($tmp) === 2) {
			$url = $tmp[1];
		}
		return array('id' => $id, 'url' => $url);
	}


	public static function checkDateConditions($start=NULL, $end=NULL) {
		$currentTime = time();
	
		if (!empty($start)) {
			$startTime = SAML2_Utils::xsDateTimeToTimestamp($start);
			/* Allow for a 10 minute difference in Time */
			if (($startTime < 0) || (($startTime - 600) > $currentTime)) {
				return FALSE;
			}
		}
		if (!empty($end)) {
			$endTime = SAML2_Utils::xsDateTimeToTimestamp($end);
			if (($endTime < 0) || ($endTime <= $currentTime)) {
				return FALSE;
			}
		}
		return TRUE;
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use SimpleSAML\Utils\Random::generateID() instead.
	 */
	public static function generateID() {
		return SimpleSAML\Utils\Random::generateID();
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use \SimpleSAML\Utils\Time::generateTimestamp() instead.
	 */
	public static function generateTimestamp($instant = NULL) {
		return SimpleSAML\Utils\Time::generateTimestamp($instant);
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use \SimpleSAML\Utils\Time::parseDuration() instead.
	 */
	public static function parseDuration($duration, $timestamp = NULL) {
		return SimpleSAML\Utils\Time::parseDuration($duration, $timestamp);
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please raise a SimpleSAML_Error_Error exception instead.
	 */
	public static function fatalError($trackId = 'na', $errorCode = null, Exception $e = null) {
		throw new SimpleSAML_Error_Error($errorCode, $e);
	}


	/**
	 * @deprecated This method will be removed in version 2.0. Use SimpleSAML\Utils\Net::ipCIDRcheck() instead.
	 */
	static function ipCIDRcheck($cidr, $ip = null) {
		return SimpleSAML\Utils\Net::ipCIDRcheck($cidr, $ip);
	}

	/*
	 * This is a temporary function, holding the redirect() functionality,
	 * meanwhile we are deprecating the it.
	 */
	private static function _doRedirect($url, $parameters = array()) {
		assert('is_string($url)');
		assert('!empty($url)');
		assert('is_array($parameters)');

		if (!empty($parameters)) {
			$url = self::addURLparameter($url, $parameters);
		}

		/* Set the HTTP result code. This is either 303 See Other or
		 * 302 Found. HTTP 303 See Other is sent if the HTTP version
		 * is HTTP/1.1 and the request type was a POST request.
		 */
		if ($_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1' &&
			$_SERVER['REQUEST_METHOD'] === 'POST') {
			$code = 303;
		} else {
			$code = 302;
		}

		if (strlen($url) > 2048) {
			SimpleSAML_Logger::warning('Redirecting to a URL longer than 2048 bytes.');
		}

		/* Set the location header. */
		header('Location: ' . $url, TRUE, $code);

		/* Disable caching of this response. */
		header('Pragma: no-cache');
		header('Cache-Control: no-cache, must-revalidate');

		/* Show a minimal web page with a clickable link to the URL. */
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"' .
			' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\n";
		echo '<html xmlns="http://www.w3.org/1999/xhtml">';
		echo '<head>
					<meta http-equiv="content-type" content="text/html; charset=utf-8">
					<title>Redirect</title>
				</head>';
		echo '<body>';
		echo '<h1>Redirect</h1>';
		echo '<p>';
		echo 'You were redirected to: ';
		echo '<a id="redirlink" href="' .
			htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a>';
		echo '<script type="text/javascript">document.getElementById("redirlink").focus();</script>';
		echo '</p>';
		echo '</body>';
		echo '</html>';

		/* End script execution. */
		exit;
	} 


	/**
	 * @deprecated 1.12.0 This function will be removed from the API. Instead, use the redirectTrustedURL or
	 * redirectUntrustedURL functions accordingly.
	 */
	public static function redirect($url, $parameters = array(), $allowed_redirect_hosts = NULL) {
		assert('is_string($url)');
		assert('strlen($url) > 0');
		assert('is_array($parameters)');

		if ($allowed_redirect_hosts !== NULL) {
			$url = self::checkURLAllowed($url, $allowed_redirect_hosts);
		} else {
			$url = self::normalizeURL($url);
		}
		self::_doRedirect($url, $parameters);
	}

	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::redirectTrustedURL() instead.
	 */
	public static function redirectTrustedURL($url, $parameters = array()) {
		\SimpleSAML\Utils\HTTP::redirectTrustedURL($url, $parameters);
	}

	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::redirectUntrustedURL() instead.
	 */
	public static function redirectUntrustedURL($url, $parameters = array()) {
		return \SimpleSAML\Utils\HTTP::redirectUntrustedURL($url, $parameters);
	}

	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\Arrays::transpose() instead.
	 */
	public static function transposeArray($in) {
		return SimpleSAML\Utils\Arrays::transpose($in);
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use SimpleSAML\Utils\XML::isDOMElementOfType() instead.
	 */
	public static function isDOMElementOfType(DOMNode $element, $name, $nsURI) {
		return SimpleSAML\Utils\XML::isDOMElementOfType($element, $name, $nsURI);
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use SimpleSAML\Utils\XML::getDOMChildren() instead.
	 */
	public static function getDOMChildren(DOMElement $element, $localName, $namespaceURI) {
		return SimpleSAML\Utils\XML::getDOMChildren($element, $localName, $namespaceURI);
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use SimpleSAML\Utils\XML::getDOMText() instead.
	 */
	public static function getDOMText($element) {
		return SimpleSAML\Utils\XML::getDOMText($element);
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::getAcceptLanguage() instead.
	 */
	public static function getAcceptLanguage() {
		return \SimpleSAML\Utils\HTTP::getAcceptLanguage();
	}


	/**
	 * This function attempts to validate an XML string against the specified schema.
	 *
	 * It will parse the string into a DOM document and validate this document against the schema.
	 *
	 * @param $xml     The XML string or document which should be validated.
	 * @param $schema  The schema which should be used.
	 * @return Returns a string with the errors if validation fails. An empty string is
	 *         returned if validation passes.
	 * @deprecated
	 */
	public static function validateXML($xml, $schema) {
		assert('is_string($xml) || $xml instanceof DOMDocument');
		assert('is_string($schema)');

		SimpleSAML_XML_Errors::begin();

		if($xml instanceof DOMDocument) {
			$dom = $xml;
			$res = TRUE;
		} else {
			$dom = new DOMDocument;
			$res = $dom->loadXML($xml);
		}

		if($res) {

			$config = SimpleSAML_Configuration::getInstance();
			$schemaPath = $config->resolvePath('schemas') . '/';
			$schemaFile = $schemaPath . $schema;

			$res = $dom->schemaValidate($schemaFile);
			if($res) {
				SimpleSAML_XML_Errors::end();
				return '';
			}

			$errorText = "Schema validation failed on XML string:\n";
		} else {
			$errorText = "Failed to parse XML string for schema validation:\n";
		}

		$errors = SimpleSAML_XML_Errors::end();
		$errorText .= SimpleSAML_XML_Errors::formatErrors($errors);

		return $errorText;
	}


	/**
	 * This function performs some sanity checks on XML documents, and optionally validates them
	 * against their schema. A warning will be printed to the log if validation fails.
	 *
	 * @param $message  The message which should be validated, as a string.
	 * @param $type     The type of document - can be either 'saml20', 'saml11' or 'saml-meta'.
	 * @deprecated
	 */
	public static function validateXMLDocument($message, $type) {
		assert('is_string($message)');
		assert($type === 'saml11' || $type === 'saml20' || $type === 'saml-meta');

		/* A SAML message should not contain a doctype-declaration. */
		if(strpos($message, '<!DOCTYPE') !== FALSE) {
			throw new Exception('XML contained a doctype declaration.');
		}

		$enabled = SimpleSAML_Configuration::getInstance()->getBoolean('debug.validatexml', NULL);
		if($enabled === NULL) {
			/* Fall back to old configuration option. */
			$enabled = SimpleSAML_Configuration::getInstance()->getBoolean('debug.validatesamlmessages', NULL);
			if($enabled === NULL) {
				/* Fall back to even older configuration option. */
				$enabled = SimpleSAML_Configuration::getInstance()->getBoolean('debug.validatesaml2messages', FALSE);
			}
		}

		if(!$enabled) {
			return;
		}

		switch($type) {
		case 'saml11':
			$result = self::validateXML($message, 'oasis-sstc-saml-schema-protocol-1.1.xsd');
			break;
		case 'saml20':
			$result = self::validateXML($message, 'saml-schema-protocol-2.0.xsd');
			break;
		case 'saml-meta':
			$result = self::validateXML($message, 'saml-schema-metadata-2.0.xsd');
			break;
		default:
			throw new Exception('Invalid message type.');
		}

		if($result !== '') {
			SimpleSAML_Logger::warning($result);
		}
	}


	/**
     * @deprecated This function will be removed in SSP 2.0. Please use openssl_random_pseudo_bytes() instead.
	 */
	public static function generateRandomBytes($length) {
		assert('is_int($length)');

		return openssl_random_pseudo_bytes($length);
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use bin2hex() instead.
	 */
	public static function stringToHex($bytes) {
		$ret = '';
		for($i = 0; $i < strlen($bytes); $i++) {
			$ret .= sprintf('%02x', ord($bytes[$i]));
		}
		return $ret;
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\System::resolvePath() instead.
	 */
	public static function resolvePath($path, $base = NULL) {
		return \SimpleSAML\Utils\System::resolvePath($path, $base);
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::resolveURL() instead.
	 */
	public static function resolveURL($url, $base = NULL) {
		return \SimpleSAML\Utils\HTTP::resolveURL($url, $base);
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::normalizeURL() instead.
	 */
	public static function normalizeURL($url) {
		return \SimpleSAML\Utils\HTTP::normalizeURL($url);
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::parseQueryString() instead.
	 */
	public static function parseQueryString($query_string) {
		return \SimpleSAML\Utils\HTTP::parseQueryString($query_string);
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use
	 * SimpleSAML\Utils\Arrays::normalizeAttributesArray() instead.
	 */
	public static function parseAttributes($attributes) {
		return SimpleSAML\Utils\Arrays::normalizeAttributesArray($attributes);
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use SimpleSAML\Utils\Config::getSecretSalt() instead.
	 */
	public static function getSecretSalt() {
		return SimpleSAML\Utils\Config::getSecretSalt();
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please call error_get_last() directly.
	 */
	public static function getLastError() {

		if (!function_exists('error_get_last')) {
			return '[Cannot get error message]';
		}

		$error = error_get_last();
		if ($error === NULL) {
			return '[No error message found]';
		}

		return $error['message'];
	}


	/**
	 * Resolves a path that may be relative to the cert-directory.
	 *
	 * @param string $path  The (possibly relative) path to the file.
	 * @return string  The file path.
	 */
	public static function resolveCert($path) {
		assert('is_string($path)');

		$globalConfig = SimpleSAML_Configuration::getInstance();
		$base = $globalConfig->getPathValue('certdir', 'cert/');
		return \SimpleSAML\Utils\System::resolvePath($path, $base);
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use SimpleSAML\Utils\Crypto::loadPublicKey() instead.
	 */
	public static function loadPublicKey(SimpleSAML_Configuration $metadata, $required = FALSE, $prefix = '') {
		return SimpleSAML\Utils\Crypto::loadPublicKey($metadata, $required, $prefix);
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use SimpleSAML\Utils\Crypto::loadPrivateKey() instead.
	 */
	public static function loadPrivateKey(SimpleSAML_Configuration $metadata, $required = FALSE, $prefix = '') {
		return SimpleSAML\Utils\Crypto::loadPrivateKey($metadata, $required, $prefix);
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use SimpleSAML\Utils\XML::formatDOMElement() instead.
	 */
	public static function formatDOMElement(DOMElement $root, $indentBase = '') {
		return SimpleSAML\Utils\XML::formatDOMElement($root, $indentBase);
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use SimpleSAML\Utils\XML::formatXMLString() instead.
	 */
	public static function formatXMLString($xml, $indentBase = '') {
		return SimpleSAML\Utils\XML::formatXMLString($xml, $indentBase);
	}

	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use SimpleSAML\Utils\Arrays::arrayize() instead.
	 */
	public static function arrayize($data, $index = 0) {
		return SimpleSAML\Utils\Arrays::arrayize($data, $index);
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use SimpleSAML\Utils\Auth::isAdmin() instead.
	 */
	public static function isAdmin() {
		return SimpleSAML\Utils\Auth::isAdmin();
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use SimpleSAML\Utils\Auth::getAdminLoginURL instead();
	 */
	public static function getAdminLoginURL($returnTo = NULL) {
		return SimpleSAML\Utils\Auth::getAdminLoginURL($returnTo);
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use SimpleSAML\Utils\Auth::requireAdmin() instead.
	 */
	public static function requireAdmin() {
		\SimpleSAML\Utils\Auth::requireAdmin();
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::submitPOSTData() instead.
	 */
	public static function postRedirect($destination, $post) {
		\SimpleSAML\Utils\HTTP::submitPOSTData($destination, $post);
	}

	/**
	 * @deprecated This method will be removed in SSP 2.0. PLease use SimpleSAML\Utils\HTTP::getPOSTRedirectURL() instead.
	 */
	public static function createPostRedirectLink($destination, $post) {
		return \SimpleSAML\Utils\HTTP::getPOSTRedirectURL($destination, $post);
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::getPOSTRedirectURL() instead.
	 */
	public static function createHttpPostRedirectLink($destination, $post) {
		assert('is_string($destination)');
		assert('is_array($post)');

		$postId = SimpleSAML\Utils\Random::generateID();
		$postData = array(
			'post' => $post,
			'url' => $destination,
		);

		$session = SimpleSAML_Session::getSessionFromRequest();
		$session->setData('core_postdatalink', $postId, $postData);

		$redirInfo = base64_encode(SimpleSAML\Utils\Crypto::aesEncrypt($session->getSessionId() . ':' . $postId));

		$url = SimpleSAML_Module::getModuleURL('core/postredirect.php', array('RedirInfo' => $redirInfo));
		$url = preg_replace("#^https:#", "http:", $url);

		return $url;
	}


	/**
	 * Validate a certificate against a CA file, by using the builtin
	 * openssl_x509_checkpurpose function
	 *
	 * @param string $certificate  The certificate, in PEM format.
	 * @param string $caFile  File with trusted certificates, in PEM-format.
	 * @return boolean|string TRUE on success, or a string with error messages if it failed.
	 * @deprecated
	 */
	private static function validateCABuiltIn($certificate, $caFile) {
		assert('is_string($certificate)');
		assert('is_string($caFile)');

		/* Clear openssl errors. */
		while(openssl_error_string() !== FALSE);

		$res = openssl_x509_checkpurpose($certificate, X509_PURPOSE_ANY, array($caFile));

		$errors = '';
		/* Log errors. */
		while( ($error = openssl_error_string()) !== FALSE) {
			$errors .= ' [' . $error . ']';
		}

		if($res !== TRUE) {
			return $errors;
		}

		return TRUE;
	}


	/**
	 * Validate the certificate used to sign the XML against a CA file, by using the "openssl verify" command.
	 *
	 * This function uses the openssl verify command to verify a certificate, to work around limitations
	 * on the openssl_x509_checkpurpose function. That function will not work on certificates without a purpose
	 * set.
	 *
	 * @param string $certificate  The certificate, in PEM format.
	 * @param string $caFile  File with trusted certificates, in PEM-format.
	 * @return boolean|string TRUE on success, a string with error messages on failure.
	 * @deprecated
	 */
	private static function validateCAExec($certificate, $caFile) {
		assert('is_string($certificate)');
		assert('is_string($caFile)');

		$command = array(
			'openssl', 'verify',
			'-CAfile', $caFile,
			'-purpose', 'any',
			);

		$cmdline = '';
		foreach($command as $c) {
			$cmdline .= escapeshellarg($c) . ' ';
		}

		$cmdline .= '2>&1';
		$descSpec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			);
		$process = proc_open($cmdline, $descSpec, $pipes);
		if (!is_resource($process)) {
			throw new Exception('Failed to execute verification command: ' . $cmdline);
		}

		if (fwrite($pipes[0], $certificate) === FALSE) {
			throw new Exception('Failed to write certificate for verification.');
		}
		fclose($pipes[0]);

		$out = '';
		while (!feof($pipes[1])) {
			$line = trim(fgets($pipes[1]));
			if(strlen($line) > 0) {
				$out .= ' [' . $line . ']';
			}
		}
		fclose($pipes[1]);

		$status = proc_close($process);
		if ($status !== 0 || $out !== ' [stdin: OK]') {
			return $out;
		}

		return TRUE;
	}


	/**
	 * Validate the certificate used to sign the XML against a CA file.
	 *
	 * This function throws an exception if unable to validate against the given CA file.
	 *
	 * @param string $certificate  The certificate, in PEM format.
	 * @param string $caFile  File with trusted certificates, in PEM-format.
	 * @deprecated
	 */
	public static function validateCA($certificate, $caFile) {
		assert('is_string($certificate)');
		assert('is_string($caFile)');

		if (!file_exists($caFile)) {
			throw new Exception('Could not load CA file: ' . $caFile);
		}

		SimpleSAML_Logger::debug('Validating certificate against CA file: ' . var_export($caFile, TRUE));

		$resBuiltin = self::validateCABuiltIn($certificate, $caFile);
		if ($resBuiltin !== TRUE) {
			SimpleSAML_Logger::debug('Failed to validate with internal function: ' . var_export($resBuiltin, TRUE));

			$resExternal = self::validateCAExec($certificate, $caFile);
			if ($resExternal !== TRUE) {
				SimpleSAML_Logger::debug('Failed to validate with external function: ' . var_export($resExternal, TRUE));
				throw new Exception('Could not verify certificate against CA file "'
					. $caFile . '". Internal result:' . $resBuiltin .
					' External result:' . $resExternal);
			}
		}

		SimpleSAML_Logger::debug('Successfully validated certificate.');
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use SimpleSAML\Utils\Time::initTimezone() instead.
	 */
	public static function initTimezone() {
		\SimpleSAML\Utils\Time::initTimezone();
	}

	/**
	 * Disable the loading of external entities in XML documents to prevent local and
	 * remote file inclusion attacks. This is in most cases already disabled by default
	 * in system libraries, but to be safe we explicitly disable it also.
     * @deprecated This function will be removed in SSP 2.0. Please use libxml_disable_entity_loader() instead.
	 */
	public static function disableXMLEntityLoader() {
		/* Function only present in PHP >= 5.2.11 while we support 5.2+ */
		if ( function_exists('libxml_disable_entity_loader') ) {
			libxml_disable_entity_loader();
		}
	}

	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\System::writeFile() instead.
	 */
	public static function writeFile($filename, $data, $mode=0600) {
		\SimpleSAML\Utils\System::writeFile($filename, $data, $mode);
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\System::getTempDir instead.
	 */
	public static function getTempDir() {
		return SimpleSAML\Utils\System::getTempDir();
	}


	/**
	 * Disable reporting of the given log levels.
	 *
	 * Every call to this function must be followed by a call to popErrorMask();
	 *
	 * @param int $mask  The log levels that should be masked.
	 */
	public static function maskErrors($mask) {
		assert('is_int($mask)');

		$currentEnabled = error_reporting();
		self::$logLevelStack[] = array($currentEnabled, self::$logMask);

		$currentEnabled &= ~$mask;
		error_reporting($currentEnabled);
		self::$logMask |= $mask;
	}


	/**
	 * Pop an error mask.
	 *
	 * This function restores the previous error mask.
	 */
	public static function popErrorMask() {

		$lastMask = array_pop(self::$logLevelStack);
		error_reporting($lastMask[0]);
		self::$logMask = $lastMask[1];
	}


	/**
	 * Find the default endpoint in an endpoint array.
	 *
	 * @param array $endpoints  Array with endpoints.
	 * @param array $bindings  Array with acceptable bindings. Can be NULL if any binding is allowed.
	 * @return  array|NULL  The default endpoint, or NULL if no acceptable endpoints are used.
	 */
	public static function getDefaultEndpoint(array $endpoints, array $bindings = NULL) {

		$firstNotFalse = NULL;
		$firstAllowed = NULL;

		/* Look through the endpoint list for acceptable endpoints. */
		foreach ($endpoints as $i => $ep) {
			if ($bindings !== NULL && !in_array($ep['Binding'], $bindings, TRUE)) {
				/* Unsupported binding. Skip it. */
				continue;
			}

			if (array_key_exists('isDefault', $ep)) {
				if ($ep['isDefault'] === TRUE) {
					/* This is the first endpoitn with isDefault set to TRUE. */
					return $ep;
				}
				/* isDefault is set to FALSE, but the endpoint is still useable as a last resort. */
				if ($firstAllowed === NULL) {
					/* This is the first endpoint that we can use. */
					$firstAllowed = $ep;
				}
			} else {
				if ($firstNotFalse === NULL) {
					/* This is the first endpoint without isDefault set. */
					$firstNotFalse = $ep;
				}
			}
		}

		if ($firstNotFalse !== NULL) {
			/* We have an endpoint without isDefault set to FALSE. */
			return $firstNotFalse;
		}

		/*
		 * $firstAllowed either contains the first endpoint we can use, or it
		 * contains NULL if we cannot use any of the endpoints. Either way we
		 * return the value of it.
		 */
		return $firstAllowed;
	}


	/**
	 * Check for session cookie, and show missing-cookie page if it is missing.
	 *
	 * @param string|NULL $retryURL  The URL the user should access to retry the operation.
	 */
	public static function checkCookie($retryURL = NULL) {
		assert('is_string($retryURL) || is_null($retryURL)');

		$session = SimpleSAML_Session::getSessionFromRequest();
		if ($session->hasSessionCookie()) {
			return;
		}

		/* We didn't have a session cookie. Redirect to the no-cookie page. */

		$url = SimpleSAML_Module::getModuleURL('core/no_cookie.php');
		if ($retryURL !== NULL) {
			$url = self::addURLParameter($url, array('retryURL' => $retryURL));
		}
		self::redirectTrustedURL($url);
	}


	/**
	 * Helper function to log messages that we send or receive.
	 *
	 * @param string|DOMElement $message  The message, as an XML string or an XML element.
	 * @param string $type  Whether this message is sent or received, encrypted or decrypted.
	 */
	public static function debugMessage($message, $type) {
		assert('is_string($message) || $message instanceof DOMElement');

		$globalConfig = SimpleSAML_Configuration::getInstance();
		if (!$globalConfig->getBoolean('debug', FALSE)) {
			/* Message debug disabled. */
			return;
		}

		if ($message instanceof DOMElement) {
			$message = $message->ownerDocument->saveXML($message);
		}

		switch ($type) {
		case 'in':
			SimpleSAML_Logger::debug('Received message:');
			break;
		case 'out':
			SimpleSAML_Logger::debug('Sending message:');
			break;
		case 'decrypt':
			SimpleSAML_Logger::debug('Decrypted message:');
			break;
		case 'encrypt':
			SimpleSAML_Logger::debug('Encrypted message:');
			break;
		default:
			assert(FALSE);
		}

		$str = SimpleSAML\Utils\XML::formatXMLString($message);
		foreach (explode("\n", $str) as $line) {
			SimpleSAML_Logger::debug($line);
		}
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::fetch() instead.
	 */
	public static function fetch($path, $context = array(), $getHeaders = FALSE) {
		return \SimpleSAML\Utils\HTTP::fetch($path, $context, $getHeaders);
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use SimpleSAML\Utils\Crypto::aesEncrypt() instead.
	 */
	public static function aesEncrypt($clear) {
		return SimpleSAML\Utils\Crypto::aesEncrypt($clear);
	}


	/**
	 * @deprecated This function will be removed in SSP 2.0. Please use SimpleSAML\Utils\Crypto::aesDecrypt() instead.
	 */
	public static function aesDecrypt($encData) {
		return SimpleSAML\Utils\Crypto::aesDecrypt($encData);
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\System::getOS() instead.
	 */
	public static function isWindowsOS() {
		return SimpleSAML\Utils\System::getOS() === SimpleSAML\Utils\System::WINDOWS;
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML\Utils\HTTP::setCookie() instead.
	 */
	public static function setCookie($name, $value, array $params = NULL, $throw = TRUE) {
		return \SimpleSAML\Utils\HTTP::setCookie($name, $value, $params, $throw);
	}

}
