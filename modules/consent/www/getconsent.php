<?php

/**
 * This script displays a page to the user, which requests that the user
 * authorizes the release of attributes.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */

if (!array_key_exists('StateId', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing required StateId query parameter.');
}

$id = $_REQUEST['StateId'];
$state = SimpleSAML_Auth_State::loadState($id, 'consent:request');


if (array_key_exists('yes', $_REQUEST)) {
	/* The user has pressed the yes-button. */

	if (array_key_exists('consent:store', $state) && array_key_exists('saveconsent', $_REQUEST)
		&& $_REQUEST['saveconsent'] === '1') {

		/* Save consent. */
		$store = $state['consent:store'];
		$userId = $state['consent:store.userId'];
		$destination = $state['consent:store.destination'];
		$attributeSet = $state['consent:store.attributeSet'];
		$store->saveConsent($userId, $destination, $attributeSet);
	}

	SimpleSAML_Auth_ProcessingChain::resumeProcessing($state);
}


/* Show consent form. */

$globalConfig = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($globalConfig, 'consent:consentform.php');
$t->data['srcMetadata'] = $state['Source'];
$t->data['dstMetadata'] = $state['Destination'];
$t->data['yesTarget'] = SimpleSAML_Module::getModuleURL('consent/getconsent.php');
$t->data['yesData'] = array('StateId' => $id);
$t->data['noTarget'] = SimpleSAML_Module::getModuleURL('consent/noconsent.php');
$t->data['noData'] = array('StateId' => $id);
$t->data['attributes'] = $state['Attributes'];

if (array_key_exists('privacypolicy', $state['Destination'])) {
	$privacypolicy = $state['Destination']['privacypolicy'];
} elseif (array_key_exists('privacypolicy', $state['Source'])) {
	$privacypolicy = $state['Source']['privacypolicy'];
} else {
	$privacypolicy = FALSE;
}
if($privacypolicy !== FALSE) {
	$privacypolicy = str_replace('%SPENTITYID%', urlencode($spentityid),
		$privacypolicy);
}
$t->data['sppp'] = $privacypolicy;

switch ($state['consent:focus']) {
case NULL:
	break;
case 'yes':
	$t->data['autofocus'] = 'yesbutton';
	break;
case 'no':
	$t->data['autofocus'] = 'nobutton';
	break;
}

if (array_key_exists('consent:store', $state)) {
	$t->data['usestorage'] = TRUE;
} else {
	$t->data['usestorage'] = FALSE;
}

$t->show();
exit;

?>