<?php


require_once('../../../www/_include.php');


require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Logger.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XML/AttributeFilter.php');
require_once('SimpleSAML/XML/SAML20/AuthnRequest.php');
require_once('SimpleSAML/XML/SAML20/AuthnResponse.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPRedirect.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPPost.php');
require_once('SimpleSAML/XHTML/Template.php');


$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance(true);

$logger = new SimpleSAML_Logger();

$idpentityid = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
$idpmeta = $metadata->getMetaDataCurrent('saml20-idp-hosted');

$requestid = null;

$logger->log(LOG_INFO, $session->getTrackID(), 'SAML2.0', 'IdP.SSOService', 'EVENT', 'Access', 'Accessing SAML 2.0 IdP endpoint SSOService');


if (isset($_GET['SAMLRequest'])) {


	try {
		$binding = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
		$authnrequest = $binding->decodeRequest($_GET);
		
		$session = $authnrequest->createSession();
		$requestid = $authnrequest->getRequestID();
		
		
		if ($binding->validateQuery($authnrequest->getIssuer(),'IdP')) {
			$logger->log(LOG_INFO, $session->getTrackID(), 'SAML2.0', 'IdP.SSOService', 'AuthnRequest', $requestid, 'Valid signature found');
		}
		
		$session->setAuthnRequest($requestid, $authnrequest);
		
		$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'IdP.SSOService', 'AuthnRequest', 
			array($authnrequest->getIssuer(), $requestid), 
			'Incomming Authentication request');
	
	} catch(Exception $exception) {
		
		$et = new SimpleSAML_XHTML_Template($config, 'error.php');
		
		$et->data['header'] = 'Error getting incomming request';
		$et->data['message'] = 'Something bad happened when simpleSAML got the incomming authentication request';	
		$et->data['e'] = $exception;
		
		$et->show();
		exit(0);
	}

} elseif(isset($_GET['RequestID'])) {

	try {

		$requestid = $_GET['RequestID'];
		/* Remove any "magic" quotes that php may have added. */
		if(get_magic_quotes_gpc()) {
			$requestid = stripslashes($requestid);
		}

		$session = SimpleSAML_Session::getInstance();
		$authnrequest = $session->getAuthnRequest($requestid);
		
		$logger->log(LOG_INFO, $session->getTrackID(), 'SAML2.0', 'IdP.SSOService', 'EVENT', $requestid, 'Got incomming RequestID');
		
		
		if (!$authnrequest) throw new Exception('Could not retrieve cached RequestID = ' . $requestid);
		
	} catch(Exception $exception) {
		
		$et = new SimpleSAML_XHTML_Template($config, 'error.php');
		
		$et->data['header'] = 'Error retrieving authnrequest cache';
		$et->data['message'] = 'simpleSAML cannot find the authnrequest that it earlier stored.';	
		$et->data['e'] = $exception;
		
		$et->show();
		exit(0);

	}
	
	
	/*	
	$authnrequest = new SimpleSAML_XML_SAML20_AuthnRequest($config, $metadata);
	$authnrequest->setXML($authnrequestXML);
	*/
	


} else {

	echo 'You must either provide a SAML Request message or a RequestID on this interface.';
	exit(0);

}




if (!$session->isAuthenticated() ) {

	$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'IdP.SSOService', 'AuthNext', $idpmeta['auth'], 
		'Will go to authentication module ' . $idpmeta['auth']);

	$relaystate = SimpleSAML_Utilities::selfURLNoQuery() .
		'?RequestID=' . urlencode($requestid);
	$authurl = '/' . $config->getValue('baseurlpath') . $idpmeta['auth'];

	SimpleSAML_Utilities::redirect($authurl,
		array('RelayState' => $relaystate));
} else {

	try {
	
	
		$spentityid = $authnrequest->getIssuer();
		$spmetadata = $metadata->getMetaData($spentityid, 'saml20-sp-remote');
		
		/*
		 * Dealing with attribute release consent.
		 */
	
		if (array_key_exists('requireconsent', $idpmeta)
		    && $idpmeta['requireconsent']) {
			
			if (!isset($_GET['consent'])) {

				$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'IdP.SSOService', 'Consent', 'request', 
					'Requires consent from user for attribute release');

				$t = new SimpleSAML_XHTML_Template($config, 'consent.php');
				$t->data['header'] = 'Consent';
				$t->data['spentityid'] = $spentityid;
				$t->data['attributes'] = $session->getAttributes();
				$t->data['consenturl'] = SimpleSAML_Utilities::addURLparameter(SimpleSAML_Utilities::selfURL(), 'consent=1');
				$t->show();
				exit(0);
				
			} else {
			
				$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'IdP.SSOService', 'ConsentOK', '-', 
					'Got consent from user');
			
			}
			
		}
	
		// Adding this service provider to the list of sessions.
		$session->add_sp_session($spentityid);


		
		$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'IdP.SSOService', 'AuthnResponse', $spentityid, 
			'Sending back AuthnResponse');
		

		/*
		 * Filtering attributes.
		 */
		$ar = new SimpleSAML_XML_SAML20_AuthnResponse($config, $metadata);
		$afilter = new SimpleSAML_XML_AttributeFilter($config, $session->getAttributes());
		if (isset($spmetadata['attributemap'])) {
			$afilter->namemap($spmetadata['attributemap']);
		}
		if (isset($spmetadata['attributes'])) {
			$afilter->filter($spmetadata['attributes']);
		}
		$filteredattributes = $afilter->getAttributes();
		
		// Generate an SAML 2.0 AuthNResponse message
		$authnResponseXML = $ar->generate($idpentityid, $spentityid, 
			$requestid, null, $filteredattributes);
	
		// Sending the AuthNResponse using HTTP-Post SAML 2.0 binding
		$httppost = new SimpleSAML_Bindings_SAML20_HTTPPost($config, $metadata);
		$httppost->sendResponse($authnResponseXML, 
			$idpentityid, $authnrequest->getIssuer(), $authnrequest->getRelayState());
		
		
		
	} catch(Exception $exception) {
		
		$et = new SimpleSAML_XHTML_Template($config, 'error.php');
		
		$et->data['header'] = 'Error sending response to service';
		$et->data['message'] = 'Some error occured when trying to issue the authentication response, and send it back to the SP.';	
		$et->data['e'] = $exception;
		
		$et->show();

	}
	
}


?>
