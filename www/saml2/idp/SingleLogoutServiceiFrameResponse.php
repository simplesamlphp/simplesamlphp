<?php


/**
 * This SAML 2.0 endpoint can receive incoming LogoutResponses. 
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */

require_once('../../_include.php');

SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutServiceiFrameResponse: Accessing SAML 2.0 IdP endpoint SingleLogoutServiceResponse (iFrame version)');

$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$idpEntityId = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
$idp = SimpleSAML_IdP::getById('saml2:' . $idpEntityId);
sspmod_saml_IdP_SAML2::receiveLogoutMessage($idp);
assert('FALSE');
