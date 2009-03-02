<?php

/**
 * The standard Feide LDAP backend implementation.
 * 
 * @author Andreas Åkre Solberg <andreas.solberg@uninett.no>, UNINETT AS
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_ldapstatus_Auth_Backend_Test_StandardLDAPTest extends sspmod_feide_Auth_Backend_StandardLDAP {
    
    
    private $orgmeta = NULL;
    
    /**
     * @param $location Must be of class Configuration..
     */
    public function __construct ($location, $orgmeta) {
    	parent::__construct($location);
    	$this->orgmeta = $orgmeta;
    }
    
    
    /**
     * Perform a test of the LDAP. Used by the LDAP status page.
     */
    public function test() {



    	$result = array();
    	
    	$tester = new sspmod_ldapstatus_Tester($this->location);
    	$orgtester = new sspmod_ldapstatus_Tester($this->orgmeta);
    	$tester->tick('all');
    
		$tester->log('Testing config');
		$result['config'] = $tester->checkConfig(array('searchbase', 'hostname'));

		$tester->log('Testing config meta');
		$result['configMeta'] = $orgtester->checkConfig(array('description', array('contactMail', 'contactURL')));

		$tester->log('Testing config testuser');
		$result['configTest'] = $tester->checkConfig(array('testUser', 'testPassword'));
	
		if (!$result['config'][0]) {
			$tester->log('Skipping because of no configuration');
			$result['time'] = $tester->tack('all', FALSE);
			return $result;
		}
		
		/*
		$this->log($this->checkParameter($this->orgconfig, 'adminUser'));
		$this->log($this->checkParameter($this->orgconfig, 'adminPassword'));
		$this->log($this->checkParameter($this->orgconfig, 'testUser'));
		$this->log($this->checkParameter($this->orgconfig, 'testPassword'));
		*/
		$hostname = $this->location->getValue('hostname');
		$urldef = explode(' ', $hostname);
		$url = parse_url($urldef[0]);
		$port = 389;
		if (!empty($url['scheme']) && $url['scheme'] === 'ldaps') $port = 636;
		if (!empty($url['port'])) $port = $url['port'];
		
		$tester->log('ldapstatus Url parse [' . $hostname . '] => [' . $url['host'] . ']:[' . $port . ']' );
    
    
		$tester->tick('ping');
		$result['ping'] = $tester->phpping($url['host'], $port);
		$result['ping'][1] .= $tester->tack('ping'); 
		
	#	echo('<pre>'); print_r($result); exit;
	
		if (!$result['ping'][0]) {
			$result['time'] = $tester->tack('all', FALSE);
			$tester->log('Skipping because of no ping');
			return $result;
		}
		
		
		$result['cert'] = $this->certCheck();
		
		
		// LDAP Connect
		try {
			$tester->tick('connect');
			// Connect to LDAP.
			SimpleSAML_Logger::debug('AUTH - ldap-feide: Attempting location: ' . 
				$this->location->getValue('hostname') . '/' . $this->location->getValue('searchbase'));
			
			$hostname = $this->location->getString('hostname');
			$enableTLS = $this->location->getBoolean('enable_tls', FALSE);
			$debugLDAP = $this->location->getBoolean('debugLDAP', FALSE);
			$timeout = $this->location->getValue('timeout', 30);
			
			$this->ldap = new SimpleSAML_Auth_LDAP($hostname, $enableTLS, $debugLDAP, $timeout);

			$result['connect'] = array(TRUE,$tester->tack('connect'));
			
		} catch (Exception $e) {
			$tester->log('ldapstatus: Connect error() [' .$hostname . ']: ' . $e->getMessage());
			$result['connect'] = array(FALSE,$e->getMessage());
			$result['time'] = $tester->tack('all', FALSE);
			return $result;
		}
		

		// Do an admin bind before searching?
		if ($this->location->hasValue('adminUser')) {
			try {
				$tester->tick('adminBind');
		
				$this->adminBind($this->location->getString('adminUser'), $this->location->getString('adminPassword'));
				$result['adminBind'] = array(TRUE,$tester->tack('connect'));
				
			} catch (Exception $e) {
				$tester->log('ldapstatus: Connect error() [' . $hostname . ']: ' . $e->getMessage());
				$result['adminBind'] = array(FALSE,$e->getMessage());
				$result['time'] = $tester->tack('all', FALSE);
				return $result;
			}
		}
		
		try {
			$tester->tick('ldapSearchBogus');
			// Search for eduPersonPrincipalName of user.		
			$username = 'sd87f6ds8fsd87@feide.no';
			$userDN = $this->searchForUser($username); 
			$result['ldapSearchBogus'] = array(TRUE,$tester->tack('ldapSearchBogus'));
			
		} catch (sspmod_feide_Exception_UserNotFound $e) {
			$result['ldapSearchBogus'] = array(TRUE,$tester->tack('ldapSearchBogus'));
			
		} catch (Exception $e) {
			$tester->log('ldapstatus: Connect error() [' .$hostname . ']: ' . $e->getMessage());
			$result['ldapSearchBogus'] = array(FALSE,$e->getMessage());
			$result['time'] = $tester->tack('all', FALSE);

			return $result;
		}

		
		
		

		// If test user is available
		if ($this->location->hasValue('testUser')) {
	
			$tester->log('Testuser found in config. Performing test with test user.');
			$attributes = array();
			// Try to search for DN of test account
			try {
				$tester->tick('ldapSearchTestUser');
				
				$userDN = $this->searchForUser($this->location->getValue('testUser')); 
				$result['ldapSearchTestUser'] = array(TRUE,$tester->tack('ldapSearchTestUser'));
			} catch (Exception $e) {
				$tester->log('LDAP Search test account:' . $e->getMessage());
				$result['ldapSearchTestUser'] = array(FALSE,$e->getMessage());
				$result['time'] = $tester->tack('all', FALSE);
				return $result;
			}
			
			$tester->tick('ldapBindTestUser');
			try {
				if ($attributes = $this->userBind(
						$userDN, 
						$this->location->getValue('testPassword') )) {
					$result['ldapBindTestUser'] = array(TRUE,$tester->tack('ldapBindTestUser'));
				} else {
					$tester->log('LDAP Test user bind() failed...');
					$result['ldapBindTestUser'] = array(FALSE,'asdsad');
					$result['time'] = $tester->tack('all', FALSE);
					return $result;
				}
			} catch(Exception $e) {
				$tester->log('LDAP Test user bind() failed...');
				$result['ldapBindTestUser'] = array(FALSE,'Failed to bind: ' . $e->getMessage() );
				$result['time'] = $tester->tack('all', FALSE);
				return $result;
			}

	
			try {
				$tester->tick('getTestOrg');

				// Get organization and organizationUnit data.
				$this->getOrg(&$attributes, $this->location->getValue('testUser'));
				$this->getOrgUnits(&$attributes, $this->location->getValue('testUser'));
				$result['getTestOrg'] = array(TRUE,$tester->tack('getTestOrg'));
			} catch(Exception $e) {
				$tester->log('LDAP Test user attributes failed:' . $e->getMessage());
				$result['getTestOrg'] = array(FALSE,$e->getMessage());
			}
		}
		$result['time'] = $tester->tack('all', FALSE);
		return $result;
    }
    
    
    private function certCheck() {
	
		$result = array(FALSE, '');
	
    	$tester = new sspmod_ldapstatus_Tester($this->location);
    	$tester->tick('certcheck');
    	
		$hostname = $this->location->getValue('hostname');
		$urldef = explode(' ', $hostname);
		$url = parse_url($urldef[0]);
		$port = 389;
		if (!empty($url['scheme']) && $url['scheme'] === 'ldaps') $port = 636;
		if (!empty($url['port'])) $port = $url['port'];	
		$host = $url['host'];

		$tester->log('ldapstatus Url parse [' . $hostname . '] => [' . $host . ']:[' . $port . ']' );
		
		$cmd = 'echo "" | openssl s_client -connect ' . $host . ':' . $port . ' 2> /dev/null | openssl x509 -enddate -noout';
		$output = shell_exec($cmd);
		
		if (!empty($output)) {
		
			$cmd2 = 'echo "" | openssl s_client -connect ' . $host . ':' . $port . ' 2> /dev/null | openssl x509 -issuer -noout';
			$output2 = shell_exec($cmd2);

			if (preg_match('/issuer=(.{0,40})/', $output2, $matches) ) {
				$result['issuer'] = $matches[1];
				$result[1] .= ' ' . $output2;
			} else {
				$result[0] = FALSE;
				$result[1] = 'Did not find Issuer in response [' . $host . ':' . $port . ']';
				return $result;
			}
		} else {
			$result[0] = FALSE;
			$result[1] = 'Empty output from s_client -connect [' . $host . ':' . $port . ']';
			return $result;
		}
	
		if (preg_match('/notAfter=(.*)/', $output, $matches) ) {
			$rawdate = $matches[1];
			$date = strtotime($rawdate) - time();
			$days = floor($date / (60*60*24));
	#		echo '<p>expires in ' . $days . ' days';
			
			$result[0] = ($days > 20);
			$result['expire'] = $days;
			$result['expireText'] = date('Y-m-d', strtotime($rawdate));
			return $result;
		}
    }
    
    
}