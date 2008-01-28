<?php

require_once('../../_include.php');


require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/XHTML/Template.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XML/Shib13/AuthnRequest.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();


$session = SimpleSAML_Session::getInstance();
		

/*
 * Incomming URL parameters
 *
 * idpentityid 		The entityid of the wanted IdP to authenticate with. If not provided will use default.
 * spentityid		The entityid of the SP config to use. If not provided will use default to host.
 * 
 */

try {

	$idpentityid = isset($_GET['idpentityid']) ? $_GET['idpentityid'] : $config->getValue('default-shib13-idp') ;
	$spentityid = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID('shib13-sp-hosted');

} catch (Exception $exception) {

	$et = new SimpleSAML_XHTML_Template($config, 'error.php');
	$et->data['message'] = 'Error loading SAML 2.0 metadata';	
	$et->data['e'] = $exception;	
	$et->show();
	exit(0);
}



if (!isset($session) || !$session->isValid('shib13') ) {
	
	if ($idpentityid == null) {
	
		$returnURL = urlencode(SimpleSAML_Utilities::selfURL());
		$discservice = '/' . $config->getValue('baseurlpath') . 'shib13/sp/idpdisco.php?entityID=' . $spentityid . 
			'&return=' . $returnURL . '&returnIDParam=idpentityid';
		SimpleSAML_Utilities::redirect($discservice);
		
	}
	
	
	try {
		$ar = new SimpleSAML_XML_Shib13_AuthnRequest($config, $metadata);
		$ar->setIssuer($spentityid);	
		if(isset($_GET['RelayState'])) 
			$ar->setRelayState($_GET['RelayState']);

		$url = $ar->createRedirect($idpentityid);
		SimpleSAML_Utilities::redirect($url);
	
	} catch(Exception $exception) {
		
		$et = new SimpleSAML_XHTML_Template($config, 'error.php');

		$et->data['message'] = 'Some error occured when trying to issue the authentication request to the IdP.';	
		$et->data['e'] = $exception;
		
		$et->show();

	}

} else {

	
	$relaystate = $session->getRelayState();
	
	if (isset($relaystate) && !empty($relaystate)) {
		SimpleSAML_Utilities::redirect($relaystate);
	} else {
		$et = new SimpleSAML_XHTML_Template($config, 'error.php');

		$et->data['message'] = 'Could not get relay state, do not know where to send the user.';	
		$et->data['e'] = new Exception();
		
		$et->show();

	
	}

}




?>