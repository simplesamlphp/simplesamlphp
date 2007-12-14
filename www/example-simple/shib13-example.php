<?php

require_once('../_include.php');

require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/XML/MetaDataStore.php');
require_once('SimpleSAML/XHTML/Template.php');


$config = SimpleSAML_Configuration::getInstance();
$metadata = new SimpleSAML_XML_MetaDataStore($config);

$session = SimpleSAML_Session::getInstance();

if (!isset($session) || !$session->isValid() ) {
	
	header('Location: /' . $config->getValue('baseurlpath') . 'shib13/sp/initSSO.php?RelayState=' . urlencode(SimpleSAML_Utilities::selfURL()));
		// . '&idpentityid=' . $idpentityid );
	exit(0);
}

$et = new SimpleSAML_XHTML_Template($config, 'status.php');

$et->data['header'] = 'Shibboleth demo';
$et->data['remaining'] = $session->remainingTime();
$et->data['attributes'] = $session->getAttributes();
$et->data['valid'] = $session->isValid() ? 'Session is valid' : 'Session is invalid';
$et->data['logout'] = 'Shibboleth logout not implemented yet.';

$et->show();


?>
