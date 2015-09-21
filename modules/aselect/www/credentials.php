<?php

/**
 * Check the credentials that the user got from the A-Select server.
 * This function is called after the user returns from the A-Select server.
 *
 * @author Wessel Dankers, Tilburg University
 */
if (!array_key_exists('ssp_state', $_REQUEST)) {
    throw new SimpleSAML_Error_Exception("Missing ssp_state parameter");
}
$id = $_REQUEST['ssp_state'];

// sanitize the input
$sid = SimpleSAML_Utilities::parseStateID($id);
if (!is_null($sid['url'])) {
    SimpleSAML_Utilities::checkURLAllowed($sid['url']);
}

$state = SimpleSAML_Auth_State::loadState($id, 'aselect:login');

if (!array_key_exists('a-select-server', $_REQUEST)) {
    SimpleSAML_Auth_State::throwException($state, new SimpleSAML_Error_Exception("Missing a-select-server parameter"));
}
$server_id = $_REQUEST['a-select-server'];

if (!array_key_exists('aselect_credentials', $_REQUEST)) {
    SimpleSAML_Auth_State::throwException($state,
                                          new SimpleSAML_Error_Exception("Missing aselect_credentials parameter"));
}
$credentials = $_REQUEST['aselect_credentials'];

if (!array_key_exists('rid', $_REQUEST)) {
    SimpleSAML_Auth_State::throwException($state, new SimpleSAML_Error_Exception("Missing rid parameter"));
}
$rid = $_REQUEST['rid'];

try {
    if (!array_key_exists('aselect::authid', $state)) {
        throw new SimpleSAML_Error_Exception("ASelect authentication source missing in state");
    }
    $authid = $state['aselect::authid'];
    $aselect = SimpleSAML_Auth_Source::getById($authid);
    if (is_null($aselect)) {
        throw new SimpleSAML_Error_Exception("Could not find authentication source with id $authid");
    }
    $creds = $aselect->verify_credentials($server_id, $credentials, $rid);

    if (array_key_exists('attributes', $creds)) {
        $state['Attributes'] = $creds['attributes'];
    } else {
        $state['Attributes'] = array('uid' => array($creds['uid']), 'organization' => array($creds['organization']));
    }
} catch (Exception $e) {
    SimpleSAML_Auth_State::throwException($state, $e);
}

SimpleSAML_Auth_Source::completeAuth($state);
SimpleSAML_Auth_State::throwException($state, new SimpleSAML_Error_Exception("Internal error in A-Select component"));
