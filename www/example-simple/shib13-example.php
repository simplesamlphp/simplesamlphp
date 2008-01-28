<?php

require_once('../_include.php');

require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XHTML/Template.php');

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();


$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();


$session = SimpleSAML_Session::getInstance();

if (!isset($session) || !$session->isValid('shib13') ) {
	
	SimpleSAML_Utilities::redirect(
		'/' . $config->getValue('baseurlpath') .
		'shib13/sp/initSSO.php',
		array('RelayState' => SimpleSAML_Utilities::selfURL())
		);
}

$et = new SimpleSAML_XHTML_Template($config, 'status.php');

$et->data['header'] = 'Shibboleth demo';
$et->data['remaining'] = $session->remainingTime();
$et->data['attributes'] = $session->getAttributes();
$et->data['valid'] = $session->isValid() ? 'Session is valid' : 'Session is invalid';
$et->data['logout'] = 'Shibboleth logout not implemented yet.';

$et->show();


?>
