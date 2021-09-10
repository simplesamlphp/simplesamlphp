<?php

/**
 * Resume calling all registered asConsumerLogoutCallbacks.
 */

if (!isset($_REQUEST['id'])) {
    throw new \SimpleSAML\Error\BadRequest('Missing id parameter.');
}
$id = (string)$_REQUEST['id'];

$sid = \SimpleSAML\Auth\State::parseStateID($id);
if (!is_null($sid['url'])) {
    \SimpleSAML\Utils\HTTP::checkURLAllowed($sid['url']);
}

$state = \SimpleSAML\Auth\State::loadState($id, 'asConsumerLogoutCallbacks:resume');

\SimpleSAML\Auth\Source::handleAsConsumerLogoutCallbacks($state);
