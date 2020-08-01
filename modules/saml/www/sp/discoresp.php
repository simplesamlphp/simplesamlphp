<?php

/**
 * Handler for response from IdP discovery service.
 */

use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Error;

if (!array_key_exists('AuthID', $_REQUEST)) {
    throw new Error\BadRequest('Missing AuthID to discovery service response handler');
}

if (!array_key_exists('idpentityid', $_REQUEST)) {
    throw new Error\BadRequest('Missing idpentityid to discovery service response handler');
}

/** @var array $state */
$state = Auth\State::loadState($_REQUEST['AuthID'], 'saml:sp:sso');

// Find authentication source
Assert::keyExists($state, 'saml:sp:AuthId');
$sourceId = $state['saml:sp:AuthId'];

$source = Auth\Source::getById($sourceId);
if ($source === null) {
    throw new Exception('Could not find authentication source with id ' . $sourceId);
}
if (!($source instanceof \SimpleSAML\Module\saml\Auth\Source\SP)) {
    throw new Error\Exception('Source type changed?');
}

$source->startSSO($_REQUEST['idpentityid'], $state);
