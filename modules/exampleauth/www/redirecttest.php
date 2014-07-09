<?php

/**
 * Request handler for redirect filter test.
 *
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 */

if (!array_key_exists('StateId', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing required StateId query parameter.');
}

$id = $_REQUEST['StateId'];

// sanitize the input
$sid = SimpleSAML_Utilities::parseStateID($id);
if (!is_null($sid['url'])) {
	SimpleSAML_Utilities::checkURLAllowed($sid['url']);
}

$state = SimpleSAML_Auth_State::loadState($id, 'exampleauth:redirectfilter-test');

$state['Attributes']['RedirectTest2'] = array('OK');

SimpleSAML_Auth_ProcessingChain::resumeProcessing($state);

?>