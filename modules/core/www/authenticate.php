<?php

$config = SimpleSAML_Configuration::getInstance();

if (!array_key_exists('as', $_REQUEST)) {
    $t = new SimpleSAML_XHTML_Template($config, 'core:authsource_list.tpl.php');

    $t->data['sources'] = SimpleSAML_Auth_Source::getSources();
    $t->show();
    exit();
}

$asId = (string) $_REQUEST['as'];
$as = new SimpleSAML_Auth_Simple($asId);

if (array_key_exists('logout', $_REQUEST)) {
    $as->logout($config->getBasePath().'logout.php');
}

if (array_key_exists(SimpleSAML_Auth_State::EXCEPTION_PARAM, $_REQUEST)) {
    // This is just a simple example of an error

    $state = SimpleSAML_Auth_State::loadExceptionState();
    assert('array_key_exists(SimpleSAML_Auth_State::EXCEPTION_DATA, $state)');
    $e = $state[SimpleSAML_Auth_State::EXCEPTION_DATA];

    throw $e;
}

if (!$as->isAuthenticated()) {
    $url = SimpleSAML\Module::getModuleURL('core/authenticate.php', array('as' => $asId));
    $params = array(
        'ErrorURL' => $url,
        'ReturnTo' => $url,
    );
    $as->login($params);
}

$attributes = $as->getAttributes();

$t = new SimpleSAML_XHTML_Template($config, 'status.php', 'attributes');

$t->data['header'] = '{status:header_saml20_sp}';
$t->data['attributes'] = $attributes;
$t->data['nameid'] = !is_null($as->getAuthData('saml:sp:NameID')) ? $as->getAuthData('saml:sp:NameID') : false;
$t->data['logouturl'] = \SimpleSAML\Utils\HTTP::getSelfURLNoQuery().'?as='.urlencode($asId).'&logout';
$t->show();
