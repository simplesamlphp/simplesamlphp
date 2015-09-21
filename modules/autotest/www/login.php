<?php

try {
	if (!isset($_GET['SourceID'])) {
		throw new SimpleSAML_Error_BadRequest('Missing SourceID parameter');
	}
	$sourceId = $_GET['SourceID'];

	$as = new SimpleSAML_Auth_Simple($sourceId);

	$as->requireAuth();

	header('Content-Type: text/plain; charset=utf-8');
	echo("OK\n");
} catch (Exception $e) {
	header('HTTP/1.0 500 Internal Server Error');
	header('Content-Type: text/plain; charset=utf-8');
	echo("ERROR\n");
	echo($e->getMessage() . "\n");
}
