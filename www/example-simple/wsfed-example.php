<?php

require_once('../_include.php');

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Session.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XHTML/Template.php');


$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

if (!$session->isValid('wsfed') ) {
	SimpleSAML_Utilities::redirect(
		'/' . $config->getBaseURL() . 'wsfed/sp/initSSO.php',
		array('RelayState' => SimpleSAML_Utilities::selfURL())
	);
}

$attributes = $session->getAttributes();

$t = new SimpleSAML_XHTML_Template($config, 'status.php', 'attributes.php');

$t->data['header'] = 'WS-Fed SP Demo Example';
$t->data['remaining'] = $session->remainingTime();
$t->data['sessionsize'] = $session->getSize();
$t->data['attributes'] = $attributes;
$t->data['icon'] = 'bino.png';
$t->data['logout'] = '[ <a href="/' . $config->getBaseURL() . 'wsfed/sp/initSLO.php?RelayState=/' . 
	$config->getBaseURL() . 'logout.html">Logout</a> ]';
$t->show();


?>
