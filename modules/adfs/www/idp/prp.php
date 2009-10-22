<?php
/**
 * ADFS PRP IDP protocol support for simpleSAMLphp.
 *
 * @author Hans Zandbelt, SURFnet BV. <hans.zandbelt@surfnet.nl>
 * @package simpleSAMLphp
 * @version $Id$
 */

$config = SimpleSAML_Configuration::getInstance();
$adfsconfig = SimpleSAML_Configuration::getConfig('adfs-idp-hosted.php');
$session = SimpleSAML_Session::getInstance();

SimpleSAML_Logger::info('ADFS - IdP.SSOService: Accessing ADFS IdP endpoint SSOService');

try {
	if (array_key_exists('entityId', $config)) {
		$idpentityid = $config['entityId'];
	} else {
		$idpentityid = 'urn:federation:' . SimpleSAML_Utilities::getSelfHost() . ':idp';
	}
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}

SimpleSAML_Logger::info('ADFS - IdP.SSOService: Accessing ADFS IdP endpoint SSOService');

function ADFS_GenerateResponse($issuer, $target, $nameid, $attributes) {
#	$nameid = 'hans@surfnet.nl';
	$issueInstant = SimpleSAML_Utilities::generateTimestamp();
	$notBefore = SimpleSAML_Utilities::generateTimestamp(time() - 30);
	$assertionExpire = SimpleSAML_Utilities::generateTimestamp(time() + 60 * 5);
	$assertionID = SimpleSAML_Utilities::generateID();
	$nameidFormat = 'http://schemas.xmlsoap.org/claims/UPN';
	$result =
'<wst:RequestSecurityTokenResponse xmlns:wst="http://schemas.xmlsoap.org/ws/2005/02/trust">
   <wst:RequestedSecurityToken>
     <saml:Assertion Issuer="' . $issuer . '" IssueInstant="' . $issueInstant . '" AssertionID="' . $assertionID . '" MinorVersion="1" MajorVersion="1" xmlns:saml="urn:oasis:names:tc:SAML:1.0:assertion">
       <saml:Conditions NotOnOrAfter="' . $assertionExpire . '" NotBefore="' . $notBefore . '">
         <saml:AudienceRestrictionCondition>
           <saml:Audience>' . $target .'</saml:Audience>
         </saml:AudienceRestrictionCondition>
       </saml:Conditions>
       <saml:AuthenticationStatement AuthenticationMethod="urn:oasis:names:tc:SAML:1.0:am:unspecified" AuthenticationInstant="' . $issueInstant . '">
         <saml:Subject>
           <saml:NameIdentifier Format="' . $nameidFormat . '">' . htmlspecialchars($nameid) . '</saml:NameIdentifier>
         </saml:Subject>
       </saml:AuthenticationStatement>
       <saml:AttributeStatement>
         <saml:Subject>
           <saml:NameIdentifier Format="' . $nameidFormat . '">' . htmlspecialchars($nameid) . '</saml:NameIdentifier>
         </saml:Subject>';
	foreach ($attributes as $name => $values) {
		if ((!is_array($values)) || (count($values) == 0)) continue;
		$hasValue = FALSE;
		$r = '<saml:Attribute AttributeNamespace="http://schemas.xmlsoap.org/claims" AttributeName="' . htmlspecialchars($name) .'">';
		foreach ($values as $value) {
			if ( (!isset($value)) or ($value === '')) continue;
			$r .= '<saml:AttributeValue>' . htmlspecialchars($value) . '</saml:AttributeValue>';
			$hasValue = TRUE;
		}
		$r .= '</saml:Attribute>';
		if ($hasValue) $result .= $r;
	}
	$result .= '
       </saml:AttributeStatement>
     </saml:Assertion>
   </wst:RequestedSecurityToken>
   <wsp:AppliesTo xmlns:wsp="http://schemas.xmlsoap.org/ws/2004/09/policy"><wsa:EndpointReference xmlns:wsa="http://schemas.xmlsoap.org/ws/2004/08/addressing">
     <wsa:Address>' . $target . '</wsa:Address>
   </wsa:EndpointReference></wsp:AppliesTo>
 </wst:RequestSecurityTokenResponse>';
	return $result;
}

