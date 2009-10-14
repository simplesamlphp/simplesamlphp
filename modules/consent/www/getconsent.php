<?php

/**
 * This script displays a page to the user, which requests that the user
 * authorizes the release of attributes.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */

/*
 * Explisit instruct consent page to send no-cache header to browsers 
 * to make sure user attribute information is not store on client disk.
 * 
 * In an vanilla apache-php installation is the php variables set to:
 * session.cache_limiter = nocache
 * so this is just to make sure.
 */
session_cache_limiter('nocache');

SimpleSAML_Logger::info('Consent - getconsent: Accessing consent interface');

if (!array_key_exists('StateId', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing required StateId query parameter.');
}

$id = $_REQUEST['StateId'];
$state = SimpleSAML_Auth_State::loadState($id, 'consent:request');


if (array_key_exists('yes', $_REQUEST)) {
	/* The user has pressed the yes-button. */
	
	if (array_key_exists('saveconsent', $_REQUEST)) {
		SimpleSAML_Logger::stats('consentResponse remember');		
	} else {
		SimpleSAML_Logger::stats('consentResponse rememberNot');
	}

	if (array_key_exists('consent:store', $state) && array_key_exists('saveconsent', $_REQUEST)
		&& $_REQUEST['saveconsent'] === '1') {

		/* Save consent. */
		$store = $state['consent:store'];
		$userId = $state['consent:store.userId'];
		$targetedId = $state['consent:store.destination'];
		$attributeSet = $state['consent:store.attributeSet'];
		
		SimpleSAML_Logger::debug('Consent - saveConsent() : [' . $userId . '|' . $targetedId . '|' .  $attributeSet . ']');	
		$store->saveConsent($userId, $targetedId, $attributeSet);
	}

	SimpleSAML_Auth_ProcessingChain::resumeProcessing($state);
}


/* Prepare attributes for presentation */
$attribute_presentation = $state['Attributes'];
$para = array(
	'attributes' => &$attribute_presentation
);

/* The callHooks function call below will call a attribute array reordering function if it exist.
 * To create this function, you hawe to create a file with name: hook_attributepresentation.php and place
 * it under a <module_dir>/hooks directory. To be found and called, the function hawe to
 * be named : <module_name>_hook_attributepresentation(&$para).
 * The parameter $para is an reference to the attribute array. By manipulating this array
 * you change the way the attribute is presented to the user on the consent and status page.
 * If you want to have the attributes listed in more than one level. You can make the function add
 * a child_ prefix to the root node attribute name in a recursive attribute tree.
 * In the array below is an example of this:
 * 
 * Array
 * (
 *  [objectClass] => Array
 *      (
 *          [0] => top						<--- These values will be listed as an bullet list
 *          [1] => person
 *      )
 *  [child_eduPersonOrgUnitDN] => Array		<--- This array hawe two child array. These will be listed in
 *      (										 two separate sub tables.
 *          [0] => Array
 *              (
 *                  [ou] => Array
 *                      (
 *                          [0] => ET
 *                      )
 *                  [cn] => Array
 *                      (
 *                          [0] => Eksterne tjenester
 *                      )
 *          [1] => Array
 *              (
 *                  [ou] => Array
 *                      (
 *                          [0] => TA
 *                      )
 *                  [cn] => Array
 *                      (
 *                          [0] => Tjenesteavdeling
 *                      )
 * 
 */
SimpleSAML_Module::callHooks('attributepresentation', $para);

/* Make, populate and layout consent form. */

$globalConfig = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($globalConfig, 'consent:consentform.php');
$t->data['srcMetadata'] = $state['Source'];
$t->data['dstMetadata'] = $state['Destination'];
$t->data['yesTarget'] = SimpleSAML_Module::getModuleURL('consent/getconsent.php');
$t->data['yesData'] = array('StateId' => $id);
$t->data['noTarget'] = SimpleSAML_Module::getModuleURL('consent/noconsent.php');
$t->data['noData'] = array('StateId' => $id);
$t->data['attributes'] = $attribute_presentation;

$t->data['checked'] = $state['consent:checked'];

if (array_key_exists('privacypolicy', $state['Destination'])) {
	$privacypolicy = $state['Destination']['privacypolicy'];
} elseif (array_key_exists('privacypolicy', $state['Source'])) {
	$privacypolicy = $state['Source']['privacypolicy'];
} else {
	$privacypolicy = FALSE;
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
if (array_key_exists('consent:hiddenAttributes', $state)) {
	$t->data['hiddenAttributes'] = $state['consent:hiddenAttributes'];
} else {
	$t->data['hiddenAttributes'] = array();
}

$t->show();
exit;

?>