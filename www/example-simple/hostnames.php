<?php

require_once('../_include.php');

require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XML/SAML20/AuthnRequest.php');
require_once('SimpleSAML/XML/SAML20/AuthnResponse.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPRedirect.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPPost.php');
require_once('SimpleSAML/XHTML/Template.php');

/* Load simpleSAMLphp, configuration */
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance(true);

/* Check if valid local session exists.. */
if (!isset($session) || !$session->isValid('login-admin') ) {
	SimpleSAML_Utilities::redirect('/' . $config->getValue('baseurlpath') . 'auth/login-admin.php',
		array('RelayState' => SimpleSAML_Utilities::selfURL())
	);
}





$attributes = array();

$attributes['selfURLhost'] = array(SimpleSAML_Utilities::selfURLhost());
$attributes['selfURLNoQuery'] = array(SimpleSAML_Utilities::selfURLNoQuery());
$attributes['selfURL'] = array(SimpleSAML_Utilities::selfURL());

$attributes['HTTP_HOST'] = array($_SERVER['HTTP_HOST']);
$attributes['HTTPS'] = array($_SERVER['HTTPS']);
$attributes['SERVER_PROTOCOL'] = array($_SERVER['SERVER_PROTOCOL']);
$attributes['SERVER_PORT'] = array($_SERVER['SERVER_PORT']);




$et = new SimpleSAML_XHTML_Template($config, 'status.php');

$et->data['header'] = 'SimpleSAMLphp Diagnostics';
$et->data['remaining'] = 'na';
$et->data['attributes'] = $attributes;
$et->data['valid'] = 'na';
$et->data['logout'] = '';

$et->show();


?>