<?php


$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

if (!$session->isValid('login-admin') ) {
	SimpleSAML_Utilities::redirect('/' . $config->getBaseURL() . 'auth/login-admin.php',
		array('RelayState' => SimpleSAML_Utilities::selfURL())
	);
}


$ldapconfig = $config->copyFromBase('loginfeide', 'config-login-feide.php');




$orgs = $ldapconfig->getValue('orgldapconfig');

#echo '<pre>'; print_r($orgs); exit;

function checkConfig($conf, $req) {
	$err = array();
	foreach($req AS $r) {
		if (!array_key_exists($r, $conf)) $err[] = $r;
	}
	if (count($err) > 0) {
		return array(FALSE, 'Missing: ' . join(', ', $err));
	}
	return array(TRUE, NULL);	
}

$results = array();

foreach ($orgs AS $orgkey => $orgconfig) {

	$results[$orgkey] = array();
	

	$results[$orgkey]['config'] = checkConfig($orgconfig, array('description', 'searchbase', 'hostname', 'attributes'));
	$results[$orgkey]['configMeta'] = checkConfig($orgconfig, array('enable_tls', 'testUser', 'testPassword', 'contactMail', 'contactURL'));
	
	$url = parse_url($orgconfig['hostname']);

	$pingreturn = NULL;
	$pingoutput = NULL;
	exec('ping -W 1 -c 1  ' . escapeshellcmd($url['host']), $pingoutput, $pingreturn);

#	echo $pingreturn; exit;

	if ($pingreturn == '0') {
		$results[$orgkey]['ping'] = array(TRUE,join("\r\n", $pingoutput));
	} else {
		$results[$orgkey]['ping'] = array(FALSE,join("\r\n", $pingoutput));
		continue;
	}
	
	#continue;
	
	// LDAP Connect
	try {
		$ldap = new SimpleSAML_Auth_LDAP($orgconfig['hostname'], $orgconfig['enable_tls']);
		$results[$orgkey]['connect'] = array(TRUE,NULL);
	} catch (Exception $e) {
		$results[$orgkey]['connect'] = array(FALSE,$e->getMessage());
		continue;
	}

	// Bind as admin user
	if (isset($orgconfig['adminUser'])) {
		try {
			$ldap->bind($orgconfig['adminUser'], $orgconfig['adminPassword']);
			$results[$orgkey]['adminBind'] = array(TRUE,NULL);
		} catch (Exception $e) {
			$results[$orgkey]['adminBind'] = array(FALSE,$e->getMessage());
			continue;
		}
	}
	
	
	$eppn = 'test@feide.no';
	// Search for bogus user
	try {
		$dn = $ldap->searchfordn($orgconfig['searchbase'], 'eduPersonPrincipalName', $eppn, TRUE);
		$results[$orgkey]['ldapSearchBogus'] = array(TRUE,NULL);
	} catch (Exception $e) {
		$results[$orgkey]['ldapSearchBogus'] = array(FALSE,$e->getMessage());
		continue;
	}


	// If test user is available
	if (array_key_exists('testUser', $orgconfig)) {

		// Try to search for DN of test account
		try {
			$dn = $ldap->searchfordn($orgconfig['searchbase'], 'eduPersonPrincipalName', $eppn);
			$results[$orgkey]['ldapSearchTestUser'] = array(TRUE,NULL);
		} catch (Exception $e) {
			$results[$orgkey]['ldapSearchTestUser'] = array(FALSE,$e->getMessage());
			continue;
		}
		
		if ($ldap->bind($orgconfig['testUser'], $orgconfig['testPassword'])) {
			$results[$orgkey]['ldapBindTestUser'] = array(TRUE,NULL);
			
		} else {
			$results[$orgkey]['ldapBindTestUser'] = array(FALSE,NULL);
			continue;
		}

		try {
			$attributes = $ldap->getAttributes($dn, $orgconfig['attributes'], $ldapconfig->getValue('attributesize.max', NULL));
			$results[$orgkey]['ldapGetAttributesTestUser'] = array(TRUE,NULL);
		} catch(Exception $e) {
			$results[$orgkey]['ldapGetAttributesTestUser'] = array(FALSE,$e->getMessage());
		}
	}
}
#echo '<pre>'; print_r($results); exit;

$t = new SimpleSAML_XHTML_Template($config, 'ldapstatus:ldapstatus.php');
$t->data['results'] = $results;
$t->data['orgconfig'] = $orgs;
$t->show();
exit;

?>
