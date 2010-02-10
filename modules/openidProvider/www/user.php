<?php

if (isset($_SERVER['PATH_INFO'])) {
	$userId = substr($_SERVER['PATH_INFO'], 1);
} else {
	$userId = FALSE;
}

$globalConfig = SimpleSAML_Configuration::getInstance();
$server = sspmod_openidProvider_Server::getInstance();
$identity = $server->getIdentity();

if (!$userId && $identity) {
	/*
	 * We are accessing the front-page, but are logged in.
	 * Redirect to the correct page.
	 */
	SimpleSAML_Utilities::redirect($identity);
}

/* Determine whether we are at the users own page. */
if ($userId && $userId === $server->getUserId()) {
	$ownPage = TRUE;
} else {
	$ownPage = FALSE;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if ($ownPage) {
		foreach ($_POST as $k => $v) {
			$op = explode('_', $k, 2);
			if (count($op) == 1 || $op[0] !== 'remove') {
				continue;
			}

			$site = $op[1];
			$site = pack("H*" , $site);
			$server->removeTrustRoot($identity, $site);
		}
	}

	SimpleSAML_Utilities::redirect($identity);
}

if ($ownPage) {
	$trustedSites = $server->getTrustRoots($identity);
} else {
	$trustedSites = array();
}

$userBase = SimpleSAML_Module::getModuleURL('openidProvider/user.php');

$xrds = SimpleSAML_Module::getModuleURL('openidProvider/xrds.php');
if ($userId !== FALSE) {
	$xrds = SimpleSAML_Utilities::addURLparameter($xrds, array('user' => $userId));
}

$as = $server->getAuthSource();
$t = new SimpleSAML_XHTML_Template($globalConfig, 'openidProvider:user.tpl.php');
$t->data['identity'] = $identity;
$t->data['loggedInAs'] = $server->getUserId();
$t->data['loginURL'] = $as->getLoginURL($userBase);
$t->data['logoutURL'] = $as->getLogoutURL();
$t->data['ownPage'] = $ownPage;
$t->data['serverURL'] = $server->getServerURL();
$t->data['trustedSites'] = $trustedSites;
$t->data['userId'] = $userId;
$t->data['userIdURL'] = $userBase . '/' . $userId;
$t->data['xrdsURL'] = $xrds;

$t->show();
exit(0);
