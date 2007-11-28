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
			$startTime = strtotime($start);
			/* Allow for a 10 minute difference in Time */
			if (($startTime < 0) || (($startTime - 600) > $currentTime)) {
				return FALSE;
			}
		}
		if (! empty($end)) {
			$endTime = strtotime($end);
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
}

?>