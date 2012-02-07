<?php
/*
* COAUTHOR: Samuel Muñoz Hidalgo
* EMAIL: samuel.mh@gmail.com
* LAST REVISION: 13-FEB-09
* DESCRIPTION: Things the STS can do
*		- InfoCard issue
*		- Error response (if the user send us wrong credentials)
*		- Request Security Token Response
*/

class sspmod_InfoCard_STS {


/*
* USED IN: www/getcardform.php
* INPUT: data and configuration
* OUTPUT; a custom error message for the identity selector
*/
	static public function createCard($ICdata,$ICconfig) {
		
		$infocardbuf  = '<Object Id="IC01" xmlns="http://www.w3.org/2000/09/xmldsig#">';
		$infocardbuf .= '<InformationCard xml:lang="en-us"  xmlns="http://schemas.xmlsoap.org/ws/2005/05/identity" xmlns:wsa="http://www.w3.org/2005/08/addressing" xmlns:wst="http://schemas.xmlsoap.org/ws/2005/02/trust" xmlns:wsx="http://schemas.xmlsoap.org/ws/2004/09/mex">';
	
		//cardId
		$infocardbuf .= '<InformationCardReference>';	
			$infocardbuf .= '<CardId>'.$ICdata['CardId'].'</CardId>'; //xs:anyURI cardId (="$cardurl/$ppid";  $ppid = "$uname-" . time();)
			$infocardbuf .= '<CardVersion>1</CardVersion>';  //xs:unsignedInt
		$infocardbuf .= '</InformationCardReference>';
	
		//cardName
		$infocardbuf .= '<CardName>'.$ICdata['CardName'].'</CardName>';
	
		//image
		$infocardbuf .= '<CardImage MimeType="'.mime_content_type($ICdata['CardImage']).'">';
			$infocardbuf .= base64_encode(file_get_contents($ICdata['CardImage']));
		$infocardbuf .= '</CardImage>';
	
		//issuer - times
		$infocardbuf .= '<Issuer>'.$ICconfig['InfoCard']['issuer'].'</Issuer>';
		$infocardbuf .= '<TimeIssued>'.gmdate('Y-m-d').'T'.gmdate('H:i:s').'Z'.'</TimeIssued>';
		$infocardbuf .= '<TimeExpires>'.$ICdata['TimeExpires'].'</TimeExpires>';
	
		//Token Service List
		$infocardbuf .= '<TokenServiceList>';	
			$infocardbuf .= '<TokenService>';
				$infocardbuf .= '<wsa:EndpointReference>';
					$infocardbuf .= '<wsa:Address>'.$ICconfig['tokenserviceurl'].'</wsa:Address>';	
					$infocardbuf .= '<wsa:Metadata>';
						$infocardbuf .= '<wsx:Metadata>';
							$infocardbuf .= '<wsx:MetadataSection>';
								$infocardbuf .= '<wsx:MetadataReference>';
									$infocardbuf .= '<wsa:Address>'.$ICconfig['mexurl'].'</wsa:Address>';
								$infocardbuf .= '</wsx:MetadataReference>';
							$infocardbuf .= '</wsx:MetadataSection>';
						$infocardbuf .= '</wsx:Metadata>';
					$infocardbuf .= '</wsa:Metadata>';
				$infocardbuf .= '</wsa:EndpointReference>';
	
	
	
				/*Types of User Credentials 
				*  Supported: UsernamePasswordCredential, SelfIssuedCredential
				*  Unsupported: KerberosV5Credential, X509V3Credential
				*/
				$infocardbuf .= '<UserCredential>';
						$infocardbuf .= '<DisplayCredentialHint>'.$ICdata['DisplayCredentialHint'].'</DisplayCredentialHint>';
				switch($ICconfig['UserCredential']){
					case 'UsernamePasswordCredential':
						$infocardbuf .= '<UsernamePasswordCredential>';
							$infocardbuf .= '<Username>'.$ICdata['UserName'].'</Username>';
						$infocardbuf .= '</UsernamePasswordCredential>';
						break;
					case 'KerberosV5Credential':
						$infocardbuf .= '<KerberosV5Credential/>';
						break;
					case 'X509V3Credential':
						$infocardbuf .= '<X509V3Credential>';
							$infocardbuf .= '<ds:X509Data>';
								$infocardbuf .= '<wsse:KeyIdentifier ValueType="http://docs.oasis-open.org/wss/2004/xx/oasis-2004xx-wss-soap-message-security-1.1#ThumbprintSHA1" EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis200401-wss-soap-message-security-1.0#Base64Binary">';
								/*This element provides a key identifier for the X.509 certificate based on the SHA1 hash
								of the entire certificate content expressed as a “thumbprint.” Note that the extensibility
								point in the ds:X509Data element is used to add wsse:KeyIdentifier as a child
								element.*/ 
								$infocardbuf .= $ICdata['KeyIdentifier']; //xs:base64binary;
								$infocardbuf .= '</wsse:KeyIdentifier>';
							$infocardbuf .= '</ds:X509Data>';
						$infocardbuf .= '</X509V3Credential>';
						break;
					case 'SelfIssuedCredential':
						$infocardbuf .= '<SelfIssuedCredential>';
							$infocardbuf .= '<PrivatePersonalIdentifier>';
								$infocardbuf .= $ICdata['PPID']; //xs:base64binary;
								$infocardbuf .= '</PrivatePersonalIdentifier>';
						$infocardbuf .= '</SelfIssuedCredential> ';
						break;
					default:
						break;
				}
				$infocardbuf .= '</UserCredential>';
	
			$infocardbuf .= '</TokenService>';
		$infocardbuf .= '</TokenServiceList>';
	
	
		//Tokentype
		$infocardbuf .= '<SupportedTokenTypeList>';
			$infocardbuf .= '<wst:TokenType>urn:oasis:names:tc:SAML:1.0:assertion</wst:TokenType>';
		$infocardbuf .= '</SupportedTokenTypeList>';
			
		//Claims
		$infocardbuf .= '<SupportedClaimTypeList>';
		$url = $ICconfig['InfoCard']['schema'].'/claims/';
		foreach ($ICconfig['InfoCard']['requiredClaims'] as $claim=>$data) {  
			$infocardbuf .= '<SupportedClaimType Uri="'.$url.$claim.'">';
				$infocardbuf .= '<DisplayTag>'.$data['displayTag'].'</DisplayTag>';
				$infocardbuf .= '<Description>'.$data['description'].'</Description>';
			$infocardbuf .= '</SupportedClaimType>';
		}
		foreach ($ICconfig['InfoCard']['optionalClaims'] as $claim=>$data) {  
			$infocardbuf .= '<SupportedClaimType Uri="'.$url.$claim.'">';
				$infocardbuf .= '<DisplayTag>'.$data['displayTag'].'</DisplayTag>';
				$infocardbuf .= '<Description>'.$data['description'].'</Description>';
			$infocardbuf .= '</SupportedClaimType>';
		}	
		$infocardbuf .= '</SupportedClaimTypeList>';
	
		//Privacy URL
		$infocardbuf .= '<PrivacyNotice>'.$ICconfig['InfoCard']['privacyURL'].'</PrivacyNotice>';
	
		$infocardbuf .= '</InformationCard>';
		$infocardbuf .= '</Object>';
				
		
		$canonicalbuf = sspmod_InfoCard_Utils::canonicalize($infocardbuf);
		
		//construct a SignedInfo block
		$signedinfo  = '<SignedInfo  xmlns="http://www.w3.org/2000/09/xmldsig#">';
			$signedinfo .= '<CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>';
			$signedinfo .= '<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>';
			$signedinfo .= '<Reference URI="#IC01">';
				$signedinfo .= '<Transforms>';
					$signedinfo .= '<Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>';
				$signedinfo .= '</Transforms>';
				$signedinfo .= '<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>';
				$signedinfo .= '<DigestValue>'.base64_encode(sha1($canonicalbuf, TRUE)).'</DigestValue>';
			$signedinfo .= '</Reference>';
		$signedinfo .= '</SignedInfo>';
	
		$canonicalbuf = sspmod_InfoCard_Utils::canonicalize($signedinfo);
	
		$signature = '';
		$privkey = openssl_pkey_get_private(file_get_contents($ICconfig['sts_key']));
		openssl_sign($canonicalbuf, $signature, $privkey);
		openssl_free_key($privkey);
		$infocard_signature = base64_encode($signature);
		
		//Envelope
		$buf = '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">';
			$buf .= $signedinfo;
			$buf .= '<SignatureValue>'.$infocard_signature.'</SignatureValue>';
			$buf .= '<KeyInfo>';
				$buf .= '<X509Data>';
			// signing certificate(s)
			foreach ($ICconfig['certificates'] as $idx=>$cert)
					$buf .= '<X509Certificate>'.sspmod_InfoCard_Utils::takeCert($cert).'</X509Certificate>';
				$buf .= '</X509Data>';
			$buf .= '</KeyInfo>';
			$buf .= $infocardbuf;
		$buf .= '</Signature>';
	
		return $buf;
	}




/*
* USED IN: www/tokenservice.php
* INPUT: error message, uuid of the RST
* OUTPUT; a custom error message for the identity selector
*/
	static public function errorMessage($msg,$relatesto){
		$buf = '<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope" xmlns:a="http://www.w3.org/2005/08/addressing">';
			$buf .= '<s:Header>';
				$buf .= '<a:Action s:mustUnderstand="1">http://www.w3.org/2005/08/addressing/soap/fault</a:Action>';
				$buf .= '<a:RelatesTo>'.$relatesto.'</a:RelatesTo>';
			$buf .= '</s:Header>';
			$buf .= '<s:Body>';
				$buf .= '<s:Fault>';
					$buf .= '<s:Code>';
						$buf .= '<s:Value xmlns:a="http://www.w3.org/2003/05/soap-envelope">';
							$buf .= 'a:Sender';
						$buf .= '</s:Value>';
						$buf .= '<s:Subcode>';
							$buf .= '<s:Value xmlns:a="http://schemas.xmlsoap.org/ws/2005/05/identity">';
								$buf .= 'a:MissingAppliesTo';
						$buf .= '</s:Value>';
						$buf .= '</s:Subcode>';
					$buf .= '</s:Code>';
					$buf .= '<s:Reason>';
						$buf .= '<s:Text xml:lang="en">';
							$buf .= $msg;
						$buf .= '</s:Text>';
					$buf .= '</s:Reason>';
				$buf .= '</s:Fault>';
			$buf .= '</s:Body>';
		$buf .= '</s:Envelope>';
		return $buf;
	}



/*
* USED IN: www/tokenservice.php
* INPUT: claims value, configuration, uuid of the RST
* OUTPUT; a security token for the identity selector
*/
	static public function createToken($claimValues,$config,$relatesto){
		$assertionid = uniqid('uuid-');
		$created = gmdate('Y-m-d').'T'.gmdate('H:i:s').'Z';
		$expires = gmdate('Y-m-d', time()+3600).'T'.gmdate('H:i:s', time()+3600).'Z';
		

		//SOAP ENVELOPE
		$env = '<?xml version="1.0"?>';
		$env .= '<S:Envelope xmlns:S="http://www.w3.org/2003/05/soap-envelope" xmlns:wsa="http://www.w3.org/2005/08/addressing"  xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:ic="http://schemas.xmlsoap.org/ws/2005/05/identity" xmlns:wst="http://schemas.xmlsoap.org/ws/2005/02/trust" xmlns:xenc="http://www.w3.org/2001/04/xmlenc" xmlns:ds="http://www.w3.org/2000/09/xmldsig#">';
	
			$env .= '<S:Header>';
				$env .= '<wsa:Action wsu:Id="_1">';
					$env .= 'http://schemas.xmlsoap.org/ws/2005/02/trust/RSTR/Issue';
				$env .= '</wsa:Action>';
				$env .= '<wsa:RelatesTo wsu:Id="_2">';
					$env .= $relatesto;
				$env .= '</wsa:RelatesTo>';
				$env .= '<wsa:To wsu:id="_3">';
					$env .= 'http://www.w3.org/2005/08/addressing/anonymous';
				$env .= '</wsa:To>';
				$env .= '<wsse:Security S:mustUnderstand="1">';
					$env .= '<wsu:Timestamp wsu:Id="_6">';
						$env .= '<wsu:Created>'.$created.'</wsu:Created>';
						$env .= '<wsu:Expires>'.$expires.'</wsu:Expires>';
					$env .= '</wsu:Timestamp>';
				$env .= '</wsse:Security>';
			$env .= '</S:Header>';
			
			
			$env .= '<S:Body wsu:Id="_10">';
				//RequestSecurityTokenResponse
				$env .= sspmod_InfoCard_STS::RequestSecurityTokenResponse($claimValues,$config,$assertionid,$created,$expires);
			$env .= '</S:Body>';
		$env .= '</S:Envelope>';
				
		return $env;
	}



/*
* USED IN: createToken
* INPUT: claims value, configuration, uuid, times
* OUTPUT; returns the <wst:RequestSecurityTokenResponse>' of the RSTR
*/
	static private function RequestSecurityTokenResponse ($claimValues,$config,$assertionid,$created,$expires){
		$tr = '<wst:RequestSecurityTokenResponse>';
			$tr .= '<wst:TokenType>urn:oasis:names:tc:SAML:1.0:assertion</wst:TokenType>';
			$tr .= '<wst:LifeTime>';
				$tr .= '<wsu:Created>'.$created.'</wsu:Created>';
				$tr .= '<wsu:Expires>'.$expires.'</wsu:Expires>';
			$tr .= '</wst:LifeTime>';
			
			//Encrypted token: SAML assertion
			$tr .= '<wst:RequestedSecurityToken>';
				$tr .= sspmod_InfoCard_STS::saml_assertion($claimValues,$config,$assertionid,$created,$expires);
			$tr .= '</wst:RequestedSecurityToken>';
			
			//RequestedAattachedReference
			$tr .= '<wst:RequestedAttachedReference>';
				$tr .= '<wsse:SecurityTokenReference>';
					$tr .= '<wsse:KeyIdentifier ValueType="http://docs.oasis-open.org/wss/oasis-wss-saml-token-profile-1.0#SAMLAssertionID">';
						$tr .= $assertionid;
					$tr .= '</wsse:KeyIdentifier>';
				$tr .= '</wsse:SecurityTokenReference>';
			$tr .= '</wst:RequestedAttachedReference>';
			
			//RequestedUnattachedReference
			$tr .= '<wst:RequestedUnattachedReference>';
				$tr .= '<wsse:SecurityTokenReference>';
					$tr .= '<wsse:KeyIdentifier ValueType="http://docs.oasis-open.org/wss/oasis-wss-saml-token-profile-1.0#SAMLAssertionID">';
						$tr .= $assertionid;
					$tr .= '</wsse:KeyIdentifier>';
				$tr .= '</wsse:SecurityTokenReference>';
			$tr .= '</wst:RequestedUnattachedReference>';
	
			//RequestedDisplayToken
			$tr .= '<ic:RequestedDisplayToken>';
				$tr .= '<ic:DisplayToken xml:lang="en-us">';
				foreach ($claimValues as $claim=>$data) {
					$tr .= '<ic:DisplayClaim Uri="'.$config['InfoCard']['schema'].'/claims/'.$claim.'">';
						$tr .= '<ic:DisplayTag>'.$data['displayTag'].'</ic:DisplayTag>';
						$tr .= '<ic:DisplayValue>'.$data['value'].'</ic:DisplayValue>';
					$tr .= "</ic:DisplayClaim>";
				}
				$tr .= '</ic:DisplayToken>';
			$tr .= '</ic:RequestedDisplayToken>';
		$tr .= '</wst:RequestSecurityTokenResponse>';
		return $tr;
	}




/*
* USED IN: RequestSecurityTokenResponse
* INPUT: claims value, configuration, uuid, times
* OUTPUT; STS Signed SAML assertion
*/
	static private function saml_assertion($claimValues,$config,$assertionid,$created,$expires){
		$saml = '<saml:Assertion MajorVersion="1" MinorVersion="0" AssertionID="'.$assertionid.'" Issuer="'.$config['issuer'].'" IssueInstant="'.$created.'" xmlns:saml="urn:oasis:names:tc:SAML:1.0:assertion">';
			$saml .= '<saml:Conditions NotBefore="'.$created.'" NotOnOrAfter="'.$expires.'" />';
			$saml .= '<saml:AttributeStatement>';
				$saml .= '<saml:Subject>';
					$saml .= '<saml:SubjectConfirmation>';
						$saml .= '<saml:ConfirmationMethod>urn:oasis:names:tc:SAML:1.0:cm:holder-of-key</saml:ConfirmationMethod>';
						// proof key
						$saml .= '<dsig:KeyInfo xmlns:dsig="http://www.w3.org/2000/09/xmldsig#">';
							$saml .= '<dsig:X509Data>';
								$saml .= '<dsig:X509Certificate>'.sspmod_InfoCard_Utils::takeCert($config['sts_crt']).'</dsig:X509Certificate>';
							$saml .= '</dsig:X509Data>';
						$saml .= '</dsig:KeyInfo>';
					$saml .= '</saml:SubjectConfirmation>';
				$saml .= '</saml:Subject>';
				foreach ($claimValues as $claim=>$data) {
					$saml .= '<saml:Attribute AttributeName="'.$claim.'" AttributeNamespace="'.$config['InfoCard']['schema'].'/claims">';
						$saml .= '<saml:AttributeValue>'.$data['value'].'</saml:AttributeValue>';
					$saml .= '</saml:Attribute>';
				}
			$saml .= '</saml:AttributeStatement>';
	
			//Pure SAML Assertion digest
			$canonicalbuf = sspmod_InfoCard_Utils::canonicalize($saml.'</saml:Assertion>');
			$myhash = sha1($canonicalbuf,TRUE);
			$samldigest = base64_encode($myhash);
	
			//Digest block
			$signedinfo = '<dsig:SignedInfo xmlns:dsig="http://www.w3.org/2000/09/xmldsig#" >';
				$signedinfo .= '<dsig:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#" />';
				$signedinfo .= '<dsig:SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1" />';
				$signedinfo .= '<dsig:Reference URI="#'.$assertionid.'">';
					$signedinfo .= '<dsig:Transforms>';
						$signedinfo .= '<dsig:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature" />';
						$signedinfo .= '<dsig:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#" />';
					$signedinfo .= '</dsig:Transforms>';
					$signedinfo .= '<dsig:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1" />';
					$signedinfo .= '<dsig:DigestValue>'.$samldigest.'</dsig:DigestValue>';
				$signedinfo .= '</dsig:Reference>';
			$signedinfo .= '</dsig:SignedInfo>';
			
			//Signature of the digest
			$canonicalbuf = sspmod_InfoCard_Utils::canonicalize($signedinfo);
			$privkey = openssl_pkey_get_private(file_get_contents($config['sts_key']));
			$signature = '';
			openssl_sign($canonicalbuf, &$signature, $privkey);
			openssl_free_key($privkey);
			$samlsignature = base64_encode($signature);
	
			//Signature block
			$saml .= '<dsig:Signature xmlns:dsig="http://www.w3.org/2000/09/xmldsig#">';
				$saml .= $signedinfo;
				$saml .= '<dsig:SignatureValue>'.$samlsignature.'</dsig:SignatureValue>';
				$saml .= '<dsig:KeyInfo>';
					$saml .= '<dsig:X509Data>';
						$saml .= '<dsig:X509Certificate>'.sspmod_InfoCard_Utils::takeCert($config['sts_crt']).'</dsig:X509Certificate>';
					$saml .= '</dsig:X509Data>';
				$saml .= '</dsig:KeyInfo>';
			$saml .= '</dsig:Signature>';
		$saml .= '</saml:Assertion>';
		return $saml;
	}
	

}

?>