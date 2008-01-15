<?php

require_once('../../_include.php');


require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XML/Shib13/AuthnRequest.php');
require_once('SimpleSAML/Bindings/Shib13/HTTPPost.php');
require_once('SimpleSAML/XHTML/Template.php');

try {
	
	/*
	echo '<pre>';
	print_r($_POST);
	echo '</pre>';
	*/
	$config = SimpleSAML_Configuration::getInstance();
	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
	
	#print_r($metadata->getMetaData('sam.feide.no'));
#	$sr = new SimpleSAML_XML_Shib13_AuthnResponse($config, $metadata);
	
	$binding = new SimpleSAML_Bindings_Shib13_HTTPPost($config, $metadata);
	$authnResponse = $binding->decodeResponse($_POST);

	$xml = $authnResponse->getXML();
	/*
		echo '<pre>';
		echo $xml;
		echo '</pre>';
	*/

	$authnResponse->validate();
	$session = $authnResponse->createSession();
	


	if (isset($session)) {
		$relayState = $authnResponse->getRelayState();
		if (isset($relayState)) {
			SimpleSAML_Utilities::redirect($relayState);
		} else {
			echo 'Could not find RelayState parameter, you are stucked here.';
		}
	} else {
		throw new Exception('Unkown error. Could not get session.');
	}


} catch(Exception $exception) {

	$et = new SimpleSAML_XHTML_Template($config, 'error.php');

	$et->data['message'] = 'Some error occured when trying to issue the authentication request to the IdP.';	
	$et->data['e'] = $exception;
	
	$et->show();


}


?>