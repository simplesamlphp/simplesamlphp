<?php
/**
 * Show a 403 Forbidden page when an attribute violates a cardinality rule
 *
 * @package SimpleSAMLphp
 */

if (!array_key_exists('StateId', $_REQUEST)) {
    throw new \SimpleSAML_Error_BadRequest('Missing required StateId query parameter.');
}
$id = $_REQUEST['StateId'];
$state = \SimpleSAML_Auth_State::loadState($id, 'core:cardinality');
$session = \SimpleSAML_Session::getSessionFromRequest();

\SimpleSAML\Logger::stats('core:cardinality:error '.$state['Destination']['entityid'].' '.$state['saml:sp:IdP'].
    ' '.implode(',', array_keys($state['core:cardinality:errorAttributes'])));

$globalConfig = SimpleSAML_Configuration::getInstance();
$t = new \SimpleSAML_XHTML_Template($globalConfig, 'core:cardinality_error.tpl.php');
$t->data['cardinalityErrorAttributes'] = $state['core:cardinality:errorAttributes'];
if (isset($state['Source']['auth'])) {
    $t->data['LogoutURL'] = \SimpleSAML\Module::getModuleURL('core/authenticate.php', array('as' => $state['Source']['auth']))."&logout";
}
header('HTTP/1.0 403 Forbidden');
$t->show();
