<?php


/*
* COAUTHOR: Samuel Muñoz Hidalgo
* EMAIL: samuel.mh@gmail.com
* LAST REVISION: 22-DEC-08
* DESCRIPTION: InfoCard module Infocard generator
*/

//Generate a raw InfoCard with the given data and the configuration
//NOTA: hay namespaces totalmente innecesarios desde un punto de vista práctico xml, están cubiertos por el nodo
//  Signature, pero si no se ponen, la canonicalización de generación de firma la de comprobación son diferentes
//	y no funciona.
//EJ: xmlns="http://www.w3.org/2000/09/xmldsig#" en los nodos Object y SignedInfo

function create_card($ICdata,$ICconfig) {
		
	$infocardbuf  = "<Object Id=\"IC01\" xmlns=\"http://www.w3.org/2000/09/xmldsig#\">";
	$infocardbuf .= "<InformationCard xml:lang=\"en-us\"  xmlns=\"http://schemas.xmlsoap.org/ws/2005/05/identity\" xmlns:wsa=\"http://www.w3.org/2005/08/addressing\" xmlns:wst=\"http://schemas.xmlsoap.org/ws/2005/02/trust\" xmlns:wsx=\"http://schemas.xmlsoap.org/ws/2004/09/mex\">";

	//cardId
	$infocardbuf .= "<InformationCardReference>";	
	$infocardbuf .= "<CardId>".$ICdata['CardId']."</CardId>"; //xs:anyURI cardId (="$cardurl/$ppid";  $ppid = "$uname-" . time();)
	$infocardbuf .= "<CardVersion>1</CardVersion>";  //xs:unsignedInt
	$infocardbuf .= "</InformationCardReference>";

	//cardName
	$infocardbuf .= "<CardName>".$ICdata['CardName']."</CardName>";

	//image
	$infocardbuf .= "<CardImage MimeType=\"".mime_content_type($ICdata['CardImage'])."\">";
	$infocardbuf .= base64_encode(file_get_contents($ICdata['CardImage']));
	$infocardbuf .= "</CardImage>";

	//issuer - times
	$infocardbuf .= "<Issuer>".$ICconfig['InfoCard']['issuer']."</Issuer>";
	$infocardbuf .= "<TimeIssued>".gmdate('Y-m-d').'T'.gmdate('H:i:s').'Z'."</TimeIssued>";
	$infocardbuf .= "<TimeExpires>".$ICdata['TimeExpires']."</TimeExpires>";

	//Token Service List
	$infocardbuf .= "<TokenServiceList>";	
		$infocardbuf .= "<TokenService>";
			$infocardbuf .= "<wsa:EndpointReference>";
				$infocardbuf .= "<wsa:Address>".$ICconfig['tokenserviceurl']."</wsa:Address>";	
				$infocardbuf .= "<wsa:Metadata>";
					$infocardbuf .= "<wsx:Metadata>";
						$infocardbuf .= "<wsx:MetadataSection>";
							$infocardbuf .= "<wsx:MetadataReference>";
								$infocardbuf .= "<wsa:Address>".$ICconfig['mexurl']."</wsa:Address>";
							$infocardbuf .= "</wsx:MetadataReference>";
						$infocardbuf .= "</wsx:MetadataSection>";
					$infocardbuf .= "</wsx:Metadata>";
				$infocardbuf .= "</wsa:Metadata>";
			$infocardbuf .= "</wsa:EndpointReference>";



			/*Types of User Credentials 
			* UsernamePasswordCredential
			* KerberosV5Credential
			* X509V3Credential
			* SelfIssuedCredential
			*/
			$infocardbuf .= "<UserCredential>";
					$infocardbuf .= "<DisplayCredentialHint>".$ICdata['DisplayCredentialHint']."</DisplayCredentialHint>";
			switch($ICdata['UserCredential']){
				case "UsernamePasswordCredential":
					$infocardbuf .= "<UsernamePasswordCredential>";
						$infocardbuf .= "<Username>".$ICdata['UserName']."</Username>";
					$infocardbuf .= "</UsernamePasswordCredential>";
					break;
				case "KerberosV5Credential":
					$infocardbuf .= "<KerberosV5Credential/>";
					break;
				case "X509V3Credential":
					$infocardbuf .= "<X509V3Credential>";
						$infocardbuf .= "<ds:X509Data>";
							$infocardbuf .= "<wsse:KeyIdentifier ValueType=\"http://docs.oasis-open.org/wss/2004/xx/oasis-2004xx-wss-soap-message-security-1.1#ThumbprintSHA1\" EncodingType=\"http://docs.oasis-open.org/wss/2004/01/oasis200401-wss-soap-message-security-1.0#Base64Binary">
							/*This element provides a key identifier for the X.509 certificate based on the SHA1 hash
							of the entire certificate content expressed as a “thumbprint.” Note that the extensibility
							point in the ds:X509Data element is used to add wsse:KeyIdentifier as a child
							element.*/ 
							$infocardbuf .= $ICdata['KeyIdentifier']; //xs:base64binary;
							$infocardbuf .= "</wsse:KeyIdentifier>";
						$infocardbuf .= "</ds:X509Data>";
					$infocardbuf .= "</X509V3Credential>"; 
					break;
				default: //SelfIssuedCredential
					$infocardbuf .= "<SelfIssuedCredential>";
						$infocardbuf .= "<PrivatePersonalIdentifier>";
							$infocardbuf .= $ICdata['PPID']; //xs:base64binary;
							$infocardbuf .= "</PrivatePersonalIdentifier>";
					$infocardbuf .= "</SelfIssuedCredential> ";
					break;
			}
			$infocardbuf .= "</UserCredential>";

		$infocardbuf .= "</TokenService>";
	$infocardbuf .= "</TokenServiceList>";


	//Tokentype
	$infocardbuf .= "<SupportedTokenTypeList>";
		$infocardbuf .= "<wst:TokenType>urn:oasis:names:tc:SAML:1.0:assertion</wst:TokenType>";
	$infocardbuf .= "</SupportedTokenTypeList>";
    
	//Claims
	$infocardbuf .= "<SupportedClaimTypeList>";
	$url = $ICconfig['InfoCard']['schema']."/claims/";
	foreach ($ICconfig['InfoCard']['requiredClaims'] as $claim=>$data) {  
		$infocardbuf .= "<SupportedClaimType Uri=\"".$url.$claim."\">";
			$infocardbuf .= "<DisplayTag>".$data['displayTag']."</DisplayTag>";
 			$infocardbuf .= "<Description>".$data['description']."</Description>";
		$infocardbuf .= "</SupportedClaimType>";
	}
	foreach ($ICconfig['InfoCard']['optionalClaims'] as $claim=>$data) {  
		$infocardbuf .= "<SupportedClaimType Uri=\"".$url.$claim."\">";
			$infocardbuf .= "<DisplayTag>".$data['displayTag']."</DisplayTag>";
 			$infocardbuf .= "<Description>".$data['description']."</Description>";
		$infocardbuf .= "</SupportedClaimType>";
	}	
	$infocardbuf .= "</SupportedClaimTypeList>";

	//Privacy URL
 	$infocardbuf .= "<PrivacyNotice>".$ICconfig['InfoCard']['privacyURL']."</PrivacyNotice>";

	$infocardbuf .= "</InformationCard>";
	$infocardbuf .= "</Object>";
	
  $canonicalbuf = sspmod_InfoCard_Utils::canonicalize($infocardbuf);
	
	//construct a SignedInfo block
	$signedinfo  = "<SignedInfo  xmlns=\"http://www.w3.org/2000/09/xmldsig#\">";
		$signedinfo .= "<CanonicalizationMethod Algorithm=\"http://www.w3.org/2001/10/xml-exc-c14n#\"/>";
		$signedinfo .= "<SignatureMethod Algorithm=\"http://www.w3.org/2000/09/xmldsig#rsa-sha1\"/>";
		$signedinfo .= "<Reference URI=\"#IC01\">";
			$signedinfo .= "<Transforms>";
				$signedinfo .= "<Transform Algorithm=\"http://www.w3.org/2001/10/xml-exc-c14n#\"/>";
			$signedinfo .= "</Transforms>";
			$signedinfo .= "<DigestMethod Algorithm=\"http://www.w3.org/2000/09/xmldsig#sha1\"/>";
			$signedinfo .= "<DigestValue>".base64_encode(sha1($canonicalbuf, TRUE))."</DigestValue>";
		$signedinfo .= "</Reference>";
	$signedinfo .= "</SignedInfo>";

  $canonicalbuf = sspmod_InfoCard_Utils::canonicalize($signedinfo);

	$signature = '';
	$privkey = openssl_pkey_get_private(file_get_contents($ICconfig['sts_key']));
	openssl_sign($canonicalbuf, &$signature, $privkey);
	openssl_free_key($privkey);
	$infocard_signature = base64_encode($signature);
	
	//Envelope
	$buf = "<Signature xmlns=\"http://www.w3.org/2000/09/xmldsig#\">";
		$buf .= $signedinfo;
		$buf .= "<SignatureValue>".$infocard_signature."</SignatureValue>";
		$buf .= "<KeyInfo>";
			$buf .= "<X509Data>";
		// signing certificate(s)
		foreach ($ICconfig['certificates'] as $idx=>$cert)
				$buf .= "<X509Certificate>".sspmod_InfoCard_Utils::takeCert($cert)."</X509Certificate>";
			$buf .= "</X509Data>";
		$buf .= "</KeyInfo>";
		$buf .= $infocardbuf;
	$buf .= "</Signature>";

	return $buf;
}



