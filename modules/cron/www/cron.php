<?php

$config = SimpleSAML_Configuration::getInstance();
$cronconfig = SimpleSAML_Configuration::getConfig('module_cron.php');

if (!is_null($cronconfig->getValue('key'))) {
	if ($_REQUEST['key'] !== $cronconfig->getValue('key')) {
		SimpleSAML_Logger::error('Cron - Wrong key provided. Cron will not run.');
		exit;
	}
}
#print_r($_REQUEST['tag']) ; exit;

if (!is_null($cronconfig->getValue('allowed_tags'))) {
	if (!in_array($_REQUEST['tag'], $cronconfig->getValue('allowed_tags'))) {
		SimpleSAML_Logger::error('Cron - Illegal tag [' . $_REQUEST['tag'] . '].');
		exit;
	}
}



$summary = array(); 
$croninfo = array(
	'summary' => &$summary,
	'tag' => $_REQUEST['tag'],
);
SimpleSAML_Module::callHooks('cron', $croninfo);

foreach ($summary AS $s) {
	SimpleSAML_Logger::debug('Cron - Summary: ' . $s);
}

if ($cronconfig->getValue('sendemail', TRUE) && count($summary) > 0) {

	$statustext = '<ul><li>' . join('</li><li>', $summary) . '</li></ul>';

	$message = '<h1>Cron report</h1><p>Cron ran at ' . date(DATE_RFC822) . '</p>' . 
		'<p>URL: <tt>' . SimpleSAML_Utilities::selfURL() . '</tt></p>' .
		'<p>Tag: ' . $_REQUEST['tag'] . "</p>\n\n" . $statustext;

	$toaddress = $config->getString('technicalcontact_email', 'na@example.org');
	if($toaddress == 'na@example.org') {		
		SimpleSAML_Logger::error('Cron - Could not send email. [technicalcontact_email] not set in config.');
	} else {
		$email = new SimpleSAML_XHTML_EMail($toaddress, 'simpleSAMLphp cron report', 'no-reply@simplesamlphp.com');
		$email->setBody($message);
		$email->send();
	}
	
}

#$t = new SimpleSAML_XHTML_Template($config, 'modinfo:modlist.php');
#$t->data['modules'] = $modinfo;
#$t->show();

?>