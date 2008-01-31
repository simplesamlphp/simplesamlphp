<?php

require_once('../../_include.php');


require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/XHTML/Template.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();


$session = SimpleSAML_Session::getInstance();
		
try {

	if (!isset($_GET['entityID'])) throw new Exception('Missing parameter: entityID');
	if (!isset($_GET['return'])) throw new Exception('Missing parameter: return');
	if (!isset($_GET['returnIDParam'])) throw new Exception('Missing parameter: returnIDParam');

	$spentityid = $_GET['entityID'];
	$return = $_GET['return'];
	$returnidparam = $_GET['returnIDParam'];
	
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'DISCOPARAMS', $exception);
}


if (isset($_GET['idpentityid'])) {

	$idpentityid = $_GET['idpentityid'];
	setcookie('preferedidp',$idpentityid,time()+60*60*24*90); // set cookie valid 90 days
	
	$returnurl = SimpleSAML_Utilities::addURLparameter($return, $returnidparam . '=' . $idpentityid);
	SimpleSAML_Utilities::redirect($returnurl);
	
}

try {
	$idplist = $metadata->getList('shib13-idp-remote');
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}

if ($config->getValue('idpdisco.layout') == 'dropdown') {
	$t = new SimpleSAML_XHTML_Template($config, 'selectidp-dropdown.php');
	$t->data['header'] = 'Select your identity provider';
	$t->data['idplist'] = $idplist;
	$t->data['return']= $return;
	$t->data['returnIDParam'] = $returnidparam;
	$t->data['entityID'] = $spentityid;
	$t->data['preferedidp'] = (!empty($_COOKIE['preferedidp'])) ? $_COOKIE['preferedidp'] : null;
	$t->data['urlpattern'] = htmlentities(SimpleSAML_Utilities::selfURLNoQuery());
	$t->show();
}
else
{
	$t = new SimpleSAML_XHTML_Template($config, 'selectidp-links.php');
	$t->data['header'] = 'Select your identity provider';
	$t->data['idplist'] = $idplist;
	$t->data['urlpattern'] = htmlentities(SimpleSAML_Utilities::selfURL() . '&idpentityid=');
	$t->show();
}


?>