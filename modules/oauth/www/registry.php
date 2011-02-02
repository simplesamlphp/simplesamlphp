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


if (isset($_REQUEST['delete'])) {
	$entryc = $store->get('consumers', $_REQUEST['delete'], '');
	$entry = $entryc['value'];

	requireOwnership($entry, $userid);
	$store->remove('consumers', $entry['key'], '');
}


$list = $store->getList('consumers');

$slist = array('mine' => array(), 'others' => array());
if (is_array($list)) 
foreach($list AS $listitem) {
	if (array_key_exists('owner', $listitem['value'])) {
		if ($listitem['value']['owner'] === $userid) {
			$slist['mine'][] = $listitem; continue;
		}
	}
	$slist['others'][] = $listitem;
}

// echo('<pre>'); print_r($slist); exit;

$template = new SimpleSAML_XHTML_Template($config, 'oauth:registry.list.php');
$template->data['entries'] = $slist;
$template->data['userid'] = $userid;
$template->show();
