<?php

/**
 * This file is part of SimpleSAMLphp. See the file COPYING in the
 * root of the distribution for licence information.
 *
 * This file implements authentication of users using CAS.
 *
 * @author Mads Freek, RUC. 
 * @package simpleSAMLphp
 * @version $Id$
 */
 
require_once('../../www/_include.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Session.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Auth/LDAP.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Metadata/MetaDataStorageHandler.php');

$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance(TRUE);

try {
	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
	// TODO: Make this authentication module independent from SAML 2.0
	$idpentityid = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
	
	$ldapconfigfile = $config->getBaseDir() . 'config/cas-ldap.php';
	require_once($ldapconfigfile);
	
	if (!array_key_exists($idpentityid, $casldapconfig)) {
		throw new Exception('No CAS authentication configuration for this SAML 2.0 entity ID [' . $idpentityid . ']');
	}

	$idpconfig = $casldapconfig[$idpentityid];
	
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}

/*
 * Load the RelayState argument. The RelayState argument contains the address
 * we should redirect the user to after a successful authentication.
 */
if (!array_key_exists('RelayState', $_REQUEST)) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
}



function casValidate($cas) {

	$service = SimpleSAML_Utilities::selfURL();
	$service = preg_replace("/(\?|&)?ticket=.*/", "", $service); # always tagged on by cas
	
	/**
	 * Got response from CAS server.
	 */
	if (isset($_GET['ticket'])) {
	
		$ticket = urlencode($_GET['ticket']);
	
		#ini_set('default_socket_timeout', 15);
		$result = file_get_contents($cas['validate'] . '?ticket=' . $ticket . '&service=' . urlencode($service) );
		$res = preg_split("/\n/",$result);
		
		if (strcmp($res[0], "yes") == 0) {
			return $res[1];
		} else {
			throw new Exception("Failed to validate CAS service ticket: $ticket");
		}
	
	/**
	 * First request, will redirect the user to the CAS server for authentication.
	 */
	} else {
		SimpleSAML_Logger::info("AUTH - cas-ldap: redirecting to {$cas['login']}");
		SimpleSAML_Utilities::redirect($cas['login'], array(
			'renew' => 'true',
			'service' => $service
		));		
	}
}



try {





	$relaystate = $_REQUEST['RelayState'];

	$username = casValidate($idpconfig['cas']);
	SimpleSAML_Logger::info('AUTH - cas-ldap: '. $username . ' authenticated by ' . $idpconfig['cas']['validate']);
	
	
	
	/*
	 * Connecting to LDAP.
	 */
	$ldap = new SimpleSAML_Auth_LDAP($idpconfig['ldap']['servers'], $idpconfig['ldap']['enable_tls']);
	
	if ($idpconfig['ldap']['priv_user_dn']) {
	
		if (!$ldap->bind($idpconfig['ldap']['priv_user_dn'], $idpconfig['ldap']['priv_user_pw']) ) {
			throw new Exception('Could not bind with system user: ' . $idpconfig['ldap']['priv_user_dn']);
		}
	}
	
	/*
	 * Search for user in LDAP.
	 */
	$dn = $ldap->searchfordn($idpconfig['ldap']['searchbase'], $idpconfig['ldap']['searchattributes'], $username);
	
	/*
	 * Retrieve attributes from LDAP
	 */
	$attributes = $ldap->getAttributes($dn, $idpconfig['ldap']['attributes']);

	
	$session->setAuthenticated(true, 'login-cas-ldap');
	$session->setAttributes($attributes);
	
	$session->setNameID(array(
			'value' => SimpleSAML_Utilities::generateID(),
			'Format' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient'));
	SimpleSAML_Utilities::redirect($relaystate);

} catch(Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'CASERROR', $exception);
}


?>