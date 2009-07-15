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
	private $cli = array();
    
    /**
     * @param $location Must be of class Configuration..
     */
    public function __construct ($location, $orgmeta) {
    	parent::__construct($location);
    	$this->orgmeta = $orgmeta;
    }
    

	public function getCLI() {
		return $this->cli;
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
		
		$this->cli[] = array('Ping LDAP host', 'ping ' . $url['host']);
		$this->cli[] = array('Traceroute LDAP host', 'traceroute ' . $url['host']);
		$this->cli[] = array('TCPtraceroute connection', 'tcptraceroute ' . $url['host'] . ' ' . $port);
		$this->cli[] = array('Check certificate', 'openssl s_client -host ' . $url['host'] . ' -port ' . $port);
    
    
		$tester->tick('ping');
		$result['ping'] = $tester->phpping($url['host'], $port);
		$result['ping'][1] .= $tester->tack('ping'); 
		$result['ping']['time'] = $tester->tack('ping', FALSE); 
		
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
		
		$cliAdminBind = '';
		// Do an admin bind before searching?
		if ($this->location->hasValue('adminUser')) {
			try {
				$tester->tick('adminBind');
		
				$this->adminBind();
				$result['adminBind'] = array(TRUE,$tester->tack('connect'));
				$result['adminBind']['time'] = $tester->tack('connect', FALSE); 
				
				$cliAdminBind = "-D '" . $this->location->getString('adminUser') . "' -W ";
				$this->cli[] = array('Bind as admin (and read user base)', 
					"ldapsearch -H " . $hostname . " -b '" . $this->location->getValue('searchbase') . "' " . 
					"-s base -V -x " . 
					$cliAdminBind
				);
				
			} catch (Exception $e) {
				$tester->log('ldapstatus: Connect error() [' . $hostname . ']: ' . $e->getMessage());
				$result['adminBind'] = array(FALSE,$e->getMessage());
				$result['time'] = $tester->tack('all', FALSE);
				return $result;
			}
		} else {
			$this->cli[] = array('Bind as anonymous (and read user base)', 
				"ldapsearch -H " . $hostname . " -b '" . $this->location->getValue('searchbase') . "' " . 
				"-s base -V -x "
			);
		}
		
		try {
			$tester->tick('ldapSearchBogus');
			// Search for eduPersonPrincipalName of user.		
			$username = 'sd87f6ds8fsd87@feide.no';
			$userDN = $this->searchForUser($username); 
			$result['ldapSearchBogus'] = array(TRUE,$tester->tack('ldapSearchBogus'));
			$result['ldapSearchBogus']['time'] = $tester->tack('ldapSearchBogus', FALSE); 
		
		} catch (SimpleSAML_Error_UserNotFound $e) {
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
				
				$testUser = $this->location->getString('testUser');
				$userDN = $this->searchForUser($testUser);
				$result['ldapSearchTestUser'] = array(TRUE,$tester->tack('ldapSearchTestUser'));
				$result['ldapSearchTestUser']['time'] = $tester->tack('ldapSearchTestUser', FALSE); 
				
				$this->cli[] = array('Search for test user', 
					"ldapsearch -H " . $hostname . " -b '" . $this->location->getValue('searchbase') . "' " . 
					"-s sub -V -x " . 
					$cliAdminBind . " '(|(eduPersonPrincipalName=" . $this->location->getValue('testUser') . "))'"
				);

				$this->cli[] = array('Read test user attributes (user bind)', 
					"ldapsearch -H " . $hostname . " -b '" . $userDN . "' " . 
					"-s base -V -x " . 
					"-D '" . $userDN . "' -W "
				);

				$this->cli[] = array('Read test user attributes (as admin/anonymous)', 
					"ldapsearch -H " . $hostname . " -b '" . $userDN . "' " . 
					"-s base -V -x " . 
					$cliAdminBind
				);

					
			} catch (Exception $e) {
				$tester->log('LDAP Search test account:' . $e->getMessage());
				$result['ldapSearchTestUser'] = array(FALSE,$e->getMessage());
				$result['time'] = $tester->tack('all', FALSE);
				return $result;
			}
			
			$tester->tick('ldapBindTestUser');
			try {
				$this->userBind($testUser, $userDN, $this->location->getValue('testPassword'));
				$attributes = $this->getAttributes($userDN);
				if ($attributes) {
					$result['ldapBindTestUser'] = array(TRUE,$tester->tack('ldapBindTestUser'));
					$result['ldapBindTestUser']['time'] = $tester->tack('ldapBindTestUser', FALSE); 
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
				$attributes = $this->addOrgAttributes($attributes);

				$result['getTestOrg'] = array(TRUE,$tester->tack('getTestOrg'));
				$result['getTestOrg']['time'] = $tester->tack('getTestOrg', FALSE); 
				
				if (array_key_exists('eduPersonOrgDN:norEduOrgSchemaVersion', $attributes)) {
					if (version_compare($attributes['eduPersonOrgDN:norEduOrgSchemaVersion'][0], '1.4', '>=')) {
						$result['schema'] = array(TRUE, 'Version: ' . $attributes['eduPersonOrgDN:norEduOrgSchemaVersion'][0]);
					} else {
						$result['schema'] = array(FALSE, 'Version: ' . $attributes['eduPersonOrgDN:norEduOrgSchemaVersion'][0]);
					}
				} 
				
				$result['getTestOrg'] = array(TRUE,$tester->tack('getTestOrg'));
				$result['getTestOrg']['time'] = $tester->tack('getTestOrg', FALSE);
				
			} catch(Exception $e) {
				$tester->log('LDAP Test user attributes failed: ' . $e->getMessage());
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
		
			$cmd2 = 'echo "" | openssl s_client -connect ' . $host . ':' . $port . ' 2> /dev/null | openssl x509 -issuer -subject -noout';
			$output2 = shell_exec($cmd2);

			if (preg_match('/issuer=(.{0,40})/', $output2, $matches) ) {
				$result['issuer'] = trim($matches[1]);
				$result[1] .= ' ' . $output2;
				
				if (preg_match('/subject=(.{0,40})/', $output2, $matches) ) {
					$result['subject'] = trim($matches[1]);
				}
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