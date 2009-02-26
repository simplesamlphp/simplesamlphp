<?php

/*
 * Incomming parameters:
 *  service
 *  renew
 *  gateway
 *  
 */


if (!array_key_exists('service', $_GET))
	throw new Exception('Required URL query parameter [service] not provided. (CAS Server)');

$service = $_GET['service'];
$renew = FALSE;
$gateway = FALSE;

if (array_key_exists('renew', $_GET)) {
	$renew = TRUE;
}

if (array_key_exists('gateway', $_GET)) {
	$gateway = TRUE;
	throw new Exception('CAS gateway to SAML IsPassive: Not yet implemented properly.');
}





/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$casconfig = SimpleSAML_Configuration::getConfig('module_casserver.php');
$session = SimpleSAML_Session::getInstance();


$legal_service_urls = $casconfig->getValue('legal_service_urls');
if (!checkServiceURL($service, $legal_service_urls))
	throw new Exception('Service parameter provided to CAS server is not listed as a legal service: [service] = ' . $service);

$auth = $casconfig->getValue('auth', 'saml2');
if (!in_array($auth, array('saml2', 'shib13')))
	throw new Exception('CAS Service configured to use [auth] = ' . $auth . ' only [saml2,shib13] is legal.');

if (!$session->isValid($auth) ) {
	SimpleSAML_Utilities::redirect(
		'/' . $config->getBaseURL() . $auth . '/sp/initSSO.php',
		array('RelayState' => SimpleSAML_Utilities::selfURL() )
	);
}
$attributes = $session->getAttributes();

$path = $casconfig->resolvePath($casconfig->getValue('ticketcache', 'ticketcache'));
$ticket = SimpleSAML_Utilities::generateID();
storeTicket($ticket, $path, $attributes);

// $test = retrieveTicket($ticket, $path);


SimpleSAML_Utilities::redirect(
	SimpleSAML_Utilities::addURLparameter($service,
		array('ticket' => $ticket)
	)
);



function storeTicket($ticket, $path, &$value ) {

	if (!is_dir($path)) 
		throw new Exception('Directory for CAS Server ticket storage [' . $path . '] does not exists. ');
		
	if (!is_writable($path)) 
		throw new Exception('Directory for CAS Server ticket storage [' . $path . '] is not writable. ');

	$filename = $path . '/' . $ticket;
	file_put_contents($filename, serialize($value));
}

function retrieveTicket($ticket, $path) {

	if (!is_dir($path)) 
		throw new Exception('Directory for CAS Server ticket storage [' . $path . '] does not exists. ');


	$filename = $path . '/' . $ticket;
	return unserialize(file_get_contents($filename));
}



function checkServiceURL($service, array $legal_service_urls) {
	foreach ($legal_service_urls AS $legalurl) {
		if (strpos($service, $legalurl) === 0) return TRUE;
	}
	return FALSE;
}






?>