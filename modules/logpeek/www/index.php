<?php

function logFilter($objFile, $tag, $cut){
	if (!preg_match('/^[a-f0-9]{10}$/D', $tag)) throw new Exception('Invalid search tag');
	
	$i = 0;
	$results = array();
	$line = $objFile->getPreviousLine();
	while($line !== FALSE && ($i++ < $cut)){
		if(strstr($line, '[' . $tag . ']')){
			$results[] = $line;
		}
		$line = $objFile->getPreviousLine();
	}
	$results[] = 'Searched ' . $i . ' lines backward. ' . count($results) . ' lines found.';
	$results = array_reverse($results);
	return $results;
}


$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

SimpleSAML_Utilities::requireAdmin();

$logpeekconfig = SimpleSAML_Configuration::getConfig('module_logpeek.php');
$logfile = $logpeekconfig->getValue('logfile', '/var/simplesamlphp.log');
$blockSize = $logpeekconfig->getValue('blocksz', 8192);

$myLog = new sspmod_logpeek_File_reverseRead($logfile, $blockSize);


$results = NULL;
if (isset($_REQUEST['tag'])) {
	$results = logFilter($myLog, $_REQUEST['tag'], $logpeekconfig->getValue('lines', 500));
}


$fileModYear = date("Y", $myLog->getFileMtime());
$firstLine = $myLog->getFirstLine();
$firstTimeEpoch = sspmod_logpeek_Syslog_parseLine::getUnixTime($firstLine, $fileModYear);
$lastLine = $myLog->getLastLine();
$lastTimeEpoch = sspmod_logpeek_Syslog_parseLine::getUnixTime($lastLine, $fileModYear);
$fileSize = $myLog->getFileSize();

$t = new SimpleSAML_XHTML_Template($config, 'logpeek:logpeek.php');
$t->data['results'] = $results;
$t->data['trackid'] = $session->getTrackID();
$t->data['timestart'] = date(DATE_RFC822, $firstTimeEpoch);
$t->data['endtime'] = date(DATE_RFC822, $lastTimeEpoch);
$t->data['filesize'] = $fileSize;

$t->show();
?>