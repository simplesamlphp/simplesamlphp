<?php

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . '../../_include.php');

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Session.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XHTML/Template.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Metadata/MetaDataStorageHandler.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

$session = SimpleSAML_Session::getInstance();

SimpleSAML_Logger::info('SAML2.0 - SP.idpDisco: Accessing SAML 2.0 discovery service');

if (!$config->getValue('enable.saml20-sp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');

	
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

	SimpleSAML_Logger::info('SAML2.0 - SP.idpDisco: Choice made [ ' . $_GET['idpentityid'] . '] Setting preferedidp cookie.');

	$idpentityid = $_GET['idpentityid'];
	setcookie('preferedidp',$idpentityid,time()+60*60*24*90); // set cookie valid 90 days
	
	$returnurl = SimpleSAML_Utilities::addURLparameter($return, $returnidparam . '=' . $idpentityid);
	SimpleSAML_Utilities::redirect($returnurl);
	
}

try {
	$idplist = $metadata->getList('saml20-idp-remote');
	$preferredidp = $metadata->getPreferredEntityIdFromCIDRhint('saml20-idp-remote', $_SERVER['REMOTE_ADDR']);
	
	if (!empty($preferredidp))
		SimpleSAML_Logger::info('SAML2.0 - SP.idpDisco: Preferred IdP from CIDR hint [ ' . $preferredidp . '].');
	
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}

if (!empty($_COOKIE['preferedidp'])) {
	$preferredidp = $_COOKIE['preferedidp'];
	SimpleSAML_Logger::info('SAML2.0 - SP.idpDisco: Preferred IdP overridden from cookie [ ' . $preferredidp . '].');
}


/**
 * Make use of an XHTML template to present the select IdP choice to the user.
 * Currently the supported options is either a drop down menu or a list view.
 */
$templatefile = ($config->getValue('idpdisco.layout') == 'dropdown' ? 'selectidp-dropdown.php' : 'selectidp-links.php');
$t = new SimpleSAML_XHTML_Template($config, $templatefile);
$t->data['header'] = 'Select your identity provider';
$t->data['idplist'] = $idplist;
$t->data['preferredidp'] = $preferredidp;

if ($config->getValue('idpdisco.layout') == 'dropdown') {
	$t->data['return']= $return;
	$t->data['returnIDParam'] = $returnidparam;
	$t->data['entityID'] = $spentityid;
	$t->data['urlpattern'] = htmlentities(SimpleSAML_Utilities::selfURLNoQuery());
	$t->show();

} else {
	$t->data['urlpattern'] = htmlentities(SimpleSAML_Utilities::selfURL() . '&idpentityid=');
	$t->show();
}


?>