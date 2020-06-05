<?php

/**
 * Built-in IdP discovery service.
 */

$discoHandler = new \SimpleSAML\XHTML\IdPDisco(['saml20-idp-remote'], 'saml');
$discoHandler->handleRequest();
