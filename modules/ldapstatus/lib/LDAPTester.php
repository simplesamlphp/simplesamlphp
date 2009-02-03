<?php

/**
 * Test LDAP connection...
 *
 * @author Andreas Åkre Solberg, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_ldapstatus_LDAPTester {


	private $orgconfig;
	private $debug;
	private $debugOutput;

	public function __construct($orgconfig, $debug, $output = FALSE) {
		$this->orgconfig = $orgconfig;
		$this->debug = $debug;
		$this->debugOutput = $output;
	}
		
	private function is_in_array($needles, $haystack) {
		$needles = SimpleSAML_Utilities::arrayize($needles);
		foreach($needles AS $needle) {
			if (array_key_exists($needle, $haystack) && !empty($haystack[$needle])) return TRUE;
		}
		return FALSE;
	}
	
	private function checkConfig($conf, $req) {
		$err = array();
		foreach($req AS $r) {
			
			if (!$this->is_in_array($r, $conf)) {
				$err[] = 'missing or empty: ' . join(', ', SimpleSAML_Utilities::arrayize($r));
			}
		}
		if (count($err) > 0) {
			return array(FALSE, 'Missing: ' . join(', ', $err));
		}
		return array(TRUE, NULL);	
	}
	
	
	private function log($str) {
		if ($this->debugOutput) {
			echo '<p>' . $str;
		} else {
			SimpleSAML_Logger::debug('ldapstatus phpping(): ping [' . $host . ':' . $port . ']' );
		}
	}
	
	private function phpping($host, $port) {
	
		$this->log('ldapstatus phpping(): ping [' . $host . ':' . $port . ']' );
	
		$timeout = 1.0;
		$socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
		@fclose($socket);
		if ($errno) {
			return array(FALSE, $errno . ':' . $errstr . ' [' . $host . ':' . $port . ']');
		} else {		
			return array(TRUE,NULL);
		}
	}
	
	public function test() {
		$start = microtime(TRUE);
		
		$result = array();
		
		$this->log('Testing config');
		$result['config'] = $this->checkConfig($this->orgconfig, array('description', 'searchbase', 'hostname'));

		$this->log('Testing config meta');
		$result['configMeta'] = $this->checkConfig($this->orgconfig, array(array('contactMail', 'contactURL')));

		$this->log('Testing config testuser');
		$result['configTest'] = $this->checkConfig($this->orgconfig, array('testUser', 'testPassword'));
	
		if (!$result['config'][0]) {
			$this->log('Skipping because of no configuration');
			$result['time'] = microtime(TRUE) - $start;
			return $result;
		}
	
		$urldef = explode(' ', $this->orgconfig['hostname']);
		$url = parse_url($urldef[0]);
		$port = 389;
		if (!empty($url['scheme']) && $url['scheme'] === 'ldaps') $port = 636;
		if (!empty($url['port'])) $port = $url['port'];
		
		$this->log('ldapstatus Url parse [' . $this->orgconfig['hostname'] . '] => [' . $url['host'] . ']:[' . $port . ']' );
	
	
		$result['ping'] = $this->phpping($url['host'], $port);
	
		if (!$result['ping'][0]) {
			$result['time'] = microtime(TRUE) - $start;
			$this->log('Skipping because of no ping');
			return $result;
		}
		
		// LDAP Connect
		try {
			$ldap = new SimpleSAML_Auth_LDAP($this->orgconfig['hostname'], 
				(array_key_exists('enable_tls', $this->orgconfig) ? $this->orgconfig['enable_tls'] : FALSE), 
				$this->debug);
			
			if ($ldap->getLastError()) throw new Exception('LDAP warning: ' . $ldap->getLastError());
			$result['connect'] = array(TRUE,NULL);
		} catch (Exception $e) {
			$this->log('ldapstatus: Connect error() [' .$orgkey . ']: ' . $e->getMessage());
			$result['connect'] = array(FALSE,$e->getMessage());
			$result['time'] = microtime(TRUE) - $start;
			return $result;
		}
	
		// Bind as admin user
		if (isset($this->orgconfig['adminUser'])) {
			try {
				$this->log('ldapstatus: Admin bind() [' .$orgkey . ']');
				$success = $ldap->bind($this->orgconfig['adminUser'], $this->orgconfig['adminPassword']);
				if ($ldap->getLastError()) throw new Exception('LDAP warning: ' . $ldap->getLastError());
				if ($success) {
					$result['adminBind'] = array(TRUE,NULL);
				} else {
					$result['adminBind'] = array(FALSE,'Could not bind()' );
				}
			} catch (Exception $e) {
				$this->log('admin Bind() error:' . $e->getMessage());
				$result['adminBind'] = array(FALSE,$e->getMessage());
				$result['time'] = microtime(TRUE) - $start;
				return $result;
			}
		}
		
		
		$eppn = 'asdasdasdasd@feide.no';
		// Search for bogus user
		try {
			$dn = $ldap->searchfordn($this->orgconfig['searchbase'], 'eduPersonPrincipalName', $eppn, TRUE);
			if ($ldap->getLastError()) throw new Exception('LDAP warning: ' . $ldap->getLastError());
			$result['ldapSearchBogus'] = array(TRUE,NULL);
		} catch (Exception $e) {
			$this->log('LDAP Search bogus:' . $e->getMessage());
			$result['ldapSearchBogus'] = array(FALSE,$e->getMessage());
			$result['time'] = microtime(TRUE) - $start;
			return $result;
		}
	
	
		// If test user is available
		if (array_key_exists('testUser', $this->orgconfig)) {
	
			$this->log('Testuser found in config. Performing test with test user.');

			// Try to search for DN of test account
			try {
				$dn = $ldap->searchfordn($this->orgconfig['searchbase'], 'eduPersonPrincipalName', $this->orgconfig['testUser']);
				if ($ldap->getLastError()) throw new Exception('LDAP warning: ' . $ldap->getLastError());
				$result['ldapSearchTestUser'] = array(TRUE,NULL);
			} catch (Exception $e) {
				$this->log('LDAP Search test account:' . $e->getMessage());
				$result['ldapSearchTestUser'] = array(FALSE,$e->getMessage());
				$result['time'] = microtime(TRUE) - $start;
				return $result;
			}
			
			if ($ldap->bind($dn, $this->orgconfig['testPassword'])) {
				$result['ldapBindTestUser'] = array(TRUE,NULL);
				
			} else {
				$this->log('LDAP Test user bind() failed...');
				$result['ldapBindTestUser'] = array(FALSE,NULL);
				$result['time'] = microtime(TRUE) - $start;
				return $result;
			}
	
			try {
				$attributes = $ldap->getAttributes($dn, $this->orgconfig['attributes']);
				if ($ldap->getLastError()) throw new Exception('LDAP warning: ' . $ldap->getLastError());
				$result['ldapGetAttributesTestUser'] = array(TRUE,NULL);
			} catch(Exception $e) {
				$this->log('LDAP Test user attributes failed:' . $e->getMessage());
				$result['ldapGetAttributesTestUser'] = array(FALSE,$e->getMessage());
			}
		}
		$result['time'] = microtime(TRUE) - $start;
		return $result;
	}
}
?>