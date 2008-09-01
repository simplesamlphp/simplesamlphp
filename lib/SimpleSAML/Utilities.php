<?php

/**
 * Misc static functions that is used several places.in example parsing and id generation.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Utilities {


	/**
	 * Will return sp.example.org
	 */
	public static function getSelfHost() {
	
		$currenthost = $_SERVER['HTTP_HOST'];
		if(strstr($currenthost, ":")) {
				$currenthostdecomposed = explode(":", $currenthost);
				$currenthost = $currenthostdecomposed[0];
		}
		return $currenthost;# . self::getFirstPathElement() ;
	}

	/**
	 * Will return https
	 */
	public static function getSelfProtocol() {
		$s = empty($_SERVER["HTTPS"]) ? ''
			: ($_SERVER["HTTPS"] == "on") ? "s"
			: "";
		$protocol = self::strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
		return $protocol;
	}

	/**
	 * Will return https://sp.example.org
	 */
	public static function selfURLhost() {
	
		$currenthost = self::getSelfHost();
	
		$protocol = self::getSelfProtocol();
		
		$portnumber = $_SERVER["SERVER_PORT"];
		$port = ':' . $portnumber;
		if ($protocol == 'http') {
			if ($portnumber == '80') $port = '';
		} elseif ($protocol == 'https') {
			if ($portnumber == '443') $port = '';
		}
			
		$querystring = '';
		return $protocol."://" . $currenthost . $port;
	
	}
	
	/**
	 * This function checks if we should set a secure cookie.
	 *
	 * @return TRUE if the cookie should be secure, FALSE otherwise.
	 */
	public static function isHTTPS() {

		if(!array_key_exists('HTTPS', $_SERVER)) {
			/* Not a https-request. */
			return FALSE;
		}

		if($_SERVER['HTTPS'] === 'off') {
			/* IIS with HTTPS off. */
			return FALSE;
		}

		/* Otherwise, HTTPS will be a non-empty string. */
		return $_SERVER['HTTPS'] !== '';
	}
	
	/**
	 * Will return https://sp.example.org/universities/ruc/baz/simplesaml/saml2/SSOService.php
	 */
	public static function selfURLNoQuery() {
	
		$selfURLhost = self::selfURLhost();
		return $selfURLhost . self::getScriptName();
	
	}
	
	public static function getScriptName() {
		$scriptname = $_SERVER['SCRIPT_NAME'];
		if (preg_match('|^/.*?(/.*)$|', $_SERVER['SCRIPT_NAME'], $matches)) {
			#$scriptname = $matches[1];
		}
		return $scriptname;
	}
	
	
	/**
	 * Will return sp.example.org/foo
	 */
	public static function getSelfHostWithPath() {
	
		$selfhostwithpath = self::getSelfHost();
		if (preg_match('|^(/.*?)/|', $_SERVER['SCRIPT_NAME'], $matches)) {
			$selfhostwithpath .= $matches[1];
		}
		return $selfhostwithpath;
	
	}
	
	/**
	 * Will return foo
	 */
	public static function getFirstPathElement($trailingslash = true) {
	
		if (preg_match('|^/(.*?)/|', $_SERVER['SCRIPT_NAME'], $matches)) {
			return ($trailingslash ? '/' : '') . $matches[1];
		}
		return '';
	}
	

	public static function selfURL() {
		$selfURLhost = self::selfURLhost();
		return $selfURLhost . self::getRequestURI();	
	}
	
	public static function getRequestURI() {
		
		$requesturi = $_SERVER['REQUEST_URI'];
		if (preg_match('|^/.*?(/.*)$|', $_SERVER['REQUEST_URI'], $matches)) {
		#$requesturi = $matches[1];
		}
		return $requesturi;
	}


	/**
	 * Add one or more query parameters to the given URL.
	 *
	 * @param $url  The URL the query parameters should be added to.
	 * @param $parameter  The query parameters which should be added to the url. This should be
	 *                    an associative array. For backwards comaptibility, it can also be a
	 *                    query string representing the new parameters. This will write a warning
	 *                    to the log.
	 * @return The URL with the new query parameters.
	 */
	public static function addURLparameter($url, $parameter) {

		/* For backwards compatibility - allow $parameter to be a string. */
		if(is_string($parameter)) {
			/* Print warning to log. */
			$backtrace = debug_backtrace();
			$where = $backtrace[0]['file'] . ':' . $backtrace[0]['line'];
			SimpleSAML_Logger::warning(
				'Deprecated use of SimpleSAML_Utilities::addURLparameter at ' .	$where .
				'. The parameter "$parameter" should now be an array, but a string was passed.');

			$parameter = self::parseQueryString($parameter);
		}
		assert('is_array($parameter)');

		$queryStart = strpos($url, '?');
		if($queryStart === FALSE) {
			$oldQuery = array();
			$url .= '?';
		} else {
			$oldQuery = substr($url, $queryStart + 1);
			if($oldQuery === FALSE) {
				$oldQuery = array();
			} else {
				$oldQuery = self::parseQueryString($oldQuery);
			}
			$url = substr($url, 0, $queryStart + 1);
		}

		$query = array_merge($oldQuery, $parameter);
		$url .= http_build_query($query);

		return $url;
	}


	public static function strleft($s1, $s2) {
		return substr($s1, 0, strpos($s1, $s2));
	}
	
	public static function checkDateConditions($start=NULL, $end=NULL) {
		$currentTime = time();
	
		if (! empty($start)) {
			$startTime = self::parseSAML2Time($start);
			/* Allow for a 10 minute difference in Time */
			if (($startTime < 0) || (($startTime - 600) > $currentTime)) {
				return FALSE;
			}
		}
		if (! empty($end)) {
			$endTime = self::parseSAML2Time($end);
			if (($endTime < 0) || ($endTime <= $currentTime)) {
				return FALSE;
			}
		}
		return TRUE;
	}
	
	public static function cert_fingerprint($pem) {
		$x509data = base64_decode( $pem );
		return strtolower( sha1( $x509data ) );
	}
	
	public static function generateID() {
		return '_' . self::stringToHex(self::generateRandomBytes(21));
	}
	

	/**
	 * This function generates a timestamp on the form used by the SAML protocols.
	 *
	 * @param $instant  The time the timestamp should represent.
	 * @return The timestamp.
	 */
	public static function generateTimestamp($instant = NULL) {
		if($instant === NULL) {
			$instant = time();
		}
		return gmdate('Y-m-d\TH:i:s\Z', $instant);
	}
	
	public static function generateTrackID() {		
		$uniqueid = substr(md5(uniqid(rand(), true)), 0, 10);
		return $uniqueid;
	}
	
	public static function array_values_equals($array, $equalsvalue) {
		$foundkeys = array();
		foreach ($array AS $key => $value) {
			if ($value === $equalsvalue) $foundkeys[] = $key;
		}
		return $foundkeys;
	}
	
	public static function checkAssocArrayRules($target, $required, $optional = array()) {

		$results = array(
			'required.found' 		=> array(),
			'required.notfound'		=> array(),
			'optional.found'		=> array(),
			'optional.notfound'		=> array(),
			'leftovers'				=> array()
		);
		
		foreach ($target AS $key => $value) {
			if(in_array($key, $required)) {
				$results['required.found'][$key] = $value;
			} elseif (in_array($key, $optional)) {
				$results['optional.found'][$key] = $value;
			} else {
				$results['leftovers'][$key] = $value;
			}
		}
		
		foreach ($required AS $key) {
			if (!array_key_exists($key, $target)) {
				$results['required.notfound'][] = $key;
			}
		}
		
		foreach ($optional AS $key) {
			if (!array_key_exists($key, $target)) {
				$results['optional.notfound'][] = $key;
			}
		}
		return $results;
	}
	

	/**
	 * Build a backtrace.
	 *
	 * This function takes in an exception and optionally a start depth, and
	 * builds a backtrace from that depth. The backtrace is returned as an
	 * array of strings, where each string represents one level in the stack.
	 *
	 * @param Exception $exception  The exception.
	 * @param int $startDepth  The depth we should print the backtrace from.
	 * @return array  The backtrace as an array of strings.
	 */
	public static function buildBacktrace(Exception $exception, $startDepth = 0) {

		assert('is_int($startDepth)');

		$bt = array();

		/* Position in the top function on the stack. */
		$pos = $exception->getFile() . ':' . $exception->getLine();

		foreach($exception->getTrace() as $t) {

			$function = $t['function'];
			if(array_key_exists('class', $t)) {
				$function = $t['class'] . '::' . $function;
			}

			$bt[] = $pos . ' (' . $function . ')';

			if(array_key_exists('file', $t)) {
				$pos = $t['file'] . ':' . $t['line'];
			} else {
				$pos = '[builtin]';
			}
		}

		$bt[] = $pos . ' (N/A)';

		/* Remove $startDepth elements from the top of the backtrace. */
		$bt = array_slice($bt, $startDepth);

		return $bt;
	}


	/**
	 * This function dumps a backtrace to the error log.
	 *
	 * The log is in the following form:
	 *  BT: (0) <filename>:<line> (<current function>)
	 *  BT: (1) <filename>:<line> (<previous fucntion>)
	 *  ...
	 *  BT: (N) <filename>:<line> (N/A)
	 *
	 * The log starts at the function which calls logBacktrace().
	 */
	public static function logBacktrace() {

		$e = new Exception();

		$bt = self::buildBackTrace($e, 1);
		foreach($bt as $depth => $t) {
			error_log('BT: (' . $depth . ') ' . $t);
		}
	}


	/* This function converts a SAML2 timestamp on the form
	 * yyyy-mm-ddThh:mm:ss(\.s+)?Z to a UNIX timestamp. The sub-second
	 * part is ignored.
	 *
	 * Andreas comments:
	 *  I got this timestamp from Shibboleth 1.3 IdP: 2008-01-17T11:28:03.577Z
	 *  Therefore I added to possibliity to have microseconds to the format.
	 * Added: (\.\\d{1,3})? to the regex.
	 *
	 *
	 * Parameters:
	 *  $time     The time we should convert.
	 *
	 * Returns:
	 *  $time converted to a unix timestamp.
	 */
	public static function parseSAML2Time($time) {
		$matches = array();


		/* We use a very strict regex to parse the timestamp. */
		if(preg_match('/^(\\d\\d\\d\\d)-(\\d\\d)-(\\d\\d)' .
		              'T(\\d\\d):(\\d\\d):(\\d\\d)(?:\\.\\d+)?Z$/D',
		              $time, $matches) == 0) {
			throw new Exception(
				'Invalid SAML2 timestamp passed to' .
				' parseSAML2Time: ' . $time);
		}

		/* Extract the different components of the time from the
		 * matches in the regex. intval will ignore leading zeroes
		 * in the string.
		 */
		$year = intval($matches[1]);
		$month = intval($matches[2]);
		$day = intval($matches[3]);
		$hour = intval($matches[4]);
		$minute = intval($matches[5]);
		$second = intval($matches[6]);

		/* We use gmmktime because the timestamp will always be given
		 * in UTC.
		 */
		$ts = gmmktime($hour, $minute, $second, $month, $day, $year);

		return $ts;
	}


	/** 
	 * Show and log fatal error message.
	 *
	 * This function logs a error message to the error log and shows the
	 * message to the user. Script execution terminates afterwards.
	 *
	 * The error code comes from the errors-dictionary. It can optionally include parameters, which
	 * will be substituted into the output string.
	 *
	 * @param string $trackid  The trackid of the user, from $session->getTrackID().
	 * @param mixed $errorcode  Either a string with the error code, or an array with the error code and
	 *                          additional parameters.
	 * @param Exception $e  The exception which caused the error.
	 */
	public static function fatalError($trackid = 'na', $errorcode = null, Exception $e = null) {
	
		$config = SimpleSAML_Configuration::getInstance();

		if(is_array($errorcode)) {
			$parameters = $errorcode;
			unset($parameters[0]);
			$errorcode = $errorcode[0];
		} else {
			$parameters = array();
		}

		// Get the exception message if there is any exception provided.
		$emsg   = (empty($e) ? 'No exception available' : $e->getMessage());
		$etrace = (empty($e) ? 'No exception available' : $e->getTraceAsString()); 

		if(!empty($errorcode) && count($parameters) > 0) {
			$reptext = array();
			foreach($parameters as $k => $v) {
				$reptext[] = '"' . $k . '"' . ' => "' . $v . '"';
			}
			$reptext = '(' . implode(', ', $reptext) . ')';
			$error = $errorcode . $reptext;
		} elseif(!empty($errorcode)) {
			$error = $errorcode;
		} else {
			$error = 'na';
		}

		// Log a error message
		SimpleSAML_Logger::error($_SERVER['PHP_SELF'].' - UserError: ErrCode:' . $error . ': ' . urlencode($emsg) );
		
		$languagefile = null;
		if (isset($errorcode)) $languagefile = 'errors';
		
		// Initialize a template
		$t = new SimpleSAML_XHTML_Template($config, 'error.php', $languagefile);
		
		
		$t->data['errorcode'] = $errorcode;
		$t->data['parameters'] = $parameters;

		$t->data['showerrors'] = $config->getValue('showerrors', true);

		/* Check if there is a valid technical contact email address. */
		if($config->getValue('technicalcontact_email', 'na@example.org') !== 'na@example.org') {
			/* Enable error reporting. */
			$baseurl = SimpleSAML_Utilities::selfURLhost() . '/' . $config->getBaseURL();
			$t->data['errorreportaddress'] = $baseurl . 'errorreport.php';

		} else {
			/* Disable error reporting. */
			$t->data['errorreportaddress'] = NULL;
		}

		$session = SimpleSAML_Session::getInstance();
		$attributes = $session->getAttributes();
		if(is_array($attributes) && array_key_exists('mail', $attributes) && count($attributes['mail']) > 0) {
			$email = $attributes['mail'][0];
		} else {
			$email = '';
		}
		$t->data['email'] = $email;

		$t->data['exceptionmsg'] = $emsg;
		$t->data['exceptiontrace'] = $etrace;
		
		$t->data['trackid'] = $trackid;
		
		$t->data['version'] = $config->getValue('version', 'na');
		$t->data['url'] = self::selfURLNoQuery();
		
		$t->show();
		
		exit;
	}
	
	/**
	 * Check whether an IP address is part of an CIDR.
	 */
	static function ipCIDRcheck($cidr, $ip = null) {
		if ($ip == null) $ip = $_SERVER['REMOTE_ADDR'];
		list ($net, $mask) = split ("/", $cidr);
		
		$ip_net = ip2long ($net);
		$ip_mask = ~((1 << (32 - $mask)) - 1);
		
		$ip_ip = ip2long ($ip);
		
		$ip_ip_net = $ip_ip & $ip_mask;
		
		return ($ip_ip_net == $ip_net);
	}


	/* This function redirects the user to the specified address.
	 * An optional set of query parameters can be appended by passing
	 * them in an array.
	 *
	 * This function will use the HTTP 303 See Other redirect if the
	 * current request is a POST request and the HTTP version is HTTP/1.1.
	 * Otherwise a HTTP 302 Found redirect will be used.
	 *
	 * The fuction will also generate a simple web page with a clickable
	 * link to the target page.
	 *
	 * Parameters:
	 *  $url         URL we should redirect to. This URL may include
	 *               query parameters. If this URL is a relative URL
	 *               (starting with '/'), then it will be turned into an
	 *               absolute URL by prefixing it with the absolute URL
	 *               to the root of the website.
	 *  $parameters  Array with extra query string parameters which should
	 *               be appended to the URL. The name of the parameter is
	 *               the array index. The value of the parameter is the
	 *               value stored in the index. Both the name and the value
	 *               will be urlencoded. If the value is NULL, then the
	 *               parameter will be encoded as just the name, without a
	 *               value.
	 *
	 * Returns:
	 *  This function never returns.
	 */
	public static function redirect($url, $parameters = array()) {
		assert(is_string($url));
		assert(strlen($url) > 0);
		assert(is_array($parameters));

		/* Check for relative URL. */
		if(substr($url, 0, 1) === '/') {
			/* Prefix the URL with the url to the root of the
			 * website.
			 */
			$url = self::selfURLhost() . $url;
		}

		/* Determine which prefix we should put before the first
		 * parameter.
		 */
		if(strpos($url, '?') === FALSE) {
			$paramPrefix = '?';
		} else {
			$paramPrefix = '&';
		}

		/* Iterate over the parameters and append them to the query
		 * string.
		 */
		foreach($parameters as $name => $value) {

			/* Encode the parameter. */
			if($value === NULL) {
				$param = urlencode($name);
			} else {
				$param = urlencode($name) . '=' .
					urlencode($value);
			}

			/* Append the parameter to the query string. */
			$url .= $paramPrefix . $param;

			/* Every following parameter is guaranteed to follow
			 * another parameter. Therefore we use the '&' prefix.
			 */
			$paramPrefix = '&';
		}


		/* Set the HTTP result code. This is either 303 See Other or
		 * 302 Found. HTTP 303 See Other is sent if the HTTP version
		 * is HTTP/1.1 and the request type was a POST request.
		 */
		if($_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1' &&
			$_SERVER['REQUEST_METHOD'] === 'POST') {
			$code = 303;
		} else {
			$code = 302;
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
		echo '<head><title>Redirect</title></head>';
		echo '<body>';
		echo '<h1>Redirect</h1>';
		echo '<p>';
		echo 'You were redirected to: ';
		echo '<a id="redirlink" href="' . htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a>';
		echo '<script type="text/javascript">document.getElementById("redirlink").focus();</script>';
		echo '</p>';
		echo '</body>';
		echo '</html>';

		/* End script execution. */
		exit;
	}


	/**
	 * This function transposes a two-dimensional array, so that
	 * $a['k1']['k2'] becomes $a['k2']['k1'].
	 *
	 * @param $in   Input two-dimensional array.
	 * @return      The transposed array.
	 */
	public static function transposeArray($in) {
		assert('is_array($in)');

		$ret = array();

		foreach($in as $k1 => $a2) {
			assert('is_array($a2)');

			foreach($a2 as $k2 => $v) {
				if(!array_key_exists($k2, $ret)) {
					$ret[$k2] = array();
				}

				$ret[$k2][$k1] = $v;
			}
		}

		return $ret;
	}


	/**
	 * This function checks if the DOMElement has the correct localName and namespaceURI.
	 *
	 * We also define the following shortcuts for namespaces:
	 * - '@ds':      'http://www.w3.org/2000/09/xmldsig#'
	 * - '@md':      'urn:oasis:names:tc:SAML:2.0:metadata'
	 * - '@saml1':   'urn:oasis:names:tc:SAML:1.0:assertion'
	 * - '@saml1md': 'urn:oasis:names:tc:SAML:profiles:v1metadata'
	 * - '@saml1p':  'urn:oasis:names:tc:SAML:1.0:protocol'
	 * - '@saml2':   'urn:oasis:names:tc:SAML:2.0:assertion'
	 * - '@saml2p':  'urn:oasis:names:tc:SAML:2.0:protocol'
	 *
	 * @param $element The element we should check.
	 * @param $name The localname the element should have.
	 * @param $nsURI The namespaceURI the element should have.
	 * @return TRUE if both namespace and localname matches, FALSE otherwise.
	 */
	public static function isDOMElementOfType($element, $name, $nsURI) {
		assert('$element instanceof DOMElement');
		assert('is_string($name)');
		assert('is_string($nsURI)');
		assert('strlen($nsURI) > 0');

		/* Check if the namespace is a shortcut, and expand it if it is. */
		if($nsURI[0] == '@') {

			/* The defined shortcuts. */
			$shortcuts = array(
				'@ds' => 'http://www.w3.org/2000/09/xmldsig#',
				'@md' => 'urn:oasis:names:tc:SAML:2.0:metadata',
				'@saml1' => 'urn:oasis:names:tc:SAML:1.0:assertion',
				'@saml1md' => 'urn:oasis:names:tc:SAML:profiles:v1metadata',
				'@saml1p' => 'urn:oasis:names:tc:SAML:1.0:protocol',
				'@saml2' => 'urn:oasis:names:tc:SAML:2.0:assertion',
				'@saml2p' => 'urn:oasis:names:tc:SAML:2.0:protocol',
				);

			/* Check if it is a valid shortcut. */
			if(!array_key_exists($nsURI, $shortcuts)) {
				throw new Exception('Unknown namespace shortcut: ' . $nsURI);
			}

			/* Expand the shortcut. */
			$nsURI = $shortcuts[$nsURI];
		}


		if($element->localName !== $name) {
			return FALSE;
		}

		if($element->namespaceURI !== $nsURI) {
			return FALSE;
		}

		return TRUE;
	}


	/**
	 * This function finds direct descendants of a DOM element with the specified
	 * localName and namespace. They are returned in an array.
	 *
	 * This function accepts the same shortcuts for namespaces as the isDOMElementOfType function.
	 *
	 * @param $element The element we should look in.
	 * @param $localName The name the element should have.
	 * @param $namespaceURI The namespace the element should have.
	 * @return Array with the matching elements in the order they are found. An empty array is
	 *         returned if no elements match.
	 */
	public static function getDOMChildren($element, $localName, $namespaceURI) {
		assert('$element instanceof DOMElement');

		$ret = array();

		for($i = 0; $i < $element->childNodes->length; $i++) {
			$child = $element->childNodes->item($i);

			/* Skip text nodes and comment elements. */
			if($child instanceof DOMText || $child instanceof DOMComment) {
				continue;
			}

			if(self::isDOMElementOfType($child, $localName, $namespaceURI) === TRUE) {
				$ret[] = $child;
			}
		}

		return $ret;
	}


	/**
	 * This function extracts the text from DOMElements which should contain
	 * only text content.
	 *
	 * @param $element The element we should extract text from.
	 * @return The text content of the element.
	 */
	public static function getDOMText($element) {
		assert('$element instanceof DOMElement');

		$txt = '';

		for($i = 0; $i < $element->childNodes->length; $i++) {
			$child = $element->childNodes->item($i);
			if(!($child instanceof DOMText)) {
				throw new Exception($element->localName . ' contained a non-text child node.');
			}

			$txt .= $child->wholeText;
		}

		$txt = trim($txt);
		return $txt;
	}


	/**
	 * This function parses the Accept-Language http header and returns an associative array with each
	 * language and the score for that language.
	 *
	 * If an language includes a region, then the result will include both the language with the region
	 * and the language without the region.
	 *
	 * The returned array will be in the same order as the input.
	 *
	 * @return An associative array with each language and the score for that language.
	 */
	public static function getAcceptLanguage() {

		if(!array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER)) {
			/* No Accept-Language header - return empty set. */
			return array();
		}

		$languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

		$ret = array();

		foreach($languages as $l) {
			$opts = explode(';', $l);

			$l = trim(array_shift($opts)); /* The language is the first element.*/

			$q = 1.0;

			/* Iterate over all options, and check for the quality option. */
			foreach($opts as $o) {
				$o = explode('=', $o);
				if(count($o) < 2) {
					/* Skip option with no value. */
					continue;
				}

				$name = trim($o[0]);
				$value = trim($o[1]);

				if($name === 'q') {
					$q = (float)$value;
				}
			}

			/* Remove the old key to ensure that the element is added to the end. */
			unset($ret[$l]);

			/* Set the quality in the result. */
			$ret[$l] = $q;

			if(strpos($l, '-')) {
				/* The language includes a region part. */

				/* Extract the language without the region. */
				$l = explode('-', $l);
				$l = $l[0];

				/* Add this language to the result (unless it is defined already). */
				if(!array_key_exists($l, $ret)) {
					$ret[$l] = $q;
				}
			}
		}

		return $ret;
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
	 */
	public static function validateXMLDocument($message, $type) {
		assert('is_string($message)');
		assert($type === 'saml11' || $type === 'saml20' || $type === 'saml-meta');

		/* A SAML message should not contain a doctype-declaration. */
		if(strpos($message, '<!DOCTYPE') !== FALSE) {
			throw new Exception('XML contained a doctype declaration.');
		}

		$enabled = SimpleSAML_Configuration::getInstance()->getValue('debug.validatexml', NULL);
		if($enabled === NULL) {
			/* Fall back to old configuration option. */
			$enabled = SimpleSAML_Configuration::getInstance()->getValue('debug.validatesamlmessages', NULL);
			if($enabled === NULL) {
				/* Fall back to even older configuration option. */
				$enabled = SimpleSAML_Configuration::getInstance()->getValue('debug.validatesaml2messages', FALSE);
				if(!is_bool($enabled)) {
					throw new Exception('Expected "debug.validatesaml2messages" to be set to a boolean value.');
				}
			} elseif(!is_bool($enabled)) {
				throw new Exception('Expected "debug.validatexml" to be set to a boolean value.');
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
	 * This function is used to generate a non-revesible unique identifier for a user.
	 * The identifier should be persistent (unchanging) for a given SP-IdP federation.
	 * The identifier can be shared between several different SPs connected to the same IdP, or it
	 * can be unique for each SP.
	 *
	 * @param $idpEntityId  The entity id of the IdP.
	 * @param $spEntityId   The entity id of the SP.
	 * @param $attributes   The attributes of the user.
	 * @return A non-reversible unique identifier for the user.
	 */
	public static function generateUserIdentifier($idpEntityId, $spEntityId, $attributes) {
		$metadataHandler = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
		$idpMetadata = $metadataHandler->getMetaData($idpEntityId, 'saml20-idp-hosted');
		$spMetadata = $metadataHandler->getMetaData($spEntityId, 'saml20-sp-remote');

		if(array_key_exists('userid.attribute', $spMetadata)) {
			$attributeName = $spMetadata['userid.attribute'];
		} elseif(array_key_exists('userid.attribute', $idpMetadata)) {
			$attributeName = $idpMetadata['userid.attribute'];
		} else {
			$attributeName = 'eduPersonPrincipalName';
		}

		if(!array_key_exists($attributeName, $attributes)) {
			throw new Exception('Missing attribute "' . $attributeName . '" for user. Cannot' .
			                    ' generate user id.');
		}

		$attributeValue = $attributes[$attributeName];
		if(count($attributeValue) !== 1) {
			throw new Exception('Attribute "' . $attributeName . '" for user did not contain exactly' .
			                    ' one value. Cannot generate user id.');
		}

		$attributeValue = $attributeValue[0];
		if(empty($attributeValue)) {
			throw new Exception('Attribute "' . $attributeName . '" for user was empty. Cannot' .
			                    ' generate user id.');
		}


		$secretSalt = self::getSecretSalt();

		$uidData = 'uidhashbase' . $secretSalt;
		$uidData .= strlen($idpEntityId) . ':' . $idpEntityId;
		$uidData .= strlen($spEntityId) . ':' . $spEntityId;
		$uidData .= strlen($attributeValue) . ':' . $attributeValue;
		$uidData .= $secretSalt;

		$userid = hash('sha1', $uidData);

		return $userid;
	}

	public static function generateRandomBytesMTrand($length) {
	
		/* Use mt_rand to generate $length random bytes. */
		$data = '';
		for($i = 0; $i < $length; $i++) {
			$data .= chr(mt_rand(0, 255));
		}
	
	}


	/**
	 * This function generates a binary string containing random bytes.
	 *
	 * It will use /dev/urandom if available, and fall back to the builtin mt_rand()-function if not.
	 *
	 * @param $length  The number of random bytes to return.
	 * @return A string of lenght $length with random bytes.
	 */
	public static function generateRandomBytes($length, $fallback = TRUE) {
		static $fp = NULL;
		assert('is_int($length)');

		if($fp === NULL) {
			$fp = @fopen('/dev/urandom', 'rb');
		}

		if($fp !== FALSE) {
			/* Read random bytes from /dev/urandom. */
			$data = fread($fp, $length);
			if($data === FALSE) {
				throw new Exception('Error reading random data.');
			}
			if(strlen($data) != $length) {
				SimpleSAML_Logger::warning('Did not get requested number of bytes from random source. Requested (' . $length . ') got (' . strlen($data) . ')');
				if ($fallback) {
					$data = self::generateRandomBytesMTrand($length);
				} else {
					throw new Exception('Did not get requested number of bytes from random source. Requested (' . $length . ') got (' . strlen($data) . ')');
				}
			}
		} else {
			/* Use mt_rand to generate $length random bytes. */
			$data = self::generateRandomBytesMTrand($length);
		}

		return $data;
	}


	/**
	 * This function converts a binary string to hexadecimal characters.
	 *
	 * @param $bytes  Input string.
	 * @return String with lowercase hexadecimal characters.
	 */
	public static function stringToHex($bytes) {
		$ret = '';
		for($i = 0; $i < strlen($bytes); $i++) {
			$ret .= sprintf('%02x', ord($bytes[$i]));
		}
		return $ret;
	}


	/**
	 * Resolve a (possibly) relative path from the given base path.
	 *
	 * A path which starts with a '/' is assumed to be absolute, all others are assumed to be
	 * relative. The default base path is the root of the simpleSAMPphp installation.
	 *
	 * @param $path  The path we should resolve.
	 * @param $base  The base path, where we should search for $path from. Default value is the root
	 *               of the simpleSAMLphp installation.
	 * @return An absolute path referring to $path.
	 */
	public static function resolvePath($path, $base = NULL) {
		if($base === NULL) {
			$config = SimpleSAML_Configuration::getInstance();
			$base =  $config->getBaseDir();
		}

		/* Remove trailing slashes from $base. */
		while(substr($base, -1) === '/') {
			$base = substr($base, 0, -1);
		}

		/* Check for absolute path. */
		if(substr($path, 0, 1) === '/') {
			/* Absolute path. */
			$ret = '/';
		} else {
			/* Path relative to base. */
			$ret = $base;
		}

		$path = explode('/', $path);
		foreach($path as $d) {
			if($d === '.') {
				continue;
			} elseif($d === '..') {
				$ret = dirname($ret);
			} else {
				if(substr($ret, -1) !== '/') {
					$ret .= '/';
				}
				$ret .= $d;
			}
		}

		return $ret;
	}


	/**
	 * Resolve a (possibly) relative URL relative to a given base URL.
	 *
	 * This function supports these forms of relative URLs:
	 *  ^\w+: Absolute URL
	 *  ^// Same protocol.
	 *  ^/ Same protocol and host.
	 *  ^? Same protocol, host and path, replace query string & fragment
	 *  ^# Same protocol, host, path and query, replace fragment
	 *  The rest: Relative to the base path.
	 *
	 * @param $url  The relative URL.
	 * @param $base  The base URL. Defaults to the base URL of this installation of simpleSAMLphp.
	 * @return An absolute URL for the given relative URL.
	 */
	public static function resolveURL($url, $base = NULL) {
		if($base === NULL) {
			$config = SimpleSAML_Configuration::getInstance();
			$base = self::selfURLhost() . '/' . $config->getBaseURL();
		}


		if(!preg_match('$^((((\w+:)//[^/]+)(/[^?#]*))(?:\?[^#]*)?)(?:#.*)?$', $base, $baseParsed)) {
			throw new Exception('Unable to parse base url: ' . $base);
		}

		$baseDir = dirname($baseParsed[5] . 'filename');
		$baseScheme = $baseParsed[4];
		$baseHost = $baseParsed[3];
		$basePath = $baseParsed[2];
		$baseQuery = $baseParsed[1];

		if(preg_match('$^\w+:$', $url)) {
			return $url;
		}

		if(substr($url, 0, 2) === '//') {
			return $baseScheme . $url;
		}

		$firstChar = substr($url, 0, 1);

		if($firstChar === '/') {
			return $baseHost . $url;
		}

		if($firstChar === '?') {
			return $basePath . $url;
		}

		if($firstChar === '#') {
			return $baseQuery . $url;
		}


		/* We have a relative path. Remove query string/fragment and save it as $tail. */
		$queryPos = strpos($url, '?');
		$fragmentPos = strpos($url, '#');
		if($queryPos !== FALSE || $fragmentPos !== FALSE) {
			if($queryPos === FALSE) {
				$tailPos = $fragmentPos;
			} elseif($fragmentPos === FALSE) {
				$tailPos = $queryPos;
			} elseif($queryPos < $fragmentPos) {
				$tailPos = $queryPos;
			} else {
				$tailPos = $fragmentPos;
			}

			$tail = substr($url, $tailPos);
			$dir = substr($url, 0, $tailPos);
		} else {
			$dir = $url;
			$tail = '';
		}

		$dir = self::resolvePath($dir, $baseDir);

		return $baseHost . $dir . $tail;
	}


	/**
	 * Parse a query string into an array.
	 *
	 * This function parses a query string into an array, similar to the way the builtin
	 * 'parse_str' works, except it doesn't handle arrays, and it doesn't do "magic quotes".
	 *
	 * Query parameters without values will be set to an empty string.
	 *
	 * @param $query_string  The query string which should be parsed.
	 * @return The query string as an associative array.
	 */
	public static function parseQueryString($query_string) {
		assert('is_string($query_string)');

		$res = array();
		foreach(split('&', $query_string) as $param) {
			$param = split('=', $param);
			$name = urldecode($param[0]);
			if(count($param) === 1) {
				$value = '';
			} else {
				$value = urldecode($param[1]);
			}

			$res[$name] = $value;
		}

		return $res;
	}


	/**
	 * Parse and validate an array with attributes.
	 *
	 * This function takes in an associative array with attributes, and parses and validates
	 * this array. On success, it will return a normalized array, where each attribute name
	 * is an index to an array of one or more strings. On failure an exception will be thrown.
	 * This exception will contain an message describing what is wrong.
	 *
	 * @param array $attributes  The attributes we should parse and validate.
	 * @return array  The parsed attributes.
	 */
	public static function parseAttributes($attributes) {

		if (!is_array($attributes)) {
			throw new Exception('Attributes was not an array. Was: ' . var_export($attributes, TRUE));
		}

		$newAttrs = array();
		foreach ($attributes as $name => $values) {
			if (!is_string($name)) {
				throw new Exception('Invalid attribute name: ' . var_export($name, TRUE));
			}

			if (!is_array($values)) {
				$values = array($values);
			}

			foreach ($values as $value) {
				if (!is_string($value)) {
					throw new Exception('Invalid attribute value for attribute ' . $name .
						': ' . var_export($value, TRUE));
				}
			}

			$newAttrs[$name] = $values;
		}

		return $newAttrs;
	}


	/**
	 * Retrieve secret salt.
	 *
	 * This function retrieves the value which is configured as the secret salt. It will
	 * check that the value exists and is set to a non-default value. If it isn't, an
	 * exception will be thrown.
	 *
	 * The secret salt can be used as a component in hash functions, to make it difficult to
	 * test all possible values in order to retrieve the original value. It can also be used
	 * as a simple method for signing data, by hashing the data together with the salt.
	 *
	 * @return string  The secret salt.
	 */
	public static function getSecretSalt() {

		$secretSalt = SimpleSAML_Configuration::getInstance()->getString('secretsalt');
		if ($secretSalt === 'defaultsecretsalt') {
			throw new Exception('The "secretsalt" configuration option must be set to a secret' .
			                    ' value.');
		}

		return $secretSalt;
	}

}

?>