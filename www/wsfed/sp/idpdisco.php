<?php

require_once('../../_include.php');

$session = SimpleSAML_Session::getInstance();

try {
	$discoHandler = new SimpleSAML_XHTML_IdPDisco(array('wsfed-idp-remote'), 'wsfed');
} catch (Exception $exception) {
	/* An error here should be caused by invalid query parameters. */
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'DISCOPARAMS', $exception);
}

try {
	$discoHandler->handleRequest();
} catch(Exception $exception) {
	/* An error here should be caused by metadata. */
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}

?>