<?php

/**
 * Builtin IdP discovery service.
 */

$discoHandler = new SimpleSAML_XHTML_IdPDisco(array('saml20-idp-remote'), 'saml20');
$discoHandler->handleRequest();

?>