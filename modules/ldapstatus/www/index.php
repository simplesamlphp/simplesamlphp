<?php


$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

$ldapconfig = SimpleSAML_Configuration::getConfig('config-login-feide.php');
$ldapStatusConfig = SimpleSAML_Configuration::getConfig('module_ldapstatus.php');

$debug = $ldapconfig->getValue('ldapDebug', FALSE);
$orgs = $ldapconfig->getValue('organizations');
$locationTemplate = $ldapconfig->getValue('locationTemplate');

if (array_key_exists('orgtest', $_REQUEST)) {
	$orgtest = $_REQUEST['orgtest'];
	if (!array_key_exists($orgtest, $orgs)) {
		throw new SimpleSAML_Error_NotFound('The organization ' . var_export($orgtest, TRUE) . ' could not be found.');
	}
	$orgConfig = SimpleSAML_Configuration::loadFromArray($orgs[$orgtest], 'org:[' . $orgtest . ']');

	$secretKey = sha1('ldapstatus|' . SimpleSAML_Utilities::getSecretSalt() . '|' . $_REQUEST['orgtest']);
	$secretURL = SimpleSAML_Utilities::addURLparameter(
		SimpleSAML_Utilities::selfURLNoQuery(), array(
			'orgtest' => $_REQUEST['orgtest'],
			'key' => $secretKey,
		)
	);

} else {
	$orgtest = NULL;
	$orgConfig = NULL;

	$secretKey = NULL;
	$secretURL = NULL;
}

$authsource = $ldapconfig->getString('ldapstatusAuth', NULL);
if ($session->isValid($authsource)) {
	$attributes = $session->getAttributes();
} else {
	$attributes = array();
}

$useridattr = $ldapconfig->getString('useridattr', 'eduPersonPrincipalName');
if (isset($attributes[$useridattr][0])) {
	$userId = $attributes[$useridattr][0];
} else {
	$userId = NULL;
}


$globalAllowedUsers = $ldapconfig->getArray('adminAccess', array());
$globalAdminACL = $ldapconfig->getValue('adminACL');
if (!is_null($globalAdminACL) && !is_string($globalAdminACL) && !is_array($globalAdminACL)) {
	throw new SimpleSAML_Error_Exception('The \'adminACL\' option must be either a string or an array.');
}


/* First check for global admin access. */
$isAdmin = SimpleSAML_Utilities::isAdmin();
if ($isAdmin) {
	SimpleSAML_Logger::debug('LDAPStatus auth - logged in as admin, access granted');
}

/* Global admin user list. */
if (!$isAdmin && !empty($globalAllowedUsers)) {
	if ($authsource === NULL) {
		throw new SimpleSAML_Error_Exception('The \'ldapstatusAuth\' option must be set if the \'adminAccess\' option is set.');
	}

	if (!$session->isValid($authsource)) {
		SimpleSAML_Logger::debug('LDAPStatus auth - global adminAccess: Not logged in with authsource ' . var_export($authsource, TRUE));
	} elseif (is_null($userId)) {
		throw new Exception('User ID is missing');
	} else if (!in_array($userId, $globalAllowedUsers)) {
		SimpleSAML_Logger::debug('LDAPStatus auth - global adminAccess: User ' . var_export($userId, TRUE) . ' not in allowed user list.');
	} else {
		$isAdmin = TRUE;
		SimpleSAML_Logger::debug('LDAPStatus auth - global adminAccess: User ' . var_export($userId, TRUE) . ' granted access by allowed user list.');
	}
} elseif (!$isAdmin) {
	SimpleSAML_Logger::debug('LDAPStatus auth - global adminAccess: Not configured.');
}

/* Global admin ACL list. */
if (!$isAdmin && !is_null($globalAdminACL)) {
	$globalAdminACL = new sspmod_core_ACL($globalAdminACL);

	if ($authsource === NULL) {
		throw new SimpleSAML_Error_Exception('The \'ldapstatusAuth\' option must be set if the \'adminACL\' option is set.');
	}

	if (!$session->isValid($authsource)) {
		SimpleSAML_Logger::debug('LDAPStatus auth - global ACL: Not logged in with authsource ' . var_export($authsource, TRUE));
	} elseif (!$globalAdminACL->allows($attributes)) {
		SimpleSAML_Logger::debug('LDAPStatus auth - global ACL: ACL does not grant this user global admin access.');
	} else {
		$isAdmin = TRUE;
		SimpleSAML_Logger::debug('LDAPStatus auth - global ACL: Admin access granted.');
	}
} elseif (!$isAdmin) {
	SimpleSAML_Logger::debug('LDAPStatus auth - global ACL: Not configured.');
}


