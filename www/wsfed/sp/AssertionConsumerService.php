<?php
/**
 * WS-Federation/ADFS PRP protocol support for simpleSAMLphp.
 *
 * The AssertionConsumerService handler accepts responses from a WS-Federation
 * Account Partner using the Passive Requestor Profile (PRP) and handles it as
 * a Resource Partner.  It receives a response, parses it and passes on the
 * authentication+attributes.
 *
 * @author Hans Zandbelt, SURFnet BV. <hans.zandbelt@surfnet.nl>
 * @package simpleSAMLphp
 * @version $Id$
 */

require_once('../../_include.php');

$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

SimpleSAML_Logger::info('WS-Fed - SP.AssertionConsumerService: Accessing WS-Fed SP endpoint AssertionConsumerService');

if (!$config->getValue('enable.wsfed-sp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');

if (empty($_POST['wresult'])) 
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'ACSPARAMS', $exception);

// verify the response from the Account Partner, containing the assertion
function wsf_verify_response($dom, $cert) {
	$objXMLSecDSig = new XMLSecurityDSig();
	$objXMLSecDSig->idKeys[] = 'AssertionID';
	$signatureElement = $objXMLSecDSig->locateSignature($dom);
	if  (!$signatureElement) {
		throw new Exception('Could not locate XML Signature element.');
	}
	$objXMLSecDSig->canonicalizeSignedInfo();
	if (!$objXMLSecDSig->validateReference()) {
		throw new Exception('XMLsec: digest validation failed');
	}
	$objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'public'));
	$objKey->loadKey($cert, TRUE, TRUE);
	if (! $objXMLSecDSig->verify($objKey)) {
		throw new Exception('Unable to validate Signature');
	}
}

try {
	
	$idpmetadata = $metadata->getMetaData($session->getIdP(), 'wsfed-idp-remote');
	$spmetadata = $metadata->getMetaDataCurrent();

	$wa = $_POST['wa'];
	$wresult = $_POST['wresult'];
	$wctx = $_POST['wctx'];
		
	$attributes = array();
	$dom = new DOMDocument();
	# accommodate for MS-ADFS escaped quotes
	$wresult = str_replace('\"', '"', $wresult);
	$dom->loadXML(str_replace ("\r", "", $wresult));	

	wsf_verify_response($dom, $config->getBaseDir() . $idpmetadata['cert']);

	$session = SimpleSAML_Session::getInstance(true);
	
	$xpath = new DOMXpath($dom);
	$xpath->registerNamespace('wst', 'http://schemas.xmlsoap.org/ws/2005/02/trust');
	$xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:1.0:assertion');
	$assertions = $xpath->query('/wst:RequestSecurityTokenResponse/wst:RequestedSecurityToken/saml:Assertion', $dom->documentElement);
	foreach ($assertions as $assertion) {
		$statement = $xpath->query('saml:AuthenticationStatement', $assertion);
		if (($statement == NULL) or ($statement->item(0) == NULL)) {
			throw new Exception('no authentication statement found');
		}
		// TODO: only process first authentication statement for now;
		$subject = $xpath->query('saml:Subject', $statement->item(0));
		if (($subject == NULL) or ($subject->item(0) == NULL)) {
			throw new Exception('no subject found in authentication statement');
		}
		$nameid = $xpath->query('saml:NameIdentifier', $subject->item(0));
		if (($nameid == NULL) or ($nameid->item(0) == NULL)) {
			throw new Exception('no nameid found in subject in authentication statement');
		}
		$session->setNameID(array(
				'Format' => $nameid->item(0)->getAttribute('Format'),
				'value' => $nameid->item(0)->textContent,
			)
		);
		$statement = $xpath->query('saml:AttributeStatement', $assertion);
		if (($statement != NULL) and ($statement->item(0) != NULL)) {
			foreach ($xpath->query('saml:Attribute/saml:AttributeValue', $statement->item(0)) as $attribute) {
				$name = $attribute->parentNode->getAttribute('AttributeName');
				$value = $attribute->textContent;
				if(!array_key_exists($name, $attributes)) {
					$attributes[$name] = array();
				}
				$attributes[$name][] = $value;
			}
		}
		// TODO: only process first assertion for now;
		break;		
	}	
	$session->setAuthenticated(true, 'wsfed');
	$session->setAttributes($attributes);
		
	SimpleSAML_Utilities::redirect($wctx);
	
} catch(Exception $exception) {		
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'PROCESSASSERTION', $exception);
}
