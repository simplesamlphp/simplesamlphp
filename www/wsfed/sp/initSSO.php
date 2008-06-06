<?php
/**
 * WS-Federation/ADFS PRP protocol support for simpleSAMLphp.
 *
 * The initSSO handler relays an internal request from a simpleSAMLphp
 * Service Provider as a WS-Federation Resource Partner using the Passive
 * Requestor Profile (PRP) to an Account Partner.
 *
 * @author Hans Zandbelt, SURFnet BV. <hans.zandbelt@surfnet.nl>
 * @package simpleSAMLphp
 * @version $Id$
 */

require_once('../../_include.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();

SimpleSAML_Logger::info('WS-Fed - SP.initSSO: Accessing WS-Fed SP initSSO script');

if (!$config->getValue('enable.wsfed-sp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');

if (empty($_GET['RelayState'])) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
}

try {

	$idpentityid = isset($_GET['idpentityid']) ? $_GET['idpentityid'] : $config->getValue('default-wsfed-idp') ;
	$spentityid = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID('wsfed-sp-hosted');

} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}

if ($idpentityid == null) {

	SimpleSAML_Logger::info('WS-Fed - SP.initSSO: No chosen or default IdP, go to WSFeddisco');

	SimpleSAML_Utilities::redirect('/' . $config->getBaseURL() . 'wsfed/sp/idpdisco.php', array(
		'entityID' => $spentityid,
		'return' => SimpleSAML_Utilities::selfURL(),
		'returnIDParam' => 'idpentityid')
	);
}

try {
	$relaystate = $_GET['RelayState'];
	
	$idpmeta = $metadata->getMetaData($idpentityid, 'wsfed-idp-remote');
	$spmeta = $metadata->getMetaData($spentityid, 'wsfed-sp-hosted');
	
	$url = $idpmeta['prp'] .
		'?wa=wsignin1.0' .
		'&wct=' . gmdate("Y-m-d\TH:i:s\Z", time()) .
		'&wtrealm=' . $spmeta['realm'] .
		'&wctx=' . urlencode($relaystate);

	SimpleSAML_Utilities::redirect($url);
	
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'CREATEREQUEST', $exception);
}
