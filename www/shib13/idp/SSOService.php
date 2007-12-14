<?php


require_once('../../../www/_include.php');


require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/XML/MetaDataStore.php');
require_once('SimpleSAML/XML/AttributeFilter.php');
require_once('SimpleSAML/XML/Shib13/AuthnRequest.php');
require_once('SimpleSAML/XML/Shib13/AuthnResponse.php');
require_once('SimpleSAML/Bindings/Shib13/HTTPPost.php');

require_once('SimpleSAML/XHTML/Template.php');


$config = SimpleSAML_Configuration::getInstance();
$metadata = new SimpleSAML_XML_MetaDataStore($config);

$idpentityid = $metadata->getMetaDataCurrentEntityID('shib13-idp-hosted');
$idpmeta = $metadata->getMetaDataCurrent('shib13-idp-hosted');

$requestid = null;
$session = null;


if (isset($_GET['shire'])) {


	try {
		$authnrequest = new SimpleSAML_XML_Shib13_AuthnRequest($config, $metadata);
		$authnrequest->parseGet($_GET);
		
		$session = $authnrequest->createSession();
	
		$requestid = $authnrequest->getRequestID();

		//$session->setShibAuthnRequest($authnrequest);

		

	
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
		$authnrequest = $session->getShibAuthnRequest();
		
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

	$relaystate = SimpleSAML_Utilities::selfURLNoQuery() . '?RequestID=' . urlencode($requestid);
	$authurl = SimpleSAML_Utilities::addURLparameter('/' . $config->getValue('baseurlpath') . $idpmeta['auth'], 
		'RelayState=' . urlencode($relaystate));
	header('Location: ' . $authurl);
	exit(0);
} else {

	try {
	
		//$session->add_sp_session($authnrequest->getIssuer());


		//$session->setAttribute('eduPersonAffiliation', array('student'));



		/*
		 * Filtering attributes.
		 */
		$afilter = new SimpleSAML_XML_AttributeFilter($config, $session->getAttributes());
		if (isset($spmetadata['attributemap'])) {
			$afilter->namemap($spmetadata['attributemap']);
		}
		if (isset($spmetadata['attributes'])) {
			$afilter->filter($spmetadata['attributes']);
		}
		$filteredattributes = $afilter->getAttributes();
		



		// Generating a Shibboleth 1.3 Response.
		$ar = new SimpleSAML_XML_Shib13_AuthnResponse($config, $metadata);
		$authnResponseXML = $ar->generate($idpentityid, $authnrequest->getIssuer(), 
			$requestid, null, $filteredattributes);
		
		
		#echo $authnResponseXML;
		#print_r($authnResponseXML);
		
		//sendResponse($response, $idpentityid, $spentityid, $relayState = null) {
		$httppost = new SimpleSAML_Bindings_Shib13_HTTPPost($config, $metadata);
		
		//echo 'Relaystate[' . $authnrequest->getRelayState() . ']';
		
		$issuer = $authnrequest->getIssuer();
		$shire = $authnrequest->getShire();
		if ($issuer == null || $issuer == '')
			throw new Exception('Could not retrieve issuer of the AuthNRequest (ProviderID)');
		
		$httppost->sendResponse($authnResponseXML, 
			$idpentityid, $issuer, $authnrequest->getRelayState(), $shire);
			
	} catch(Exception $exception) {
		
		$et = new SimpleSAML_XHTML_Template($config, 'error.php');
		
		$et->data['header'] = 'Error sending response to service';
		$et->data['message'] = 'Some error occured when trying to issue the authentication response, and send it back to the SP.';	
		$et->data['e'] = $exception;
		
		$et->show();

	}
	
}


?>