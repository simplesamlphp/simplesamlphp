<?php

require_once('../../_include.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();


SimpleSAML_Logger::info('SAML2.0 - SP.idpDisco: Accessing SAML 2.0 discovery service');

if (!$config->getValue('enable.saml20-sp', false)) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');
}

/* The base path for cookies. This should be the installation directory for simpleSAMLphp. */
$cookiePath = '/' . $config->getBaseUrl();

/* Has the admin enabled the remember choice option in the config? */
$rememberEnabled = $config->getBoolean('idpdisco.enableremember', FALSE);

/* Check script parameters. */
try {

	if (!isset($_GET['entityID'])) throw new Exception('Missing parameter: entityID');
	if (!isset($_GET['return'])) throw new Exception('Missing parameter: return');



	$spentityid = $_GET['entityID'];
	$return = $_GET['return'];
	
	// Default value for "returnIDParam". Added to support Shibboleth 2.0 SP which does not 
	// send this parameter.
	//    if (!isset($_GET['returnIDParam'])) throw new Exception('Missing parameter: returnIDParam'); 
	$returnidparam = 'idpentityid';
	
	//
	if (isset($_GET['returnIDParam'])) {
		$returnidparam = $_GET['returnIDParam'];
	}
	
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'DISCOPARAMS', $exception);
}


$selectedIdP = NULL;
$userSelectedIdP = FALSE;

/* Check for dropdown-style of IdP selection. */
if(array_key_exists('idpentityid', $_GET)) {
	$selectedIdP = $_GET['idpentityid'];
	$userSelectedIdP = TRUE;

}

if($selectedIdP === NULL) {
	/* Search for the IdP selection from the form used by the links view.
	 * This form uses a name which equals idp_<entityid>, so we search for that.
	 *
	 * Unfortunately, php replaces periods in the name with underscores, and there
	 * is no reliable way to get them back. Therefore we do some quick and dirty
	 * parsing of the query string.
	 */
	$qstr = $_SERVER['QUERY_STRING'];
	$matches = array();
	if(preg_match('/(?:^|&)idp_([^=]+)=/', $qstr, $matches)) {
		$selectedIdP = urldecode($matches[1]);
		$userSelectedIdP = TRUE;
	}
}


if($selectedIdP === NULL && $rememberEnabled) {
	/* No choice made by the user. Check if there is a remembered IdP for the user. */

	if(array_key_exists('idpdisco_saml20_rememberchoice', $_COOKIE) &&
		array_key_exists('idpdisco_saml20_lastidp', $_COOKIE)) {

		$selectedIdP = $_COOKIE['idpdisco_saml20_lastidp'];
		$userSelectedIdP = FALSE;
	}
}

/* Check that the selected IdP is a valid IdP. */
if($selectedIdP !== NULL) {
	try {
		$idpMetadata = $metadata->getMetaData($selectedIdP, 'saml20-idp-remote');
	} catch(Exception $e) {
		/* The entity id wasn't valid. */
		$selectedIdP = NULL;
		$userSelectedIdP = FALSE;
	}
}

if($selectedIdP !== NULL) {
	/* We have an IdP selection. */

	if($userSelectedIdP) {
		/* We save the users choice for 90 days. */
		$saveUntil = time() + 60*60*24*90;

		SimpleSAML_Logger::info('SAML2.0 - SP.idpDisco: Choice made [ ' . $selectedIdP . ']' .
			' Setting idpdisco_saml20_lastidp cookie.');
		setcookie('idpdisco_saml20_lastidp', $selectedIdP, $saveUntil, $cookiePath);

		if($rememberEnabled) {
			if(array_key_exists('remember', $_GET)) {
				/* The remember choice option is enabled, and the user has selected
				 * "remember choice" in the IdP list. Save this choice.
				 */
				setcookie('idpdisco_saml20_rememberchoice', 1, $saveUntil, $cookiePath);
			}
		}
	}

	SimpleSAML_Logger::info('SAML2.0 - SP.idpDisco: Choice made [ ' . $selectedIdP . '] (Redirecting the user back)');
	SimpleSAML_Utilities::redirect($return, array($returnidparam => $selectedIdP));
}


/* Load list of entities. */
try {
	$idplist = $metadata->getList('saml20-idp-remote');
	$preferredidp = $metadata->getPreferredEntityIdFromCIDRhint('saml20-idp-remote', $_SERVER['REMOTE_ADDR']);
	
	if (!empty($preferredidp)) {
		SimpleSAML_Logger::info('SAML2.0 - SP.idpDisco: Preferred IdP from CIDR hint [ ' . $preferredidp . '].');
	}
} catch(Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}

if(array_key_exists('idpdisco_saml20_lastidp', $_COOKIE)) {
	$preferredidp = $_COOKIE['idpdisco_saml20_lastidp'];
	SimpleSAML_Logger::info('SAML2.0 - SP.idpDisco: Preferred IdP overridden from cookie [ ' . $preferredidp . '].');
}


/*
 * Make use of an XHTML template to present the select IdP choice to the user.
 * Currently the supported options is either a drop down menu or a list view.
 */
switch($config->getString('idpdisco.layout', 'links')) {
 case 'dropdown':
	 $templatefile = 'selectidp-dropdown.php';
	 break;
 case 'links':
	 $templatefile = 'selectidp-links.php';
	 break;
 default:
	 throw new Exception('Invalid value for the \'idpdisco.layout\' option.');
}

$t = new SimpleSAML_XHTML_Template($config, $templatefile, 'disco.php');
$t->data['idplist'] = $idplist;
$t->data['preferredidp'] = $preferredidp;

$t->data['return']= $return;
$t->data['returnIDParam'] = $returnidparam;
$t->data['entityID'] = $spentityid;
$t->data['urlpattern'] = htmlspecialchars(SimpleSAML_Utilities::selfURLNoQuery());

$t->data['rememberenabled'] = $rememberEnabled;

$t->show();

?>