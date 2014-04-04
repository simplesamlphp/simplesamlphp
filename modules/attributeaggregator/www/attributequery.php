<?php

$session = SimpleSAML_Session::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

if (!array_key_exists('StateId', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest(
			'[attributeaggregator] - Missing required StateId query parameter.'
	);
}

$id = $_REQUEST['StateId'];
$state = SimpleSAML_Auth_State::loadState($id, 'attributeaggregator:request');
SimpleSAML_Logger::info('[attributeaggregator] - Querying attributes from ' . $state['attributeaggregator:entityId'] );
$aaMetadata = $metadata->getMetadata($state['attributeaggregator:entityId'],'attributeauthority-remote');

/* Find an AttributeService with SOAP binding */
$aas = $aaMetadata['AttributeService'];
for ($i=0;$i<count($aas);$i++){
	if ($aas[$i]['Binding'] == SAML2_Const::BINDING_SOAP){
		$index = $i;
		break;
	}
}

if (empty($aas[$index]['Location'])) {
	throw new SimplesSAML_Error("Can't find the AttributeService endpoint to send the attribute query.");
}
$url = $aas[$index]['Location'];

/* nameId */
$data['nameIdValue'] = $state['attributeaggregator:attributeId'][0];
$data['nameIdFormat'] = $state['attributeaggregator:nameIdFormat'];
$data['nameIdQualifier'] = '';
$data['nameIdSPQualifier'] = '';

/* VO AttributeAuthority endpoint */
$data['url'] = $url;
$data['stateId'] = $id;


/* Building the query */

$dataId = SimpleSAML_Utilities::generateID();
$session->setData('attributeaggregator:data', $dataId, $data, 3600);

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

$attributes = $state['attributeaggregator:attributes'];
$attributes_to_send = array();
foreach ($attributes as $name => $params) {
	if (array_key_exists('values', $params)){
		$attributes_to_send[$name] = $params['values'];
	}
	else {
		$attributes_to_send[$name] = array();
	}
}

$attributeNameFormat = $state['attributeaggregator:attributeNameFormat'];

$authsource = SimpleSAML_Auth_Source::getById($state["attributeaggregator:authsourceId"]);
$src = $authsource->getMetadata();
$dst = $metadata->getMetaDataConfig($state['attributeaggregator:entityId'],'attributeauthority-remote');

// Sending query
try {
	$response = sendQuery($dataId, $data['url'], $nameId, $attributes_to_send, $attributeNameFormat, $src, $dst);	
} catch (Exception $e) {
	throw new SimpleSAML_Error_Exception('[attributeaggregator] Error in sending query. ' .$e);
}


 /* Getting the response */
SimpleSAML_Logger::debug('[attributeaggregator] attributequery - getting response');

if (!($response instanceof SAML2_Response)) {
	throw new SimpleSAML_Error_Exception('Unexpected message received in response to the attribute query.');
}

$idpEntityId = $response->getIssuer();
if ($idpEntityId === NULL) {
	throw new SimpleSAML_Error_Exception('Missing issuer in response.');
}
$assertions = $response->getAssertions();
$attributes_from_aa = $assertions[0]->getAttributes();
$expected_attributes = $state['attributeaggregator:attributes'];
// get attributes from response, and put it in the state.
foreach ($attributes_from_aa as $name=>$values){
	// expected?
	if (array_key_exists($name, $expected_attributes)){
		// There is in the existing attributes?
		if(array_key_exists($name, $state['Attributes'])){
			// has multiSource rule?
			if (! empty($expected_attributes[$name]['multiSource'])){
				switch ($expected_attributes[$name]['multiSource']) {
					case 'override':
						$state['Attributes'][$name] = $values;
						break;
					case 'keep':
						continue;
						break;
					case 'merge':
						$state['Attributes'][$name] = array_merge($state['Attributes'][$name],$values);
						break;					
				}
			}
			// default: merge the attributes
			else {
				$state['Attributes'][$name] = array_merge($state['Attributes'][$name],$values);
			}
		}
		// There is not in the existing attributes, create it.
		else {
			$state['Attributes'][$name] = $values;
		}
	}
	// not expected? Put it to attributes array.
	else {
		if (!empty($state['Attributes'][$name])){
			$state['Attributes'][$name] = array_merge($state['Attributes'][$name],$values);
		}
		else
			$state['Attributes'][$name] = $values;
	}
}

SimpleSAML_Logger::debug('[attributeaggregator] - Attributes now:'.var_export($state['Attributes'],true));
SimpleSAML_Auth_ProcessingChain::resumeProcessing($state);
exit;

/**
 * build and send AttributeQuery
 */
function sendQuery($dataId, $url, $nameId, $attributes, $attributeNameFormat,$src,$dst) {
	assert('is_string($dataId)');
	assert('is_string($url)');
	assert('is_array($nameId)');
	assert('is_array($attributes)');

	SimpleSAML_Logger::debug('[attributeaggregator] - sending request');

	$query = new SAML2_AttributeQuery();
	$query->setRelayState($dataId);
	$query->setDestination($url);
	$query->setIssuer($src->getValue('entityid'));
	$query->setNameId($nameId);
	$query->setAttributeNameFormat($attributeNameFormat);
	if (! empty($attributes)){
		$query->setAttributes($attributes);
	}
	sspmod_saml_Message::addSign($src,$dst,$query);

	if (! $query->getSignatureKey()){
		throw new SimpleSAML_Error_Exception('[attributeaggregator] - Unable to find private key for signing attribute request.');
	}
	
	SimpleSAML_Logger::debug('[attributeaggregator] - sending attribute query: '.var_export($query,1));
	$binding = new SAML2_SOAPClient();

	$result = $binding->send($query,$src);
	return $result;
}
