<?php

require_once('../_include.php');

$session = SimpleSAML_Session::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

$spEntityId = $metadata->getMetaDataCurrentEntityID('saml20-sp-hosted');

function sendQuery($dataId, $url, $nameId) {
	assert('is_string($dataId)');
	assert('is_string($url)');
	assert('is_array($nameId)');

	SimpleSAML_Logger::debug('attributequery - sending request');

	$query = new SAML2_AttributeQuery();
	$query->setRelayState($dataId);
	$query->setDestination($url);
	$query->setIssuer($GLOBALS['spEntityId']);
	$query->setNameId($nameId);

	$binding = new SAML2_HTTPRedirect();
	$binding->send($query);
}

function handleResponse() {
	try {
		$binding = SAML2_Binding::getCurrentBinding();
		$response = $binding->receive();
	} catch (Exception $e) {
		return;
	}

	SimpleSAML_Logger::debug('attributequery - received message.');

	if (!($response instanceof SAML2_Response)) {
		throw new SimpleSAML_Error_Exception('Unexpected message received to attribute query example.');
	}

	$idpEntityId = $response->getIssuer();
	if ($idpEntityId === NULL) {
		throw new SimpleSAML_Error_Exception('Missing issuer in response.');
	}

	$idpMetadata = $GLOBALS['metadata']->getMetaDataConfig($idpEntityId, 'saml20-idp-remote');
	$spMetadata =  $GLOBALS['metadata']->getMetaDataConfig($GLOBALS['spEntityId'], 'saml20-sp-hosted');

	$assertion = sspmod_saml_Message::processResponse($spMetadata, $idpMetadata, $response);
	if (count($assertion) > 1) {
		throw new SimpleSAML_Error_Exception('More than one assertion in received response.');
	}
	$assertion = $assertion[0];

	$dataId = $response->getRelayState();
	if ($dataId === NULL) {
		throw new SimpleSAML_Error_Exception('RelayState was lost during request.');
	}

	$data = $GLOBALS['session']->getData('attributequeryexample:data', $dataId);
	$data['attributes'] = $assertion->getAttributes();
	$GLOBALS['session']->setData('attributequeryexample:data', $dataId, $data, 3600);

	SimpleSAML_Utilities::redirect(SimpleSAML_Utilities::selfURLNoQuery(),
		array('dataId' => $dataId));
}

handleResponse();

$defNameId = $session->getNameId();
if (empty($defNameId)) {
	$defNameId = array();
}
if (!array_key_exists('Value', $defNameId)) {
	$defNameId['Value'] = SimpleSAML_Utilities::generateID();
}
if (!array_key_exists('Format', $defNameId)) {
	$defNameId['Format'] = SAML2_Const::NAMEID_TRANSIENT;
}
if (!array_key_exists('NameQualifier', $defNameId) || $defNameId['NameQualifier'] === NULL) {
	$defNameId['NameQualifier'] = '';
}
if (!array_key_exists('SPNameQualifier', $defNameId) || $defNameId['SPNameQualifier'] === NULL) {
	$defNameId['SPNameQualifier'] = '';
}


if (array_key_exists('dataId', $_REQUEST)) {
	$dataId = (string)$_REQUEST['dataId'];
	$data = $session->getData('attributequeryexample:data', $dataId);
	if ($data == NULL) {
		$data = array();
	}
} else {
	$dataId = SimpleSAML_Utilities::generateID();
	$data = array();
}

if (array_key_exists('nameIdFormat', $_REQUEST)) {
	$data['nameIdFormat'] = (string)$_REQUEST['nameIdFormat'];
} elseif (!array_key_exists('nameIdFormat', $data)) {
	$data['nameIdFormat'] = $defNameId['Format'];
}

if (array_key_exists('nameIdValue', $_REQUEST)) {
	$data['nameIdValue'] = (string)$_REQUEST['nameIdValue'];
} elseif (!array_key_exists('nameIdValue', $data)) {
	$data['nameIdValue'] = $defNameId['Value'];
}

if (array_key_exists('nameIdQualifier', $_REQUEST)) {
	$data['nameIdQualifier'] = (string)$_REQUEST['nameIdQualifier'];
} elseif (!array_key_exists('nameIdQualifier', $data)) {
	$data['nameIdQualifier'] = $defNameId['NameQualifier'];
}

if (array_key_exists('nameIdSPQualifier', $_REQUEST)) {
	$data['nameIdSPQualifier'] = (string)$_REQUEST['nameIdSPQualifier'];
} elseif (!array_key_exists('nameIdSPQualifier', $data)) {
	$data['nameIdSPQualifier'] = $defNameId['SPNameQualifier'];
}


if (array_key_exists('url', $_REQUEST)) {
	$data['url'] = (string)$_REQUEST['url'];
} elseif (!array_key_exists('url', $data)) {
	$data['url'] = SimpleSAML_Module::getModuleURL('exampleattributeserver/attributeserver.php');
}

if (!array_key_exists('attributes', $data)) {
	$data['attributes'] = NULL;
}

$session->setData('attributequeryexample:data', $dataId, $data, 3600);

if (array_key_exists('send', $_REQUEST)) {

	$nameId = array(
		'Format' => $data['nameIdFormat'],
		'Value' => $data['nameIdValue'],
		'NameQualifier' => $data['nameIdQualifier'],
		'SPNameQualifier' => $data['nameIdSPQualifier'],
	);
	if (empty($nameId['NameQualifier'])) {
		$nameId['NameQualifier'] = NULL;
	}
	if (empty($nameId['SPNameQualifier'])) {
		$nameId['SPNameQualifier'] = NULL;
	}

	sendQuery($dataId, $data['url'], $nameId);
}

$t = new SimpleSAML_XHTML_Template(SimpleSAML_Configuration::getInstance(), 'attributequery.php');
$t->data['dataId'] = $dataId;
$t->data['url'] = $data['url'];
$t->data['nameIdFormat'] = $data['nameIdFormat'];
$t->data['nameIdValue'] = $data['nameIdValue'];
$t->data['nameIdQualifier'] = $data['nameIdQualifier'];
$t->data['nameIdSPQualifier'] = $data['nameIdSPQualifier'];
$t->data['attributes'] = $data['attributes'];

$t->show();
