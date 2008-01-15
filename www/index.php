<?php

require_once('_include.php');


require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/XHTML/Template.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');

$config = SimpleSAML_Configuration::getInstance();


$session = SimpleSAML_Session::getInstance();
	
	
$links = array();

$links[] = array('href' => 'admin/metadata.php', 'text' => 'Meta data overview for your installation. Diagnose your meta data files.');

if ($config->getValue('enable.saml20-sp') === true)
	$links[] = array('href' => 'saml2/sp/metadata.php', 'text' => 'SAML 2.0 Service Provider Metadata (automatically generated)');

if ($config->getValue('enable.saml20-sp') === true)
	$links[] = array('href' => 'example-simple/saml2-example.php', 'text' => 'SAML 2.0 SP example - test logging in through your IdP');

if ($config->getValue('enable.saml20-idp') === true)
	$links[] = array('href' => 'saml2/idp/metadata.php', 'text' => 'SAML 2.0 Identity Provider Metadata (automatically generated)');

if ($config->getValue('enable.shib13-sp') === true)
	$links[] = array('href' => 'example-simple/shib13-example.php', 'text' => 'Shibboleth 1.3 SP example - test logging in through your Shib IdP');


if ($config->getValue('enable.openid-provider') === true)
	$links[] = array('href' => 'openid/provider/server.php', 'text' => 'OpenID Provider site - Alpha version (test code)');


$t = new SimpleSAML_XHTML_Template($config, 'frontpage.php');
$t->data['header'] = 'simpleSAMLphp installation page';
$t->data['links'] = $links;
$t->show();



?>