<?php


$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

if (!$session->isValid('login-admin') ) {
	SimpleSAML_Utilities::redirect('/' . $config->getBaseURL() . 'auth/login-admin.php',
		array('RelayState' => SimpleSAML_Utilities::selfURL())
	);
}


$ldapconfig = $config->copyFromBase('loginfeide', 'config-login-feide.php');
$ldapStatusConfig = $config->copyFromBase('ldapstatus', 'module_ldapstatus.php');

$pingcommand = $ldapStatusConfig->getValue('ping');


$orgs = $ldapconfig->getValue('orgldapconfig');

#echo '<pre>'; print_r($orgs); exit;


function phpping($host, $port) {

	SimpleSAML_Logger::debug('ldapstatus phpping(): ping [' . $host . ':' . $port . ']' );

	$timeout = 1.0;
	$socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
	@fclose($socket);
	if ($errno) {
		return array(FALSE, $errno . ':' . $errstr);
	} else {		
		return array(TRUE,NULL);
	}
}

function checkConfig($conf, $req) {
	$err = array();
	foreach($req AS $r) {
		if (!array_key_exists($r, $conf)) {
			$err[] = $r;
		} elseif (empty($conf[$r])) {
			$err[] = 'empty:' . $r;
		}
	}
	if (count($err) > 0) {
		return array(FALSE, 'Missing: ' . join(', ', $err));
	}
	return array(TRUE, NULL);	
}

$results = array();

foreach ($orgs AS $orgkey => $orgconfig) {

	$results[$orgkey] = array();
	

	$results[$orgkey]['config'] = checkConfig($orgconfig, array('description', 'searchbase', 'hostname'));
	$results[$orgkey]['configMeta'] = checkConfig($orgconfig, array('enable_tls', 'testUser', 'testPassword', 'contactMail', 'contactURL'));

	if (!$results[$orgkey]['config'][0]) continue;

	$urldef = explode(' ', $orgconfig['hostname']);
	$url = parse_url($urldef[0]);
	$port = 389;
	if (!empty($url['port'])) $port = $url['port'];
	
	SimpleSAML_Logger::debug('ldapstatus Url parse [' . $orgconfig['hostname'] . '] => [' . $url['host'] . ']:[' . $port . ']' );

// 	$pingreturn = NULL;
// 	$pingoutput = NULL;
// 	exec($pingcommand . ' ' . escapeshellcmd($url['host']), $pingoutput, $pingreturn);
// 	if ($pingreturn == '0') {
// 		$results[$orgkey]['ping'] = array(TRUE,join("\r\n", $pingoutput));
// 	} else {
// 		$results[$orgkey]['ping'] = array(FALSE,join("\r\n", $pingoutput));
// 		continue;
// 	}

	$results[$orgkey]['ping'] = phpping($url['host'], $port);

	if (!$results[$orgkey]['ping'][0]) continue;

	
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
		
		if ($ldap->bind($dn, $orgconfig['testPassword'])) {
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

function resultCode($res) {
	$code = '';
	$columns = array('config', 'configMeta', 'ping', 'adminUser', 'ldapSearchBogus', 'ldapSearchTestUser', 'ldapBindTestUser', 'ldapGetAttributesTestUser');
	foreach ($columns AS $c) {
		if (array_key_exists($c, $res)) {
			$code .= ($res[$c][0] ? '0' : '2');
		} else {
			$code .= '1';
		}
	}
	return $code;
}

	
	
$ressortable = array();
foreach ($results AS $key => $res) {
	$ressortable[$key] = resultCode($res);
}
asort($ressortable);
#echo '<pre>'; print_r($ressortable); exit;


$t = new SimpleSAML_XHTML_Template($config, 'ldapstatus:ldapstatus.php');
$t->data['results'] = $results;
$t->data['orgconfig'] = $orgs;
$t->data['sortedOrgIndex'] = array_keys($ressortable);
$t->show();
exit;

?>
