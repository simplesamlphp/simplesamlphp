<?php

/**
 * Request handler for redirect filter test.
 *
 * @author Olav Morken, UNINETT AS.
 * @package SimpleSAMLphp
 */

if (!array_key_exists('StateId', $_REQUEST)) {
    throw new \SimpleSAML\Error\BadRequest('Missing required StateId query parameter.');
}

$state = \SimpleSAML\Auth\State::loadState($_REQUEST['StateId'], 'exampleauth:redirectfilter-test');
if ($state === null) {
    throw new \SimpleSAML\Error\NoState();
}

$state['Attributes']['RedirectTest2'] = ['OK'];

\SimpleSAML\Auth\ProcessingChain::resumeProcessing($state);
