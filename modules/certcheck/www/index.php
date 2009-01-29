<?php


$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

if (!$session->isValid('login-admin') ) {
	SimpleSAML_Utilities::redirect('/' . $config->getBaseURL() . 'auth/login-admin.php',
		array('RelayState' => SimpleSAML_Utilities::selfURL())
	);
}


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


$ldapconfig = $config->copyFromBase('loginfeide', 'config-login-feide.php');
$ldapStatusConfig = $config->copyFromBase('ldapstatus', 'module_ldapstatus.php');

$pingcommand = $ldapStatusConfig->getValue('ping');

$debug = $ldapconfig->getValue('ldapDebug', FALSE);

$orgs = $ldapconfig->getValue('orgldapconfig');

#echo '<pre>'; print_r($orgs); exit;

$results = array();
$resultsm = array();

$i = 0;
foreach ($orgs AS $orgkey => $orgconfig) {

#	if (++$i > 10) continue;

	if (empty($orgconfig['hostname'])) continue;

	$urldef = explode(' ', $orgconfig['hostname']);
	$url = parse_url($urldef[0]);
	$port = 389;
	if (preg_match('/^ldaps/', $urldef[0])) $port = 636;
	if (!empty($url['port'])) $port = $url['port'];
	
	if (!array_key_exists('host', $url)) {
		echo 'could not resolve host name in ' . $urldef[0]; exit;
	}
	
	$host = $url['host'];
	
#	echo 'pinging ' . $host . ' port ' . $port;
	$ping = phpping($host, $port);
	if ($ping[0] === FALSE) continue;
	
	
	$cmd = 'echo "" | openssl s_client -connect ' . $host . ':' . $port . ' 2> /dev/null | openssl x509 -enddate -noout';
	$output = shell_exec($cmd);
	
	if (!empty($output)) {
	
		$cmd2 = 'echo "" | openssl s_client -connect ' . $host . ':' . $port . ' 2> /dev/null | openssl x509 -issuer -noout';
		$output2 = shell_exec($cmd2);
// 		echo $output; exit;
		if (preg_match('/issuer=(.{0,40})/', $output2, $matches) ) {
			$resultsm[$host]['issuer'] = $matches[1];
		}
	}

	if (preg_match('/notAfter=(.*)/', $output, $matches) ) {
		$rawdate = $matches[1];
		$date = strtotime($rawdate) - time();
// 		echo '<pre>';
// 		print_r($date); 
		$days = floor($date / (60*60*24));
#		echo '<p>expires in ' . $days . ' days';
		
		$results[$host] = $days;
		$resultsm[$host]['expire'] = date('jS F Y', strtotime($rawdate));		

	}
	
}

asort($results);
// echo '<pre>';
// print_r($results);
// print_r($resultsm);
// exit;

$t = new SimpleSAML_XHTML_Template($config, 'certcheck:certcheck.php');
$t->data['results'] = $results;
$t->data['resultsm'] = $resultsm;
$t->show();
exit;

?>
