<?php

require_once('../../_include.php');


require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/XHTML/Template.php');
require_once('SimpleSAML/XML/MetaDataStore.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = new SimpleSAML_XML_MetaDataStore($config);


$session = SimpleSAML_Session::getInstance();
		
try {

	if (!isset($_GET['entityID'])) throw new Exception('Missing parameter: entityID');
	if (!isset($_GET['return'])) throw new Exception('Missing parameter: return');
	if (!isset($_GET['returnIDParam'])) throw new Exception('Missing parameter: returnIDParam');

	$spentityid = $_GET['entityID'];
	$return = $_GET['return'];
	$returnidparam = $_GET['returnIDParam'];
	
} catch (Exception $exception) {

	$et = new SimpleSAML_XHTML_Template($config, 'error.php');
	$et->data['message'] = 'Error getting required parameters for IdP Discovery Service';	
	$et->data['e'] = $exception;	
	$et->show();
	exit(0);
}


if (isset($_GET['idpentityid'])) {

	$idpentityid = $_GET['idpentityid'];

	$returnurl = SimpleSAML_Utilities::addURLparameter($return, $returnidparam . '=' . $idpentityid);
	SimpleSAML_Utilities::redirect($returnurl);
}


$idplist = $metadata->getList('shib13-idp-remote');


$t = new SimpleSAML_XHTML_Template($config, 'selectidp.php');
$t->data['header'] = 'Select your identity provider';
$t->data['idplist'] = $idplist;
$t->data['urlpattern'] = htmlentities(SimpleSAML_Utilities::selfURL() . '&idpentityid=');
$t->show();



?>