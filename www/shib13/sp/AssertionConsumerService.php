<?php

require_once('../../_include.php');


require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XML/Shib13/AuthnRequest.php');
require_once('SimpleSAML/Bindings/Shib13/HTTPPost.php');
require_once('SimpleSAML/XHTML/Template.php');

$config = SimpleSAML_Configuration::getInstance();

$session = SimpleSAML_Session::getInstance(TRUE);


SimpleSAML_Logger::info('Shib1.3 - SP.AssertionConsumerService: Accessing Shibboleth 1.3 SP endpoint AssertionConsumerService');

if (!$config->getValue('enable.shib13-sp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');

if (empty($_POST['SAMLResponse'])) 
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'ACSPARAMS', $exception);

try {

	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

	$binding = new SimpleSAML_Bindings_Shib13_HTTPPost($config, $metadata);
	$authnResponse = $binding->decodeResponse($_POST);

	$authnResponse->validate();
	$session = $authnResponse->createSession();


	if (isset($session)) {

		SimpleSAML_Logger::notice('Shib1.3 - SP.AssertionConsumerService: Successfully created local session from Authentication Response');

		/**
		 * Make a log entry in the statistics for this SSO login.
		 */
		$tempattr = $session->getAttributes();
		$realmattr = $config->getValue('statistics.realmattr', null);
		$realmstr = 'NA';
		if (!empty($realmattr)) {
			if (array_key_exists($realmattr, $tempattr) && is_array($tempattr[$realmattr]) ) {
				$realmstr = $tempattr[$realmattr][0];
			} else {
				SimpleSAML_Logger::warning('Could not get realm attribute to log [' . $realmattr. ']');
			}
		} 
		SimpleSAML_Logger::stats('shib13-sp-SSO ' . $metadata->getMetaDataCurrentEntityID('shib13-sp-hosted') . ' ' . $session->getIdP() . ' ' . $realmstr);


	
		$relayState = $authnResponse->getRelayState();
		if (isset($relayState)) {
			SimpleSAML_Utilities::redirect($relayState);
		} else {
			SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
		}
	} else {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOSESSION');
	}


} catch(Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'GENERATEAUTHNRESPONSE', $exception);
}


?>