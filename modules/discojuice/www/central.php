<?php

if (empty($_REQUEST['entityID'])) throw new Exception('Missing parameter [entityID]');
if (empty($_REQUEST['return'])) throw new Exception('Missing parameter [return]');


$djconfig = SimpleSAML_Configuration::getOptionalConfig('discojuice.php');
$config = SimpleSAML_Configuration::getInstance();

// EntityID
$entityid = $_REQUEST['entityID'];

// Return to...
$returnidparam = !empty($_REQUEST['returnIDParam']) ? $_REQUEST['returnIDParam'] : 'entityID';
$href = SimpleSAML_Utilities::addURLparameter(
	$_REQUEST['return'],
	array($returnidparam => '')
);


$hostedConfig = array(
	// Name of service
	$djconfig->getString('name', 'Service'),

	$entityid,
	
	// Url to response
	SimpleSAML_Module::getModuleURL('discojuice/response.html'),
	
	// Set of feeds to subscribe to.
	$djconfig->getArray('feeds', array('edugain')), 
	
	$href
);

/*
	"a.signin", "Teest Demooo",
    "https://example.org/saml2/entityid",
    "' . SimpleSAML_Module::getModuleURL('discojuice/discojuice/discojuiceDiscoveryResponse.html') . '", ["kalmar"], "http://example.org/login?idp="
*/

$t = new SimpleSAML_XHTML_Template($config, 'discojuice:central.tpl.php');
$t->data['hostedConfig'] = $hostedConfig;
$t->data['enableCentralStorage'] = $djconfig->getBoolean('enableCentralStorage', true);
$t->data['additionalFeeds'] = $djconfig->getArray('additionalFeeds', null);
$t->show();



