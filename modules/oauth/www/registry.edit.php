<?php

/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();
$oauthconfig = SimpleSAML_Configuration::getOptionalConfig('module_oauth.php');

$store = new sspmod_core_Storage_SQLPermanentStorage('oauth');

//$authsource = $oauthconfig->getValue('auth', 'admin');
$authsource = "admin";	// force admin to authenticate as registry maintainer
$useridattr = $oauthconfig->getValue('useridattr', 'user');
//$useridattr = $oauthconfig->getValue('useridattr', 'uid');

if ($session->isValid($authsource)) {
	$attributes = $session->getAttributes();
	// Check if userid exists
	if (!isset($attributes[$useridattr])) 
		throw new Exception('User ID is missing');
	$userid = $attributes[$useridattr][0];
} else {
	SimpleSAML_Auth_Default::initLogin($authsource, SimpleSAML_Utilities::selfURL());
}

function requireOwnership($entry, $userid) {
	if (!isset($entry['owner']))
		throw new Exception('OAuth Consumer has no owner. Which means no one is granted access, not even you.');
	if ($entry['owner'] !== $userid) 
		throw new Exception('OAuth Consumer has an owner that is not equal to your userid, hence you are not granted access.');
}

if (array_key_exists('editkey', $_REQUEST)) {
	$entryc = $store->get('consumers', $_REQUEST['editkey'], '');
	$entry = $entryc['value'];
	requireOwnership($entry, $userid);
	
} else {
	$entry = array(
		'owner' => $userid,
		'key' => SimpleSAML_Utilities::generateID(),
		'secret' => SimpleSAML_Utilities::generateID(),
	);
}


$editor = new sspmod_oauth_Registry();


if (isset($_POST['submit'])) {
	$editor->checkForm($_POST);

	$entry = $editor->formToMeta($_POST, array(), array('owner' => $userid));

	requireOwnership($entry, $userid);
	
#	echo('<pre>Created: '); print_r($entry); exit;
	
	$store->set('consumers', $entry['key'], '', $entry);
	
	$template = new SimpleSAML_XHTML_Template($config, 'oauth:registry.saved.php');
	$template->data['entry'] = $entry;
	$template->show();
	exit;
}

$form = $editor->metaToForm($entry);

$template = new SimpleSAML_XHTML_Template($config, 'oauth:registry.edit.tpl.php');
$template->data['form'] = $form;
$template->show();

