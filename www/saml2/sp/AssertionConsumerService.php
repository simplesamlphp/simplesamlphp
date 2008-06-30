<?php

require_once('../../_include.php');

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
$session = SimpleSAML_Session::getInstance();

SimpleSAML_Logger::info('SAML2.0 - SP.AssertionConsumerService: Accessing SAML 2.0 SP endpoint AssertionConsumerService');

if (!$config->getValue('enable.saml20-sp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');

if (empty($_POST['SAMLResponse'])) 
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'ACSPARAMS', $exception);

	
try {
	
	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

	$binding = new SimpleSAML_Bindings_SAML20_HTTPPost($config, $metadata);
	$authnResponse = $binding->decodeResponse($_POST);
	
	$result = $authnResponse->process();

	/* Fetch the request information if it exists, fall back to RelayState if not. */
	$requestId = $authnResponse->getInResponseTo();
	$info = $session->getData('SAML2:SP:SSO:Info', $requestId);
	if($info === NULL) {
		/* Fall back to RelayState. */
		$info = array();
		$info['RelayState'] = $authnResponse->getRelayState();
		if(!isset($info['RelayState'])) {
			/* RelayState missing. */
			SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
		}
	}

	/* Check status code, call OnError handler on error. */
	if($result === FALSE) {
		/* Not successful. */
		$statusCode = $authnResponse->findstatus();
		if(array_key_exists('OnError', $info)) {
			/* We have an error handler. Return the error to it. */
			SimpleSAML_Utilities::redirect($info['OnError'], array('StatusCode' => $statusCode));
		} else {
			/* We don't have an error handler. Show an error page. */
			SimpleSAML_Utilities::fatalError($session->getTrackID(), 'RESPONSESTATUSNOSUCCESS',
				new Exception("Status = " . $statusCode));
		}
	}

	/* Successful authentication. */

	SimpleSAML_Logger::info('SAML2.0 - SP.AssertionConsumerService: Successfully created local session from Authentication Response');

	/* The response should include the entity id of the IdP. */
	$idpentityid = $authnResponse->findIssuer();
	
	$idpmetadata = $metadata->getMetaData($idpentityid, 'saml20-idp-remote');
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
	SimpleSAML_Logger::stats('saml20-sp-SSO ' . $metadata->getMetaDataCurrentEntityID() . ' ' . $idpentityid . ' ' . $realmstr);
	
	
	$afilter->processFilter($idpmetadata, $spmetadata);
			
	$session->setAttributes($afilter->getAttributes());
	SimpleSAML_Logger::info('SAML2.0 - SP.AssertionConsumerService: Completed attribute handling');
	
	
	$session->setIdP($idpentityid);
		
		

	SimpleSAML_Utilities::redirect($info['RelayState']);

} catch(Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'PROCESSASSERTION', $exception);
}


?>