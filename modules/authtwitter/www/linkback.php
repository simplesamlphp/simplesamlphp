<?php

/**
 * Handle linkback() response from Twitter.
 */
# sspmod_oauth_Consumer::dummy();

// $config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();
 
$oauthState = $session->getData('oauth', 'oauth');

if (empty($oauthState)) throw new Exception('Could not load oauthstate');
if (empty($oauthState['stateid'])) throw new Exception('Could not load oauthstate:stateid');

$stateId = $oauthState['stateid'];

// echo 'stateid is ' . $stateId;

$state = SimpleSAML_Auth_State::loadState($stateId, sspmod_authtwitter_Auth_Source_Twitter::STAGE_INIT);
$state['requestToken'] = $oauthState['requestToken'];



/* Find authentication source. */
assert('array_key_exists(sspmod_authtwitter_Auth_Source_Twitter::AUTHID, $state)');
$sourceId = $state[sspmod_authtwitter_Auth_Source_Twitter::AUTHID];

$source = SimpleSAML_Auth_Source::getById($sourceId);
if ($source === NULL) {
	throw new Exception('Could not find authentication source with id ' . $sourceId);
}



$config = SimpleSAML_Configuration::getInstance();

$source->finalStep($state);



SimpleSAML_Auth_Source::completeAuth($state);


