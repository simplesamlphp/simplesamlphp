<?php

/**
 * Handle linkback() response from CAS.
 */

if (!isset($_GET['stateID'])) {
    throw new \SimpleSAML\Error\BadRequest('Missing stateID parameter.');
}
$state = \SimpleSAML\Auth\State::loadState($_GET['stateID'], \SimpleSAML\Module\cas\Auth\Source\CAS::STAGE_INIT);

if (!isset($_GET['ticket'])) {
    throw new \SimpleSAML\Error\BadRequest('Missing ticket parameter.');
}
$state['cas:ticket'] = (string) $_GET['ticket'];

// Find authentication source
assert(array_key_exists(\SimpleSAML\Module\cas\Auth\Source\CAS::AUTHID, $state));
$sourceId = $state[\SimpleSAML\Module\cas\Auth\Source\CAS::AUTHID];

$source = \SimpleSAML\Auth\Source::getById($sourceId);
if ($source === null) {
    throw new \Exception('Could not find authentication source with id '.$sourceId);
}

$source->finalStep($state);
