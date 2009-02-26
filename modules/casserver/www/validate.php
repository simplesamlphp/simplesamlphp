<?php

/*
 * Incomming parameters:
 *  service
 *  renew
 *  ticket
 *
 */


if (!array_key_exists('service', $_GET))
	throw new Exception('Required URL query parameter [service] not provided. (CAS Server)');

$service = $_GET['service'];

if (!array_key_exists('ticket', $_GET))
	throw new Exception('Required URL query parameter [ticket] not provided. (CAS Server)');

$ticket = $_GET['ticket'];

$renew = FALSE;

if (array_key_exists('renew', $_GET)) {
	$renew = TRUE;
}



try {
	/* Load simpleSAMLphp, configuration and metadata */
	$casconfig = SimpleSAML_Configuration::getConfig('module_casserver.php');
	
	
	$path = $casconfig->resolvePath($casconfig->getValue('ticketcache', 'ticketcache'));
	
	$ticketcontent = retrieveTicket($ticket, $path);
	
	$usernamefield = $casconfig->getValue('attrname', 'eduPersonPrincipalName');
	
	if (array_key_exists($usernamefield, $ticketcontent)) {
		returnResponse('YES', $ticketcontent[$usernamefield][0]);
	} else {
		returnResponse('NO');
	}

} catch (Exception $e) {

	returnResponse('NO');
}

function returnResponse($value, $username = '') {
	if ($value === 'YES') {
		echo 'YES' . "\n" . $username;
	} else {
		echo 'NO' . "\n";
	}
}


function storeTicket($ticket, $path, &$value ) {

	if (!is_dir($path)) 
		throw new Exception('Directory for CAS Server ticket storage [' . $path . '] does not exists. ');
		
	if (!is_writable($path)) 
		throw new Exception('Directory for CAS Server ticket storage [' . $path . '] is not writable. ');

	$filename = $path . '/' . $ticket;
	file_put_contents($filename, serialize($value));
}

function retrieveTicket($ticket, $path) {

	if (!preg_match('/^_?[a-zA-Z0-9]+$/', $ticket)) throw new Exception('Invalid characters in ticket');

	if (!is_dir($path)) 
		throw new Exception('Directory for CAS Server ticket storage [' . $path . '] does not exists. ');

	$filename = $path . '/' . $ticket;

	if (!file_exists($filename))
		throw new Exception('Could not find ticket');
	
	$content = file_get_contents($filename);
	
	unlink($filename);
	
	return unserialize($content);
}




?>