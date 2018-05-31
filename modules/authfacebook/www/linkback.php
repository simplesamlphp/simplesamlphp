<?php

/**
 * Handle linkback() response from Facebook.
 */

// For backwards compatability look for AuthState first
if (array_key_exists('AuthState', $_REQUEST) && !empty($_REQUEST['AuthState'])) {
    $state = \SimpleSAML\Auth\State::loadState($_REQUEST['AuthState'], sspmod_authfacebook_Auth_Source_Facebook::STAGE_INIT);
} elseif (array_key_exists('state', $_REQUEST) && !empty($_REQUEST['state'])) {
    $state = \SimpleSAML\Auth\State::loadState($_REQUEST['state'], sspmod_authfacebook_Auth_Source_Facebook::STAGE_INIT);
} else {
    throw new \SimpleSAML\Error\BadRequest('Missing state parameter on facebook linkback endpoint.');
}

// Find authentication source
if (!array_key_exists(sspmod_authfacebook_Auth_Source_Facebook::AUTHID, $state)) {
    throw new \SimpleSAML\Error\BadRequest('No data in state for ' . sspmod_authfacebook_Auth_Source_Facebook::AUTHID);
}
$sourceId = $state[sspmod_authfacebook_Auth_Source_Facebook::AUTHID];

$source = \SimpleSAML\Auth\Source::getById($sourceId);
if ($source === null) {
    throw new \SimpleSAML\Error\BadRequest('Could not find authentication source with id ' . var_export($sourceId, TRUE));
}

try {
    if (isset($_REQUEST['error_reason']) && $_REQUEST['error_reason'] == 'user_denied') {
        throw new \SimpleSAML\Error\UserAborted();
    }

    $source->finalStep($state);
} catch (\SimpleSAML\Error\Exception $e) {
    \SimpleSAML\Auth\State::throwException($state, $e);
} catch (\Exception $e) {
    \SimpleSAML\Auth\State::throwException($state, new \SimpleSAML\Error\AuthSource($sourceId, 'Error on facebook linkback endpoint.', $e));
}

\SimpleSAML\Auth\Source::completeAuth($state);
