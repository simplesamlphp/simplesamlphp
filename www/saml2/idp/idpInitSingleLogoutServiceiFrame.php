<?php

/**
 * IdP Initiated Single Log-Out. Requires one parameter: RelayState.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */

require_once('../../_include.php');

$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$idpEntityId = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
$idp = SimpleSAML_IdP::getById('saml2:' . $idpEntityId);

if (!isset($_REQUEST['RelayState'])) {
	throw new SimpleSAML_Error_BadRequest('Missing required RelayState parameter.');
}

$idp->doLogoutRedirect((string)$_REQUEST['RelayState']);
assert('FALSE');
