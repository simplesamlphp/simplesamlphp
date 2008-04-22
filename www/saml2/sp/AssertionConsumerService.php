<?php

require_once('../../_include.php');

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Configuration.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Session.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Logger.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XML/AttributeFilter.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XML/SAML20/AuthnRequest.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Bindings/SAML20/HTTPPost.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XHTML/Template.php');

/**
 * This SAML 2.0 endpoint is the endpoint at the SAML 2.0 SP that takes an Authentication Response
 * as HTTP-POST in, and parses and processes it before it redirects the use to the RelayState.
 *
 * @author Andreas Aakre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 * @abstract
 */

$config = SimpleSAML_Configuration::getInstance();

/* Get the session object for the user. Create a new session if no session
 * exists for this user.
 */
$session = SimpleSAML_Session::getInstance(TRUE);

SimpleSAML_Logger::info('SAML2.0 - SP.AssertionConsumerService: Accessing SAML 2.0 SP endpoint AssertionConsumerService');

if (!$config->getValue('enable.saml20-sp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');

if (empty($_POST['SAMLResponse'])) 
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'ACSPARAMS', $exception);

	
try {
	
	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

	$binding = new SimpleSAML_Bindings_SAML20_HTTPPost($config, $metadata);
	$authnResponse = $binding->decodeResponse($_POST);
	
	$authnResponse->process();

	SimpleSAML_Logger::info('SAML2.0 - SP.AssertionConsumerService: Successfully created local session from Authentication Response');
	
	
	$idpmetadata = $metadata->getMetaData($session->getIdP(), 'saml20-idp-remote');
	$spmetadata = $metadata->getMetaDataCurrent();
	
	
	/*
	 * Attribute handling
	 */
	$attributes = $session->getAttributes();
	$afilter = new SimpleSAML_XML_AttributeFilter($config, $attributes);
	$afilter->process($idpmetadata, $spmetadata);
	
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
	SimpleSAML_Logger::stats('saml20-sp-SSO ' . $metadata->getMetaDataCurrentEntityID() . ' ' . $session->getIdP() . ' ' . $realmstr);
	
	
	$afilter->processFilter($idpmetadata, $spmetadata);
			
	$session->setAttributes($afilter->getAttributes());
	SimpleSAML_Logger::info('SAML2.0 - SP.AssertionConsumerService: Completed attribute handling');
	
	

		
		

	$relayState = $authnResponse->getRelayState();
	if (isset($relayState)) {
		SimpleSAML_Utilities::redirect($relayState);
	} else {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
	}

} catch(Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'PROCESSASSERTION', $exception);
}


?>