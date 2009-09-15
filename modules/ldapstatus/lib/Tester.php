<?php

/**
 * Test helper class.
 *
 * @author Andreas Åkre Solberg, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_ldapstatus_Tester {


	private $location;
	private $debugOutput;
	private $times;

	public function __construct($location, $debugOutput = FALSE) {
		$this->location = $location;
		$this->times = array();
		$this->debugOutput = $debugOutput;
	}
	
	/**
	 * Start timer
	 */
	public function tick($tag = 'default') {
		$this->times[$tag] = microtime(TRUE);
	}

	/**
	 * Stop timer
	 */	
	public function tack($tag = 'default', $text = TRUE) {
		if($text) 
			return $this->getTimeText(microtime(TRUE) - $this->times[$tag]);	
		return (microtime(TRUE) - $this->times[$tag]);	
	}
	
	/**
	 * Get duration as text.
	 */
	private function getTimeText($time) {
		return 'Operation took ' . ceil($time*1000) . ' ms';
	}
	
	public function checkConfig($req) {
		$err = array();
		foreach($req AS $r) {
			$rs = SimpleSAML_Utilities::arrayize($r);
			if (!$this->location->hasValueOneOf($rs)) {
				$err[] = 'one of (' . join(',', $rs) . ')';
			}
		}
		if (count($err) > 0) 
			return array(FALSE, 'Missing: ' . join(' | ', $err));
		return array(TRUE, '');	
	}
	
	
	private function checkParameter($conf, $req) {
		$res = $this->checkConfig($conf, array($req));
		if ($res[0]) {
			return 'Parameter [' . $req . '] found';
		} else {
			return 'Parameter [' . $req . '] NOT found';
		}
	}
	
	public function log($str) {
		if ($this->debugOutput) {
			echo '<p>' . $str;
		} else {
			SimpleSAML_Logger::debug($str);
		}
		flush();
	}
	
	
	/**
	 * TCP ping implemented in php.
	 *
	 * @param string $host Hostname
	 * @param int $port Port number (TCP)
	 */
	public function phpping($host, $port) {
		assert('is_string($host)');
		assert('is_int($port)');

		$this->log('ldapstatus phpping(): ping [' . $host . ':' . $port . ']' );

		$ips = gethostbynamel($host);
		if ($ips === FALSE) {
			return array(FALSE, 'Unable to look up hostname ' . $host . '.');
		}
		if (count($ips) === 0) {
			return array(FALSE, 'No IP address found for host ' . $host . '.');
		}

		$errors = array();
		foreach ($ips as $ip) {
			$timeout = 1.0;
			$socket = @fsockopen($ip, $port, $errno, $errstr, $timeout);
			if ($errno) {
				$errors[] = $errno . ':' . $errstr . ' (' . $host . '[' . $ip . ']:' . $port . ')';
			} elseif ($socket === FALSE) {
				$errors[] = '[Unknown error, check log] (' . $host . '[' . $ip . ']:' . $port . ')';
			} else {
				@fclose($socket);
			}
		}

		if (count($errors) === 0) {
			return array(TRUE, count($ips) . ' LDAP servers working.');
		}

		$error = count($errors) . ' of ' . count($ips) . ' failed: '. implode(';', $errors);
		return array(FALSE, $error);
	}
	

	
}