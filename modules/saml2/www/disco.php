<?php

/**
 * Builtin IdP discovery service.
 */

$discoHandler = new SimpleSAML_XHTML_IdPDisco('saml20');
$discoHandler->handleRequest();

?>