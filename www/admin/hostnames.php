<?php

require_once('../_include.php');

/* Load simpleSAMLphp, configuration */
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

/* Check if valid local session exists.. */
SimpleSAML_Utilities::requireAdmin();





$attributes = array();


$attributes['HTTP_HOST'] = array($_SERVER['HTTP_HOST']);
$attributes['HTTPS'] = array($_SERVER['HTTPS']);
$attributes['SERVER_PROTOCOL'] = array($_SERVER['SERVER_PROTOCOL']);
$attributes['SERVER_PORT'] = array($_SERVER['SERVER_PORT']);

$attributes['Utilities_getBaseURL()'] = array(SimpleSAML_Utilities::getBaseURL());
$attributes['Utilities_getSelfHost()'] = array(SimpleSAML_Utilities::getSelfHost());
$attributes['Utilities_selfURLhost()'] = array(SimpleSAML_Utilities::selfURLhost());
$attributes['Utilities_selfURLNoQuery()'] = array(SimpleSAML_Utilities::selfURLNoQuery());
$attributes['Utilities_getSelfHostWithPath()'] = array(SimpleSAML_Utilities::getSelfHostWithPath());
$attributes['Utilities_getFirstPathElement()'] = array(SimpleSAML_Utilities::getFirstPathElement());
$attributes['Utilities_selfURL()'] = array(SimpleSAML_Utilities::selfURL());





$et = new SimpleSAML_XHTML_Template($config, 'status.php');

$et->data['header'] = '{status:header_diagnostics}';
$et->data['remaining'] = 'na';
$et->data['attributes'] = $attributes;
$et->data['valid'] = 'na';
$et->data['logout'] = null;

$et->show();


?>