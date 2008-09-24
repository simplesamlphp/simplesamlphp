<?php

/**
 * The _include script registers a autoloader for the simpleSAMLphp libraries. It also
 * initializes the simpleSAMLphp config class with the correct path.
 */
require_once('_include.php');


/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

if (! $session->isValid('openid') ) {
	/* Authenticate with an AuthSource. */
	$hints = array('openid' => NULL);
	SimpleSAML_Auth_Default::initLogin('openid', SimpleSAML_Utilities::selfURL(), NULL, $hints);
}

$attributes = $session->getAttributes();

$t = new SimpleSAML_XHTML_Template($config, 'status.php', 'attributes');
$t->data['header'] = '{openid:dictopenid:openidtestpage}';
$t->data['remaining'] = $session->remainingTime();
$t->data['sessionsize'] = $session->getSize();
$t->data['attributes'] = $attributes;
$t->data['icon'] = 'bino.png';
$t->data['logouturl'] = NULL;
$t->show();


?>