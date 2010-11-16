<?php

require_once('../_include.php');

$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

if (!$session->isValid('wsfed') ) {
	SimpleSAML_Utilities::redirect(
		'/' . $config->getBaseURL() . 'wsfed/sp/initSSO.php',
		array('RelayState' => SimpleSAML_Utilities::selfURL())
	);
}

$attributes = $session->getAuthData('wsfed', 'Attributes');

$t = new SimpleSAML_XHTML_Template($config, 'status.php', 'attributes');

$t->data['header'] = '{status:header_wsfed}';
$t->data['remaining'] = $session->getAuthData('wsfed', 'Expire') - time();
$t->data['sessionsize'] = $session->getSize();
$t->data['attributes'] = $attributes;
$t->data['logouturl'] = '/' . $config->getBaseURL() . 'wsfed/sp/initSLO.php?RelayState=/' . $config->getBaseURL() . 'logout.php';
$t->show();


?>
