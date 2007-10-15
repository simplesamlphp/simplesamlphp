<?php


require_once('../../../www/_include.php');


require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/XML/MetaDataStore.php');
require_once('SimpleSAML/XML/SAML20/AuthnRequest.php');
require_once('SimpleSAML/XML/SAML20/AuthnResponse.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPRedirect.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPPost.php');
require_once('SimpleSAML/XHTML/Template.php');


session_start();

$config = SimpleSAML_Configuration::getInstance();
$metadata = new SimpleSAML_XML_MetaDataStore($config);

$idpentityid = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
$idpmeta = $metadata->getMetaDataCurrent('saml20-idp-hosted');

$requestid = null;
$session = null;


if (isset($_GET['SAMLRequest'])) {


	try {
		$binding = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
		$authnrequest = $binding->decodeRequest($_GET);
		
		$session = $authnrequest->createSession();
	
		$requestid = $authnrequest->getRequestID();
		
	
		
		$session->setAuthnRequest($requestid, $authnrequest);
	
	} catch(Exception $exception) {
		
		$et = new SimpleSAML_XHTML_Template($config, 'error.php');
		
		$et->data['header'] = 'Error getting incomming request';
		$et->data['message'] = 'Something bad happened when simpleSAML got the incomming authentication request';	
		$et->data['e'] = $exception;
		
		$et->show();

	}

} elseif(isset($_GET['RequestID'])) {

	try {

		$requestid = $_GET['RequestID'];
		$session = SimpleSAML_Session::getInstance();
		$authnrequest = $session->getAuthnRequest($requestid);
		
		if (!$authnrequest) throw new Exception('Could not retrieve cached RequestID = ' . $requestid);
		
	} catch(Exception $exception) {
		
		$et = new SimpleSAML_XHTML_Template($config, 'error.php');
		
		$et->data['header'] = 'Error retrieving authnrequest cache';
		$et->data['message'] = 'simpleSAML cannot find the authnrequest that it earlier stored.';	
		$et->data['e'] = $exception;
		
		$et->show();

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

	$relaystate = SimpleSAML_Utilities::selfURLNoQuery() . '?RelayState=' . urlencode($_GET['RelayState']) .
		'&RequestID=' . urlencode($requestid);
	$authurl = SimpleSAML_Utilities::addURLparameter('/' . $config->getValue('baseurlpath') . $idpmeta['auth'], 
		'RelayState=' . urlencode($relaystate));
	header('Location: ' . $authurl);
	exit(0);
} else {

	try {
	
	
		$spentityid = $authnrequest->getIssuer();
		//$spmetadata = $metadata->getMetaData($spentityid, 'saml20-sp-remote');
		
		
	
		if ($idpmeta['requireconsent']) {
			
			if (!isset($_GET['consent'])) {
			
				$t = new SimpleSAML_XHTML_Template($config, 'consent.php');
				$t->data['header'] = 'Consent';
				$t->data['spentityid'] = $spentityid;
				$t->data['attributes'] = $session->getAttributes();
				$t->data['consenturl'] = SimpleSAML_Utilities::addURLparameter(SimpleSAML_Utilities::selfURL(), 'consent=1');
				$t->show();
				exit(0);
				
			}
			
		}
	
	
		$session->add_sp_session($spentityid);

		$ar = new SimpleSAML_XML_SAML20_AuthnResponse($config, $metadata);
		$authnResponseXML = $ar->generate($idpentityid, $spentityid, 
			$requestid, null, $session->getAttributes());
		
		#echo $authnResponseXML;
		#print_r($session);
		
		//sendResponse($response, $idpentityid, $spentityid, $relayState = null) {
		$httppost = new SimpleSAML_Bindings_SAML20_HTTPPost($config, $metadata);
		
		//echo 'Relaystate[' . $authnrequest->getRelayState() . ']';
		
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