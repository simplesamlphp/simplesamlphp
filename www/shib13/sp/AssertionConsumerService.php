<?php

require_once('../../_include.php');

$config = SimpleSAML_Configuration::getInstance();

$session = SimpleSAML_Session::getInstance();


/**
 * Finish login operation.
 *
 * This helper function finishes a login operation and redirects the user back to the page which
 * requested the login.
 *
 * @param array $authProcState  The state of the authentication process.
 */
function finishLogin($authProcState) {
	assert('is_array($authProcState)');
	assert('array_key_exists("Attributes", $authProcState)');
	assert('array_key_exists("core:shib13-sp:NameID", $authProcState)');
	assert('array_key_exists("core:shib13-sp:SessionIndex", $authProcState)');
	assert('array_key_exists("core:shib13-sp:TargetURL", $authProcState)');
	assert('array_key_exists("Source", $authProcState)');
	assert('array_key_exists("entityid", $authProcState["Source"])');

	global $session;

	/* Update the session information */
	$session->doLogin('shib13');
	$session->setAttributes($authProcState['Attributes']);
	$session->setNameID($authProcState['core:shib13-sp:NameID']);
	$session->setSessionIndex($authProcState['core:shib13-sp:SessionIndex']);
	$session->setIdP($authProcState['Source']['entityid']);

	SimpleSAML_Utilities::redirect($authProcState['core:shib13-sp:TargetURL']);
}


SimpleSAML_Logger::info('Shib1.3 - SP.AssertionConsumerService: Accessing Shibboleth 1.3 SP endpoint AssertionConsumerService');

if (!$config->getBoolean('enable.shib13-sp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');

if (array_key_exists(SimpleSAML_Auth_ProcessingChain::AUTHPARAM, $_REQUEST)) {
	/* We have returned from the authentication processing filters. */

	$authProcId = $_REQUEST[SimpleSAML_Auth_ProcessingChain::AUTHPARAM];
	$authProcState = SimpleSAML_Auth_ProcessingChain::fetchProcessedState($authProcId);
	finishLogin($authProcState);
}

if (empty($_POST['SAMLResponse'])) 
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'ACSPARAMS', $exception);

try {

	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

	$binding = new SimpleSAML_Bindings_Shib13_HTTPPost($config, $metadata);
	$authnResponse = $binding->decodeResponse($_POST);

	$authnResponse->validate();

	/* Successfully authenticated. */

	$idpmetadata = $metadata->getMetadata($authnResponse->getIssuer(), 'shib13-idp-remote');

	SimpleSAML_Logger::info('Shib1.3 - SP.AssertionConsumerService: Successful authentication to IdP ' . $idpmetadata['entityid']);


	SimpleSAML_Logger::stats('shib13-sp-SSO ' . $metadata->getMetaDataCurrentEntityID('shib13-sp-hosted') . ' ' . $idpmetadata['entityid'] . ' NA');


	$relayState = $authnResponse->getRelayState();
	if (!isset($relayState)) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
	}

	$spmetadata = $metadata->getMetaData(NULL, 'shib13-sp-hosted');

	/* Begin module attribute processing */
	$pc = new SimpleSAML_Auth_ProcessingChain($idpmetadata, $spmetadata, 'sp');

	$authProcState = array(
		'core:shib13-sp:NameID' => $authnResponse->getNameID(),
		'core:shib13-sp:SessionIndex' => $authnResponse->getSessionIndex(),
		'core:shib13-sp:TargetURL' => $relayState,
		'ReturnURL' => SimpleSAML_Utilities::selfURLNoQuery(),
		'Attributes' => $authnResponse->getAttributes(),
		'Destination' => $spmetadata,
		'Source' => $idpmetadata,
		);

	$pc->processState($authProcState);
	/* Since this function returns, processing has completed and attributes have
	 * been updated.
	 */

	finishLogin($authProcState);

} catch(Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'GENERATEAUTHNRESPONSE', $exception);
}


?>