$username = $_POST['username'];
$password = $_POST['password'];

if (sspmod_InfoCard_UserFunctions::validateUser($username,$password)){
	
	$config = SimpleSAML_Configuration::getInstance();
	$autoconfig = $config->copyFromBase('logininfocard', 'config-login-infocard.php');
	$ICconfig['InfoCard'] = $autoconfig->getValue('InfoCard');
	$ICconfig['InfoCard']['issuer'] = $autoconfig->getValue('tokenserviceurl');//sspmod_InfoCard_Utils::getIssuer($sts_crt);
	$ICconfig['tokenserviceurl'] = $autoconfig->getValue('tokenserviceurl');
	$ICconfig['mexurl'] = $autoconfig->getValue('mexurl');
	$ICconfig['sts_key'] = $autoconfig->getValue('sts_key');
	$ICconfig['certificates'] = $autoconfig->getValue('certificates');
	
	$ICdata = sspmod_InfoCard_UserFunctions::fillICdata($username);	
	
	$IC = create_card($ICdata,$ICconfig);
	header("Content-Disposition: attachment; filename=\"".$ICdata['CardName'].".crd\"");
	header('Content-Type: application/x-informationcard');
	header('Content-Length:'.strlen($IC));
}else{
	$IC = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\"><html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\"><head><title>ERROR!</title></head><body><h1>Wrong credentials!</h1> Could not authenticate you</body></html>";
}

echo $IC;
?>
