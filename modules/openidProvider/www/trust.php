<?php

if (!is_string($_REQUEST['StateID'])) {
	throw new SimpleSAML_Error_BadRequest('Missing StateID-parameter.');
}
$StateID = $_REQUEST['StateID'];

$server = sspmod_openidProvider_Server::getInstance();
$state = $server->loadState($_REQUEST['StateID']);

$trustRoot = $state['request']->trust_root;
$identity = $server->getIdentity();
if ($identity === NULL) {
	$server->processRequest($state);
}


if (isset($_REQUEST['TrustYes'])) {
	if (isset($_REQUEST['TrustRemember'])) {
		$server->addTrustRoot($identity, $trustRoot);
	}

	$state['TrustResponse'] = TRUE;
	$server->processRequest($state);
}

if (isset($_REQUEST['TrustNo'])) {
	$state['TrustResponse'] = FALSE;
	$server->processRequest($state);
}

$globalConfig = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($globalConfig, 'openidProvider:trust.tpl.php');
$t->data['StateID'] = $_REQUEST['StateID'];
$t->data['trustRoot'] = $trustRoot;
$t->show();
