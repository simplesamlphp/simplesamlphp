<?php

$config = SimpleSAML_Configuration::getInstance();
$cronconfig = SimpleSAML_Configuration::getConfig('module_cron.php');

if (!is_null($cronconfig->getValue('key'))) {
	if ($_REQUEST['key'] !== $cronconfig->getValue('key')) {
		SimpleSAML\Logger::error('Cron - Wrong key provided. Cron will not run.');
		exit;
	}
}

$cron = new SimpleSAML\Module\cron\Cron();
if (!$cron->isValidTag($_REQUEST['tag'])) {
    SimpleSAML\Logger::error('Cron - Illegal tag [' . $_REQUEST['tag'] . '].');
    exit;
}


$url = \SimpleSAML\Utils\HTTP::getSelfURL();
$time = date(DATE_RFC822);

$croninfo = $cron->runTag($_REQUEST['tag']);
$summary = $croninfo['summary'];

if ($cronconfig->getValue('sendemail', TRUE) && count($summary) > 0) {

	$message = '<h1>Cron report</h1><p>Cron ran at ' . $time . '</p>' .
		'<p>URL: <tt>' . $url . '</tt></p>' .
		'<p>Tag: ' . $croninfo['tag'] . "</p>\n\n" .
		'<ul><li>' . join('</li><li>', $summary) . '</li></ul>';

	$toaddress = $config->getString('technicalcontact_email', 'na@example.org');
	if($toaddress == 'na@example.org') {
		SimpleSAML\Logger::error('Cron - Could not send email. [technicalcontact_email] not set in config.');
	} else {
		// Use $toaddress for both TO and FROM
		$email = new SimpleSAML_XHTML_EMail($toaddress, 'SimpleSAMLphp cron report', $toaddress);
		$email->setBody($message);
		$email->send();
	}
	
}

if (isset($_REQUEST['output']) && $_REQUEST['output'] == "xhtml") {
	$t = new SimpleSAML_XHTML_Template($config, 'cron:croninfo-result.php','cron:cron');
	$t->data['tag'] = $croninfo['tag'];
	$t->data['time'] = $time;
	$t->data['url'] = $url;
	$t->data['summary'] = $summary;
	$t->show();
}
