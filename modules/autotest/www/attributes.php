<?php

try {
	if (!isset($_GET['SourceID'])) {
		throw new SimpleSAML_Error_BadRequest('Missing SourceID parameter');
	}
	$sourceId = $_GET['SourceID'];

	$as = new SimpleSAML_Auth_Simple($sourceId);

	if (!$as->isAuthenticated()) {
		throw new SimpleSAML_Error_Exception('Not authenticated.');
	}

	$attributes = $as->getAttributes();

	header('Content-Type: text/plain; charset=utf-8');
	echo("OK\n");
	foreach ($attributes as $name => $values) {
		echo("$name\n");
		foreach ($values as $value) {
			echo("\t$value\n");
		}
	}

} catch (Exception $e) {
	header('HTTP/1.0 500 Internal Server Error');
	header('Content-Type: text/plain; charset=utf-8');
	echo("ERROR\n");
	echo($e->getMessage() . "\n");
}