function ADFS_SignResponse($response, $key, $cert) {
	$objXMLSecDSig = new XMLSecurityDSig();
	$objXMLSecDSig->idKeys = array('AssertionID');	
	$objXMLSecDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);	
	$responsedom = new DOMDocument();
	$responsedom->loadXML(str_replace ("\r", "", $response));
	$firstassertionroot = $responsedom->getElementsByTagName('Assertion')->item(0);
	$objXMLSecDSig->addReferenceList(array($firstassertionroot), XMLSecurityDSig::SHA1,
		array('http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N),
		array('id_name' => 'AssertionID'));
	$objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'private'));
	$objKey->loadKey($key, TRUE);
	$objXMLSecDSig->sign($objKey);
	if ($cert) {
		$public_cert = file_get_contents($cert);
		$objXMLSecDSig->add509Cert($public_cert, TRUE);
	}
	$newSig = $responsedom->importNode($objXMLSecDSig->sigNode, TRUE);
	$firstassertionroot->appendChild($newSig);	
	return $responsedom->saveXML();
}

function ADFS_PostResponse($url, $wresult, $wctx) {
	print '
<body onload="document.forms[0].submit()"><form method="post" action="' . $url . '">
	<input type="hidden" name="wa" value="wsignin1.0">
	<input type="hidden" name="wresult" value="' . htmlspecialchars($wresult) . '">
	<input type="hidden" name="wctx" value="' . htmlspecialchars($wctx) . '">
	<noscript><input type="submit" value="Continue"></noscript>
</form></body>';
	exit;
}

if (isset($_GET['wa'])) {

	if ($_GET['wa'] == 'wsignin1.0') {
		try {
			// accomodate for disfunctional $_GET "windows" slash decoding in PHP
			$wctx = $_GET['wctx'];
			foreach (explode('&', $_SERVER['REQUEST_URI']) as $e) {
				$a = explode('=', $e);
				if ($a[0] == 'wctx') $wctx = urldecode($a[1]);
			}
			$requestid = $wctx;
			$issuer = $_GET['wtrealm'];
			$requestcache = array(
				'RequestID' => $requestid,
				'Issuer' => $issuer,
				'RelayState' => $requestid
			);

			$spentityid = $requestcache['Issuer'];
		
			SimpleSAML_Logger::info('ADFS - IdP.SSOService: Incoming Authentication request: '.$issuer.' id '.$requestid);
	
		} catch(Exception $exception) {
			SimpleSAML_Utilities::fatalError($session->getTrackID(), 'PROCESSAUTHNREQUEST', $exception);
		}
	}

} elseif(isset($_GET['RequestID'])) {

	try {
	
		SimpleSAML_Logger::info('ADFS - IdP.SSOService: Got incoming authentication ID');
		
		$authId = $_GET['RequestID'];
		$requestcache = $session->getAuthnRequest('adfs', $authId);
		if (!$requestcache) {
			throw new Exception('Could not retrieve cached RequestID = ' . $authId);
		}
		
	} catch(Exception $exception) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'CACHEAUTHNREQUEST', $exception);
	}
	
} elseif(isset($_REQUEST[SimpleSAML_Auth_ProcessingChain::AUTHPARAM])) {

	$authProcId = $_REQUEST[SimpleSAML_Auth_ProcessingChain::AUTHPARAM];
	$authProcState = SimpleSAML_Auth_ProcessingChain::fetchProcessedState($authProcId);
	$requestcache = $authProcState['core:adfs-idp:requestcache'];

} else {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'SSOSERVICEPARAMS');
}

if(SimpleSAML_Auth_Source::getById($adfsconfig->getValue('auth')) !== NULL) {
	$authSource = TRUE;
	$authority = $adfsconfig->getValue('auth');
} else {
	$authSource = FALSE;
	$authority = $adfsconfig->getValue('authority');
}

