<?php

/**
 * Built-in IdP discovery service.
 */

$discoHandler = new \impleSAML\XHTML\IdPDisco(array('saml20-idp-remote', 'shib13-idp-remote'), 'saml');
$discoHandler->handleRequest();
