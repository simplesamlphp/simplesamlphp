<?php

$config = \SimpleSAML\Configuration::getInstance();
$sources = \SimpleSAML\Configuration::getOptionalConfig('authsources.php')->toArray();

//delete admin
if (isset($sources['admin'])) {
    unset($sources['admin']);
}

//if only 1 auth
if (count($sources) == 1) {
    $_REQUEST['as'] = key($sources);
}

if (!array_key_exists('as', $_REQUEST)) {
    $t = new \SimpleSAML\XHTML\Template($config, 'core:login.twig');

    $t->data['loginurl'] = \SimpleSAML\Utils\Auth::getAdminLoginURL();
    $t->data['sources'] = $sources;
    $t->show();
    exit();
}

$asId = (string) $_REQUEST['as'];
$as = new \SimpleSAML\Auth\Simple($asId);

if (array_key_exists('logout', $_REQUEST)) {
    $as->logout($config->getBasePath().'logout.php');
}

if (array_key_exists(\SimpleSAML\Auth\State::EXCEPTION_PARAM, $_REQUEST)) {
    // This is just a simple example of an error

    $state = \SimpleSAML\Auth\State::loadExceptionState();
    assert(array_key_exists(\SimpleSAML\Auth\State::EXCEPTION_DATA, $state));
    $e = $state[\SimpleSAML\Auth\State::EXCEPTION_DATA];

    throw $e;
}

if (!$as->isAuthenticated()) {
    $url = \SimpleSAML\Module::getModuleURL('core/login.php', ['as' => $asId]);
    $params = [
        'ErrorURL' => $url,
        'ReturnTo' => $url,
    ];
    $as->login($params);
}

$attributes = $as->getAttributes();
$session = \SimpleSAML\Session::getSessionFromRequest();

$t = new \SimpleSAML\XHTML\Template($config, 'auth_status.twig', 'attributes');


$t->data['header'] = '{status:header_saml20_sp}';
$t->data['attributes'] = $attributes;
$t->data['nameid'] = !is_null($as->getAuthData('saml:sp:NameID')) ? $as->getAuthData('saml:sp:NameID') : false;
$t->data['logouturl'] = \SimpleSAML\Utils\HTTP::getSelfURLNoQuery().'?as='.urlencode($asId).'&logout';
$t->data['remaining'] = $session->getAuthData($asId, 'Expire') - time();

$t->show();