if (!$session->isValid($authority) ) {

	SimpleSAML_Logger::info('ADFS - IdP.SSOService: Will go to authentication module ' . $adfsconfig->getValue('auth'));

	$authId = SimpleSAML_Utilities::generateID();
	$session->setAuthnRequest('adfs', $authId, $requestcache);

	$redirectTo = SimpleSAML_Utilities::selfURLNoQuery() . '?RequestID=' . urlencode($authId);

	if($authSource) {

		SimpleSAML_Auth_Default::initLogin($adfsconfig->getValue('auth'), $redirectTo);
	} else {
		$authurl = '/' . $config->getBaseURL() . $adfsconfig->getValue('auth');

		SimpleSAML_Utilities::redirect($authurl, array(
		       'RelayState' => $redirectTo,
		       'AuthId' => $authId,
		       'protocol' => 'adfs',
		));
	}

} else {

	try {
	
		$spentityid = $requestcache['Issuer'];
		$spmetadata = SimpleSAML_Configuration::getConfig('adfs-sp-remote.php');
		
		$arr = $spmetadata->getValue($spentityid);
		if (!isset($arr)) {
			throw new Exception('Metadata for ADFS SP "' . $spentityid . '" could not be found in adfs-sp-remote.php!');
		}
		$spmetadata = SimpleSAML_Configuration::loadFromArray($arr);

		$sp_name = $spmetadata->getValue('name', $spentityid);

		SimpleSAML_Logger::info('ADFS - IdP.SSOService: Sending back AuthnResponse to ' . $spentityid);
		
		$attributes = $session->getAttributes();
		
		if (!isset($authProcState)) {

			$idpap = $adfsconfig->getValue('authproc');
			if ($idpap) $idpap = array('authproc' => $idpap); else $idpap = array();
			$idpap['entityid'] = $idpentityid;
			
			$spap = $spmetadata->getValue('authproc');
			if ($spap) $spap = array('authproc' => $spap); else $spap = array();
			$spap['entityid'] = $spentityid;
			
			$pc = new SimpleSAML_Auth_ProcessingChain($idpap, $spap, 'idp');

			$authProcState = array(
				'core:adfs-idp:requestcache' => $requestcache,
				'ReturnURL' => SimpleSAML_Utilities::selfURLNoQuery(),
				'Attributes' => $attributes,
				'Destination' => $spap,
				'Source' => $idpap,
				'isPassive' => false,
			);

			$previousSSOTime = $session->getData('adfs-idp-ssotime', $spentityid);
			if ($previousSSOTime !== NULL) {
				$authProcState['PreviousSSOTimestamp'] = $previousSSOTime;
			}

			try {
				$pc->processState($authProcState);
			} catch (SimpleSAML_Error_NoPassive $e) {
				SimpleSAML_Utilities::fatalError($session->getTrackID(), 'GENERATEAUTHNRESPONSE', $exception);
			}

			$requestcache['AuthProcState'] = $authProcState;
		}

		$attributes = $authProcState['Attributes'];

		$session->setData('adfs-idp-ssotime', $spentityid, time(),
			SimpleSAML_Session::DATA_TIMEOUT_LOGOUT);

		$requestID = NULL; $relayState = NULL;
		if (array_key_exists('RequestID', $requestcache)) $requestID = $requestcache['RequestID'];
		if (array_key_exists('RelayState', $requestcache)) $relayState = $requestcache['RelayState'];

		$nameid = $session->getNameID();
		$nameid = $nameid['Value'];
		
		$nameidattribute = $spmetadata->getValue('simplesaml.nameidattribute');
		if (isset($nameidattribute)) {
			if (!array_key_exists($nameidattribute, $attributes)) {
				throw new Exception('simplesaml.nameidattribute does not exist in resulting attribute set');
			}
			$nameid = $attributes[$nameidattribute][0];
		}

		$response = ADFS_GenerateResponse($idpentityid, $spentityid, $nameid, $attributes);
		$wresult = ADFS_SignResponse($response, $config->getPathValue('certdir', 'cert/') . $adfsconfig->getValue('key'), $config->getPathValue('certdir', 'cert/') . $adfsconfig->getValue('cert'));

		ADFS_PostResponse($spmetadata->getValue('prp'), $wresult, $relayState);
		
	} catch(Exception $exception) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'GENERATEAUTHNRESPONSE', $exception);
	}
	
}

?>
