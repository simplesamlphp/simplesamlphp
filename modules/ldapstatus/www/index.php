<?php


$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

$ldapconfig = SimpleSAML_Configuration::getConfig('config-login-feide.php');
$ldapStatusConfig = SimpleSAML_Configuration::getConfig('module_ldapstatus.php');

$debug = $ldapconfig->getValue('ldapDebug', FALSE);
$orgs = $ldapconfig->getValue('organizations');
$locationTemplate = $ldapconfig->getValue('locationTemplate');


$isAdmin = FALSE;
$secretURL = NULL;
if (array_key_exists('orgtest', $_REQUEST)) {
	$secretKey = sha1('ldapstatus|' . $config->getValue('secret') . '|' . $_REQUEST['orgtest']);
	$secretURL = SimpleSAML_Utilities::addURLparameter(
		SimpleSAML_Utilities::selfURLNoQuery(), array(
			'orgtest' => $_REQUEST['orgtest'],
			'key' => $secretKey,
		)
	);
	if (array_key_exists('key', $_REQUEST) && $_REQUEST['key'] == $secretKey ) {
		// OK Access
	} else {
		
		
		$useridattr = $ldapconfig->getString('useridattr', 'eduPersonPrincipalName');
		$authsource = $ldapconfig->getString('ldapstatusAuth', NULL);

		$allowedusers = $ldapconfig->getArray('adminAccess', array());		
		if (isset($orgs[$_REQUEST['orgtest']]) && array_key_exists('adminAccess', $orgs[$_REQUEST['orgtest']]))
			$allowedusers = array_merge($allowedusers, $orgs[$_REQUEST['orgtest']]['adminAccess']);
	
		if (SimpleSAML_Utilities::isAdmin()) {
			// User logged in as admin. OK.
			SimpleSAML_Logger::debug('LDAPStatus auth - logged in as admin, access granted');
			
		} elseif(isset($authsource) && $session->isValid($authsource) ) {
		
			// User logged in with auth source.
			SimpleSAML_Logger::debug('LDAPStatus auth - valid login with auth source [' . $authsource . ']');
			SimpleSAML_Logger::debug('LDAPStatus auth - allowed users [' . join(',', $allowedusers). ']');
			
			// Retrieving attributes
			$attributes = $session->getAttributes();
			
			// Check if userid exists
			if (!isset($attributes[$useridattr])) 
				throw new Exception('User ID is missing');
			
			// Check if userid is allowed access..
			if (!in_array($attributes[$useridattr][0], $allowedusers)) {
				SimpleSAML_Logger::debug('LDAPStatus auth - User denied access by user ID [' . $attributes[$useridattr][0] . ']');
				throw new Exception('Access denied for this user.');
			}
			SimpleSAML_Logger::debug('LDAPStatus auth - User granted access by user ID [' . $attributes[$useridattr][0] . ']');		
			
		} elseif(isset($authsource)) {
			// If user is not logged in init login with authrouce if authsousrce is defined.
			SimpleSAML_Auth_Default::initLogin($authsource, SimpleSAML_Utilities::selfURL());
			
		} else {
			// If authsource is not defined, init admin login.
			SimpleSAML_Utilities::requireAdmin();
		}

		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
// 		SimpleSAML_Utilities::requireAdmin();
		$isAdmin = TRUE;
	}

} else {

	// Require admin access to overview page...
	SimpleSAML_Utilities::requireAdmin();
	$isAdmin = TRUE;

}



function backtrace() {
	return join(' - ', debug_backtrace());
}

function myErrorHandler($errno, $errstr, $errfile, $errline) {


	echo('<div style="border: 1px dotted #ccc; margin: .3em; padding: .4em;">');
	switch ($errno) {
		case E_USER_ERROR:
			echo('<p>PHP_ERROR   : [' . $errno . '] ' . $errstr . '. Fatal error on line ' . $errline . ' in file ' . $errfile);
			break;
	
		case E_USER_WARNING:
			echo('<p>PHP_WARNING : [' . $errno . '] ' . $errstr . '. Warning on line ' . $errline . ' in file ' . $errfile);
			break;
	
		case E_USER_NOTICE:
			echo('<p>PHP_WARNING : [' . $errno . '] ' . $errstr . '. Warning on line ' . $errline . ' in file ' . $errfile);        
			break;
	
		default:
			echo('<p>PHP_UNKNOWN : [' . $errno . '] ' . $errstr . '. Unknown error on line ' . $errline . ' in file ' . $errfile);        
			break;
    }
    
#    echo('<div style="font-style:monospace; font-size: x-small; margin: 1em; color: #966"><li>' . join('</li><li>', debug_backtrace()) . '</li></div>');
    echo('<pre style="font-style:monospace; font-size: small; margin: 1em; color: #966">');
    echo(debug_print_backtrace()); 
    echo('</pre>');
	echo('</div>');

    
    flush();

    /* Don't execute PHP internal error handler */
    return true;
}








$results = $session->getData('module:ldapstatus', 'results');
if (empty($results)) {
	$results = array();
} elseif (array_key_exists('reset', $_GET) && $_GET['reset'] === '1') {
	$results = array();
}

#echo('<pre>'); print_r($results); exit;


$start = microtime(TRUE);
$previous = microtime(TRUE);

$maxtime = $ldapStatusConfig->getValue('maxExecutionTime', 15); 


