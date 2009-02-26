<?php


$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

if (!$session->isValid('login-admin') ) {
	SimpleSAML_Utilities::redirect('/' . $config->getBaseURL() . 'auth/login-admin.php',
		array('RelayState' => SimpleSAML_Utilities::selfURL())
	);
}


$logpeekconfig = SimpleSAML_Configuration::getConfig('module_logpeek.php');

$logfile = $logpeekconfig->getValue('logfile', '/var/simplesamlphp.log');

function grepLog($logfile, $tag, $lines) {

	if (!is_readable($logfile)) throw new Exception('Log file [' . $logfile . '] is not readable. Consider checking the file permissions');
	if (!preg_match('/^[a-f0-9]{10}$/', $tag)) throw new Exception('Invalid search tag');
	
	$results = array();
	$i=0 ;
	$line = '';
	$fp = fopen($logfile,"r") ;
	if(is_resource($fp)){
		fseek($fp,0,SEEK_END) ;
		$a = ftell($fp) ;
		while($i <= $lines){
			if(fgetc($fp) == "\n"){
				$line = fgets($fp);
				$i++ ;
				if (strstr($line, '[' . $tag . ']')) 
					$results[] = $line;
			}
			fseek($fp,$a);
			$a-- ;
		}
	}

	$results[] = 'Start search line (' . $lines . ' lines back): ' . substr($line,0,40) . '...';
	$results = array_reverse($results);
	return $results;
}

$results = NULL;
if (isset($_REQUEST['tag'])) {
	$results = grepLog($logfile, $_REQUEST['tag'], $logpeekconfig->getValue('lines', 500));
// 	echo('<pre>log:');
// 	print_r($results);
}



$t = new SimpleSAML_XHTML_Template($config, 'logpeek:logpeek.php');
$t->data['results'] = $results;
$t->data['trackid'] = $session->getTrackID();
$t->show();
exit;

?>
