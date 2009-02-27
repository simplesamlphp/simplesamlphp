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
	 * Warning: Will return Success if hostname is illegal. should be fixed.
	 *
	 * @param $host Hostname
	 * @param $port Port number (TCP)
	 */
	public function phpping($host, $port) {
	
		$this->log('ldapstatus phpping(): ping [' . $host . ':' . $port . ']' );
	
		$timeout = 1.0;
		$socket = @fsockopen($host, $port, &$errno, $errstr, $timeout);
		if ($socket) @fclose($socket);
		if ($errno) {
			return array(FALSE, $errno . ':' . $errstr . ' [' . $host . ':' . $port . ']');
		} else {		
			return array(TRUE,'');
		}
	}
	

	
}