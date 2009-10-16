<?php

if (!is_string($_REQUEST['StateID'])) {
	throw new SimpleSAML_Error_BadRequest('Missing StateID-parameter');
}

$server = sspmod_openidProvider_Server::getInstance();
$state = $server->loadState($_REQUEST['StateID']);
$server->processRequest($state);