if (!$isAdmin && !is_null($orgConfig)) {

	$orgAllowedUsers = $orgConfig->getArray('adminAccess', array());
	$orgAdminACL = $orgConfig->getValue('adminACL');
	if (!is_null($orgAdminACL) && !is_string($orgAdminACL) && !is_array($orgAdminACL)) {
		throw new SimpleSAML_Error_Exception('The organization\'s \'adminACL\' option must be either a string or an array.');
	}

	if (array_key_exists('key', $_REQUEST) && $_REQUEST['key'] == $secretKey ) {
		SimpleSAML_Logger::debug('LDAPStatus auth - org secretKey: Allowed access.');
		$isAdmin = TRUE;
	}

	/* Organization admin user list. */
	if (!$isAdmin && !empty($orgAllowedUsers)) {
		if ($authsource === NULL) {
			throw new SimpleSAML_Error_Exception('The \'ldapstatusAuth\' option must be set if the \'adminAccess\' option is set.');
		}

		if (!$session->isValid($authsource)) {
			SimpleSAML_Logger::debug('LDAPStatus auth - org adminAccess: Not logged in with authsource ' . var_export($authsource, TRUE));
		} elseif (is_null($userId)) {
			throw new Exception('User ID is missing');
		} else if (!in_array($userId, $orgAllowedUsers)) {
			SimpleSAML_Logger::debug('LDAPStatus auth - org adminAccess: User ' . var_export($userId, TRUE) . ' not in allowed user list.');
		} else {
			$isAdmin = TRUE;
			SimpleSAML_Logger::debug('LDAPStatus auth - org adminAccess: User ' . var_export($userId, TRUE) . ' granted access by allowed user list.');
		}
	} elseif (!$isAdmin) {
		SimpleSAML_Logger::debug('LDAPStatus auth - org adminAccess: Not configured.');
	}

	/* Organization admin ACL list. */
	if (!$isAdmin && !is_null($orgAdminACL)) {
		$orgAdminACL = new sspmod_core_ACL($orgAdminACL);

		if ($authsource === NULL) {
			throw new SimpleSAML_Error_Exception('The \'ldapstatusAuth\' option must be set if the \'adminACL\' option is set.');
		}

		if (!$session->isValid($authsource)) {
			SimpleSAML_Logger::debug('LDAPStatus auth - org ACL: Not logged in with authsource ' . var_export($authsource, TRUE));
		} elseif (!$orgAdminACL->allows($attributes)) {
			SimpleSAML_Logger::debug('LDAPStatus auth - org ACL: ACL does not grant this user access.');
		} else {
			$isAdmin = TRUE;
			SimpleSAML_Logger::debug('LDAPStatus auth - org ACL: Admin access granted.');
		}
	} elseif (!$isAdmin) {
		SimpleSAML_Logger::debug('LDAPStatus auth - org ACL: Not configured.');
	}
}

if (!$isAdmin) {
	if ($authsource === NULL) {
		/* No authsource configured - attempt global admin login. */
		SimpleSAML_Utilities::requireAdmin();
		$isAdmin = TRUE;
	} elseif ($session->isValid($authsource)) {
		throw new SimpleSAML_Error_Exception('Access denied to current user.');
	} else {
		/* Attempt to authenticate with the authsource. */
		SimpleSAML_Auth_Default::initLogin($authsource, SimpleSAML_Utilities::selfURL());
	}
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
	$cli = array();
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
	
	// Machine readable output
	if(array_key_exists('output', $_REQUEST) && $_REQUEST['output'] === 'text') {
		
		$ignores = array();
		if(array_key_exists('ignore', $_REQUEST)) {
			$ignores = explode(',', $_REQUEST['ignore']);
		}
		
		$ok = TRUE;
		foreach ($res AS $tag => $resEntry) {
			if (in_array($tag, $ignores)) continue;
			if ($tag == 'time') continue;
			if ($resEntry[0] == 0) {
				$ok = FALSE;
				echo("Error (" . $tag . ") : " . $resEntry[1] . "\n");
			}
		}		
		if ($ok) echo('OOOKKK');
		exit;
	}
	

	$t = new SimpleSAML_XHTML_Template($config, 'ldapstatus:ldapsinglehost.php');
	$t->data['res'] = $res;
	$t->data['cli'] = $tester->getCLI();
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
	$columns = array('config', 'ping', 'cert', 'adminBind', 'ldapSearchBogus', 'configTest', 'ldapSearchTestUser', 'ldapBindTestUser', 'getTestOrg', 'configMeta', 'schema');
	
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
