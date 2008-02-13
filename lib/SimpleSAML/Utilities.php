<?php

require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/XHTML/Template.php');
require_once('SimpleSAML/Logger.php');

/**
 * Misc static functions that is used several places.in example parsing and id generation.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Utilities {



	public static function getSelfHost() {
	
		$currenthost = $_SERVER['HTTP_HOST'];
		if(strstr($currenthost, ":")) {
				$currenthostdecomposed = explode(":", $currenthost);
				$currenthost = $currenthostdecomposed[0];
		}
		return $currenthost;
	}

	public static function getSelfProtocol() {
		$s = empty($_SERVER["HTTPS"]) ? ''
			: ($_SERVER["HTTPS"] == "on") ? "s"
			: "";
		$protocol = self::strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
		return $protocol;
	}

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
	


	public static function selfURLNoQuery() {
	
		$selfURLhost = self::selfURLhost();
		return $selfURLhost . $_SERVER['SCRIPT_NAME'];
	
	}

	public static function selfURL() {
		$selfURLhost = self::selfURLhost();
		return $selfURLhost . $_SERVER['REQUEST_URI'];	
	}
	
	public static function addURLparameter($url, $parameter) {
		if (strstr($url, '?')) {
			return $url . '&' . $parameter;
		} else {
			return $url . '?' . $parameter;
		}
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
	
		$length = 42;
		$key = "_";
		for ( $i=0; $i < $length; $i++ )
		{
			 $key .= dechex( rand(0,15) );
		}
		return $key;
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
	
	
	

	/* This function dumps a backtrace to the error log.
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

		/* Get the backtrace. */
		$bt = debug_backtrace();

		/* Variable to hold the stack depth. */
		$depth = 0;

		/* PHP stores the backtrace as a list of function calls.
		 * This means that $bt[0]['function'] contains the function
		 * which is called, while $bt[0]['line'] contains the line
		 * the function was called from.
		 *
		 * To get the form of bactrace we want, we are going to use
		 * $bt[i+1] to get the function and $bt[i] to get the file
		 * name and the line number.
		 */

		for($i = 0; $i < count($bt); $i++) {
			$file = $bt[$i]['file'];
			$line = $bt[$i]['line'];

			/* We can't get a function name or class for the source
			 * of the first call.
			 */
			if($i == count($bt) - 1) {
				$function = 'N/A';
				$class = NULL;
			} else {
				$function = $bt[$i+1]['function'];
				$class = $bt[$i+1]['class'];
			}

			/* Attach the class name to the function name if
			 * we have a class name.
			 */
			if($class !== NULL) {
				$function = $class  . '::' . $function;
			}


			error_log('BT: (' . $depth . ') ' . $file . ':' .
			          $line . ' (' . $function . ')');

			$depth++;
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
	 * This function logs a error message to the error log and shows the
	 * message to the user. Script execution terminates afterwards.
	 *
	 *  @param $title       Short title for the error message.
	 *  @param $message     The error message.
	 */
	public static function fatalError($trackid = 'na', $errorcode = null, Exception $e = null, $level = LOG_ERR) {
	
		$config = SimpleSAML_Configuration::getInstance();
		
		// Get the exception message if there is any exception provided.
		$emsg   = (empty($e) ? 'No exception available' : $e->getMessage());
		$etrace = (empty($e) ? 'No exception available' : $e->getTraceAsString()); 
		
		// Log a error message
		Logger::error($_SERVER['PHP_SELF'].' - UserError: ErrCode:'.(!empty($errorcode) ? $errorcode : 'na').': '.urlencode($emsg) );
		
		$languagefile = null;
		if (isset($errorcode)) $languagefile = 'error_' . $errorcode . '.php';
		
		// Initialize a template
		$t = new SimpleSAML_XHTML_Template($config, 'error.php', $languagefile);
		
		
		$t->data['showerrors'] = $config->getValue('showerrors', true);
		$t->data['errorreportaddress'] = $config->getValue('errorreportaddress', null); 
		
		$t->data['exceptionmsg'] = $emsg;
		$t->data['exceptiontrace'] = $etrace;
		
		$t->data['trackid'] = $trackid;
		
		$t->data['version'] = $config->getValue('version', 'na');
		$t->data['email'] = $config->getValue('technicalcontact_email', 'na');
		
		$t->show();
		
		exit;
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
}

?>