if (array_key_exists('orgtest', $_REQUEST)) {
	#$old_error_handler = set_error_handler("myErrorHandler");
	
	$locindex = 0;
	if (array_key_exists('locindex', $_REQUEST)) $locindex = $_REQUEST['locindex'];
	
	SimpleSAML_Logger::setCaptureLog();
	
	$orgconfig = SimpleSAML_Configuration::loadFromArray($orgs[$_REQUEST['orgtest']], 'org:[' . $_REQUEST['orgtest'] . ']');
	$orgloc = $orgs[$_REQUEST['orgtest']]['locations'][$locindex];
	$orgloc = mergeWithTemplate($orgloc, $locationTemplate);
	$classname = SimpleSAML_Module::resolveClass($orgloc['testType'], 'Auth_Backend_Test');
	$tester = new $classname(
			SimpleSAML_Configuration::loadFromArray($orgloc, 'Location@[' . $_REQUEST['orgtest'] . ']'),
			$orgconfig);
			
	$res = $tester->test();
	
	if(array_key_exists('output', $_REQUEST) && $_REQUEST['output'] === 'text') {
		
		$ok = TRUE;
		foreach ($res AS $tag => $resEntry) {
			if ($tag == 'time') continue;
			if ($resEntry[0] == 0) {
				$ok = FALSE;
				echo("Error (" . $tag . ") : " . $resEntry[1] . "\n");
			}
		}
		
		if ($ok) {
			echo('OOOKKK');
		}
		
		// print_r($res);
		// print_r($orgs[$_REQUEST['orgtest']]);
		
		exit;
		
		
	}
	

	$t = new SimpleSAML_XHTML_Template($config, 'ldapstatus:ldapsinglehost.php');
	
	$t->data['res'] = $res;
	$t->data['org'] = $orgs[$_REQUEST['orgtest']];
	$t->data['debugLog'] = SimpleSAML_Logger::getCapturedLog();
	if ($isAdmin) $t->data['secretURL'] = $secretURL;
	$t->show();
	exit;
}

function mergeWithTemplate($location, $template) {
	foreach($template AS $key => $value) {
		if (!array_key_exists($key, $location)) $location[$key] = $value;
	}
	return $location;
}

$start = microtime(TRUE);
foreach($orgs AS $orgkey => $org) {
	if (array_key_exists($orgkey, $results)) continue;
	$orgconfig = SimpleSAML_Configuration::loadFromArray($org, 'org:[' . $orgkey . ']');
	$orglocs = $org['locations'];
	$results[$orgkey] = array();
	foreach($orglocs AS $orgloc) {
		$orgloc = mergeWithTemplate($orgloc, $locationTemplate);
		$classname = SimpleSAML_Module::resolveClass($orgloc['testType'], 'Auth_Backend_Test');
		$tester = new $classname(
			SimpleSAML_Configuration::loadFromArray($orgloc, 'Location@[' . $orgkey . ']'),
			$orgconfig);
		$results[$orgkey][] = $tester->test();
	}
	if ((microtime(TRUE) - $start) > $maxtime) {
		SimpleSAML_Logger::debug('ldapstatus: Completing execution after maxtime [' .(microtime(TRUE) - $start) . ' of maxtime ' . $maxtime . ']');
		break;
	}
	
}



$session->setData('module:ldapstatus', 'results', $results);

#echo '<pre>'; print_r($results); exit;

$lightCounter = array(0,0,0);



function resultCode($res, $sortby = NULL) {
	global $lightCounter;
	$code = '';
	$columns = array('config', 'ping', 'cert', 'adminBind', 'ldapSearchBogus', 'configTest', 'ldapSearchTestUser', 'ldapBindTestUser', 'getTestOrg', 'configMeta');
	
	if (!empty($sortby) && in_array($sortby, $columns)) {
		
			
		if (array_key_exists($sortby, $res)) {
		
			if ($res[$sortby][0]) {
				$code .= '0';
			} else {
				$code .= '2';
			}
			
		} else {
			$code .= '1';
		}
		
		if ($sortby == 'cert') {
			if (array_key_exists($sortby, $res) && isset($res[$sortby]['expire'])) 
				$code .= sprintf("%05s", (99999 - $res[$sortby]['expire']) );
			else
				$code .= '-----';
		}
		
		$code .= '|';
	}
	if ($sortby === 'time') {
		if (array_key_exists($sortby, $res)) 
			$code .= sprintf("%05s", floor(1000*$res[$sortby]) );
		else
			$code .= '-----';
		$code .= '|';
	}
	
	foreach ($columns AS $c) {
		if (array_key_exists($c, $res)) {
			if ($res[$c][0]) {
				$code .= '0';
				$lightCounter[0]++;
			} else {
				$code .= '2';
				$lightCounter[2]++;
			}
			
		} else {
			$code .= '0';
			$lightCounter[1]++;
		}
	}
	return $code;
}
	
	
$ressortable = array();
foreach ($results AS $key => $res) {
	$ressortable[$key] = resultCode($res[0], (isset($_REQUEST['sort']) ? $_REQUEST['sort'] : NULL));
}
arsort($ressortable);
#echo '<pre>'; print_r($ressortable); exit;


$t = new SimpleSAML_XHTML_Template($config, 'ldapstatus:ldapstatus.php');

$t->data['showcomments'] = array_key_exists('showcomments', $_REQUEST);
$t->data['completeNo'] = count($results);
$t->data['completeOf'] = count($orgs);
$t->data['results'] = $results;
$t->data['orgconfig'] = $orgs;
$t->data['lightCounter'] = $lightCounter;
$t->data['sortedOrgIndex'] = array_keys($ressortable);
$t->show();
exit;

?>
