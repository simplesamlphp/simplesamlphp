<?php


/**
 * SimpleSAMLphp
 *
 * PHP versions 4 and 5
 *
 * LICENSE: See the COPYING file included in this distribution.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 */
 
require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/XHTML/Template.php');

/**
 * Configuration of SimpleSAMLphp
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


	public static function selfURLhost() {
	
		$currenthost = self::getSelfHost();
	
		$s = empty($_SERVER["HTTPS"]) ? ''
			: ($_SERVER["HTTPS"] == "on") ? "s"
			: "";
		$protocol = self::strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
		
		$port = ($_SERVER["SERVER_PORT"] == "80") ? ""
			: (":".$_SERVER["SERVER_PORT"]);
		
		
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
	 * yyyy-mm-ddThh:mm:ssZ to a UNIX timestamp.
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
		              'T(\\d\\d):(\\d\\d):(\\d\\d)Z$/D',
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


	/* This function logs a error message to the error log and shows the
	 * message to the user. Script execution terminates afterwards.
	 *
	 * Parameters:
	 *  $title       Short title for the error message.
	 *  $message     The error message.
	 */
	public static function fatalError($title, $message) {
		error_log($title . ': ' . $message);

		$config = SimpleSAML_Configuration::getInstance();
		$t = new SimpleSAML_XHTML_Template($config, 'error.php');
		$t->data['header'] = $title;
		$t->data['message'] = $message;
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
		echo '<html><body><h1>Redirect</h1>You were redirected to: <a href="' .
			htmlspecialchars($url) . '">' . htmlspecialchars($url)
			. '</a></body></html>';

		/* End script execution. */
		exit;
	}
}

?>