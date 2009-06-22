<?php

/*
* AUTHOR: Samuel Muñoz Hidalgo
* EMAIL: samuel.mh@gmail.com
* LAST REVISION: 13-FEB-09
* DESCRIPTION: InfoCard module metadata exchange (POLICY)
*/


$method = $_SERVER["REQUEST_METHOD"];

if ($method == "POST"){
	$use_soap = true;
	Header('Content-Type: application/soap+xml;charset=utf-8');
}else{
	$use_soap = false;
	Header('Content-Type: application/xml;charset=utf-8');
}


$config = SimpleSAML_Configuration::getInstance();
$autoconfig = $config->copyFromBase('logininfocard', 'config-login-infocard.php');
$ICconfig['tokenserviceurl'] = $autoconfig->getValue('tokenserviceurl');
$ICconfig['certificates'] = $autoconfig->getValue('certificates');
$ICconfig['UserCredential'] = $autoconfig->getValue('UserCredential');


// Grab the important parts of the token request.  That's pretty much just
// the request ID.
$request_id = '';
if ($use_soap && strlen($HTTP_RAW_POST_DATA))
{
    $token = new DOMDocument();
    $token->loadXML($HTTP_RAW_POST_DATA);
    $doc = $token->documentElement;
    $elements = $doc->getElementsByTagname('MessageID');
    $request_id = $elements->item(0)->nodeValue;
}

$buf = '<?xml version="1.0"?>';

$buf .= '<S:Envelope xmlns:S="http://www.w3.org/2003/05/soap-envelope" xmlns:wsa="http://www.w3.org/2005/08/addressing">';

	$buf .= '<S:Header>';
		$buf .= '<wsa:Action S:mustUnderstand="1">';
			$buf .= 'http://schemas.xmlsoap.org/ws/2004/09/transfer/GetResponse';
		$buf .= '</wsa:Action>';
		$buf .= '<wsa:RelatesTo>';
			$buf .= $request_id;
		$buf .= '</wsa:RelatesTo>';
	$buf .= '</S:Header>';
	
	$buf .= '<S:Body>';	
		$buf .= '<Metadata xmlns="http://schemas.xmlsoap.org/ws/2004/09/mex">';
		
			$buf .= '<MetadataSection Dialect="http://schemas.xmlsoap.org/wsdl/" Identifier="http://schemas.xmlsoap.org/ws/2005/02/trust">';
				$buf .= '<wsdl:definitions name="STS_wsdl" targetNamespace="'.$ICconfig['tokenserviceurl'].'" xmlns:tns="'.$ICconfig['tokenserviceurl'].'" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" xmlns:wsa="http://www.w3.org/2005/08/addressing" xmlns:wst="http://schemas.xmlsoap.org/ws/2005/02/trust" xmlns:wsid="http://schemas.xmlsoap.org/ws/2006/02/addressingidentity" xmlns:wsaw="http://www.w3.org/2006/05/addressing/wsdl" xmlns:wsp="http://schemas.xmlsoap.org/ws/2004/09/policy" xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" xmlns:soap12="http://schemas.xmlsoap.org/wsdl/soap12/" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:ic="http://schemas.xmlsoap.org/ws/2005/05/identity" xmlns:q1="'.$ICconfig['tokenserviceurl'].'">';
				
					$buf .= '<wsdl:types>';
						$buf .= '<xs:schema targetNamespace="http://schemas.xmlsoap.org/ws/2005/02/trust/Imports">';
							$buf .= '<xs:import schemaLocation="" namespace="'.$ICconfig['tokenserviceurl'].'"/>';
						$buf .= '</xs:schema>';
					$buf .= '</wsdl:types>';
				
					$buf .= '<wsdl:message name="RequestSecurityTokenMsg">';
						$buf .= '<wsdl:part name="request" type="q1:MessageBody" />';
					$buf .= '</wsdl:message>';
					$buf .= '<wsdl:message name="RequestSecurityTokenResponseMsg">';
						$buf .= '<wsdl:part name="response" type="q1:MessageBody" />';
					$buf .= '</wsdl:message>';
					
					$buf .= '<wsdl:portType name="SecurityTokenService">';
						$buf .= '<wsdl:operation name="Issue">';
							$buf .= '<wsdl:input wsaw:Action="http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Issue" message="tns:RequestSecurityTokenMsg">';
							$buf .= '</wsdl:input>';
							$buf .= '<wsdl:output wsaw:Action="http://schemas.xmlsoap.org/ws/2005/02/trust/RSTR/Issue" message="tns:RequestSecurityTokenResponseMsg">';
							$buf .= '</wsdl:output>';
						$buf .= '</wsdl:operation>';
					$buf .= '</wsdl:portType>';
					
					$buf .= '<wsp:Policy wsu:Id="STS_endpoint_policy">';
						$buf .= '<wsp:ExactlyOne>';
							$buf .= '<wsp:All>';
								$buf .= '<ic:RequireFederatedIdentityProvisioning />';
								$buf .= '<sp:TransportBinding>';
									$buf .= '<wsp:Policy>';
										$buf .= '<sp:TransportToken>';
											$buf .= '<wsp:Policy>';
												$buf .= '<sp:HttpsToken RequireClientCertificate="false" />';
											$buf .= '</wsp:Policy>';
										$buf .= '</sp:TransportToken>';
										$buf .= '<sp:AlgorithmSuite>';
											$buf .= '<wsp:Policy>';
												$buf .= '<sp:Basic256/>';
											$buf .= '</wsp:Policy>';
										$buf .= '</sp:AlgorithmSuite>';
										$buf .= '<sp:Layout>';
											$buf .= '<wsp:Policy>';
												$buf .= '<sp:Strict/>';
											$buf .= '</wsp:Policy>';
										$buf .= '</sp:Layout>';
										$buf .= '<sp:IncludeTimestamp/>';
									$buf .= '</wsp:Policy>';
								$buf .= '</sp:TransportBinding>';
								
								// Authentication token assertion
								switch($ICconfig['UserCredential']){
									case "UsernamePasswordCredential":
										$buf .= '<sp:SignedSupportingTokens xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
											$buf .= '<wsp:Policy>';
												$buf .= '<sp:UsernameToken sp:IncludeToken="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy/IncludeToken/AlwaysToRecipient">';
													$buf .= '<wsp:Policy>';
														$buf .= '<sp:WssUsernameToken10/>';
													$buf .= '</wsp:Policy>';
												$buf .= '</sp:UsernameToken>';
											$buf .= '</wsp:Policy>';
										$buf .= '</sp:SignedSupportingTokens>';
										break;
									case "KerberosV5Credential":
										$buf .= '<sp:ProtectionToken>';
											$buf .= '<wsp:Policy>';
												$buf .= '<sp:KerberosToken sp:IncludeToken="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy/IncludeToken/Once">';
													$buf .= '<wsp:Policy>';
														$buf .= '<sp: WssGssKerberosV5ApReqToken11/>';
													$buf .= '</wsp:Policy>';
												$buf .= '</sp:KerberosToken>';
											$buf .= '<wsp:Policy>';
										$buf .= '</sp:ProtectionToken>';
										break;
									case "X509V3Credential":
										$buf .= '<sp:EndorsingSupportingTokens xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
											$buf .= '<wsp:Policy>';
												$buf .= '<sp:X509Token sp:IncludeToken="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy/IncludeToken/AlwaysToRecipient">';
													$buf .= '<wsp:Policy>';
														$buf .= '<sp:WssX509V3Token10/>';
													$buf .= '</wsp:Policy>';
												$buf .= '</sp:X509Token>';
											$buf .= '</wsp:Policy>';
										$buf .= '</sp:EndorsingSupportingTokens>';
										break;
									case "SelfIssuedCredential":
										$buf .= '<sp:EndorsingSupportingTokens xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy" xmlns:wst="http://schemas.xmlsoap.org/ws/2005/02/trust">';
											$buf .= '<wsp:Policy>';
												$buf .= '<sp:IssuedToken sp:IncludeToken="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy/IncludeToken/AlwaysToRecipient">';
													$buf .= '<sp:Issuer>';
														$buf .= '<wsa:Address>';
															$buf .= 'http://schemas.xmlsoap.org/ws/2005/05/identity/issuer/self';
														$buf .= '</wsa:Address>';
													$buf .= '</sp:Issuer>';
													$buf .= '<sp:RequestSecurityTokenTemplate>';
														$buf .= '<wst:TokenType>';
															$buf .= 'urn:oasis:names:tc:SAML:1.0:assertion';
														$buf .= '</wst:TokenType>';
														$buf .= '<wst:KeyType>';
															$buf .= 'http://schemas.xmlsoap.org/ws/2005/02/trust/PublicKey';
														$buf .= '</wst:KeyType>';
														$buf .= '<wst:Claims xmlns:ic="http://schemas.xmlsoap.org/ws/2005/05/identity">';
															$buf .= '<ic:ClaimType Uri="http://schemas.xmlsoap.org/ws/2005/05/identity/claims/privatepersonalidentifier"/>';
														$buf .= '</wst:Claims>';
													$buf .= '</sp:RequestSecurityTokenTemplate>';
													$buf .= '<wsp:Policy>';
														$buf .= '<sp:RequireInternalReference/>';
													$buf .= '</wsp:Policy>';
												$buf .= '</sp:IssuedToken>';
											$buf .= '</wsp:Policy>';
										$buf .= '</sp:EndorsingSupportingTokens>';
										break;
									default:
										break;
								}
								
								$buf .= '<sp:Wss11>';
									$buf .= '<wsp:Policy>';
										$buf .= '<sp:MustSupportRefThumbprint/>';
										$buf .= '<sp:MustSupportRefEncryptedKey/>';
									$buf .= '</wsp:Policy>';
								$buf .= '</sp:Wss11>';
								$buf .= '<sp:Trust10>';
									$buf .= '<wsp:Policy>';
										$buf .= '<sp:RequireClientEntropy/>';
										$buf .= '<sp:RequireServerEntropy/>';
									$buf .= '</wsp:Policy>';
								$buf .= '</sp:Trust10>';
								$buf .= '<wsaw:UsingAddressing wsdl:required="true" />';
							$buf .= '</wsp:All>';
						$buf .= '</wsp:ExactlyOne>';
					$buf .= '</wsp:Policy>';
					
					$buf .= '<wsdl:binding name="Transport_binding" type="tns:SecurityTokenService">';
						$buf .= '<wsp:PolicyReference URI="#STS_endpoint_policy"/>';
							$buf .= '<soap12:binding transport="http://schemas.xmlsoap.org/soap/http"/>';
							$buf .= '<wsdl:operation name="Issue">';
								$buf .= '<soap12:operation soapAction="http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Issue" style="document"/>';
								$buf .= '<wsdl:input>';
									$buf .= '<soap12:body use="literal"/>';
								$buf .= '</wsdl:input>';
								$buf .= '<wsdl:output>';
									$buf .= '<soap12:body use="literal"/>';
								$buf .= '</wsdl:output>';
							$buf .= '</wsdl:operation>';
					$buf .= '</wsdl:binding>';
				
					$buf .= '<wsdl:service name="STS_0">';
						$buf .= '<wsdl:port name="STS_0_port" binding="tns:Transport_binding">';
							$buf .= '<soap12:address location="'.$ICconfig['tokenserviceurl'].'" />';
							$buf .= '<wsa:EndpointReference>';
								$buf .= '<wsa:Address>'.$ICconfig['tokenserviceurl'].'</wsa:Address>';
								$buf .= '<wsid:Identity>';
									$buf .= '<ds:KeyInfo>';
										$buf .= '<ds:X509Data>';
											$buf .= '<ds:X509Certificate>';
												$buf .= sspmod_InfoCard_Utils::takeCert($ICconfig['certificates'][0]);
											$buf .='</ds:X509Certificate>';
										$buf .= '</ds:X509Data>';
									$buf .= '</ds:KeyInfo>';
								$buf .= '</wsid:Identity>';
							$buf .= '</wsa:EndpointReference>';
						$buf .= '</wsdl:port>';
					$buf .= '</wsdl:service>';
					
				$buf .= '</wsdl:definitions>';
			$buf .= '</MetadataSection>';
		
		
			$buf .= '<MetadataSection Dialect="http://www.w3.org/2001/XMLSchema" Identifier="'.$ICconfig['tokenserviceurl'].'">';
				$buf .= '<xs:schema xmlns:tns="'.$ICconfig['tokenserviceurl'].'" xmlns:xs="http://www.w3.org/2001/XMLSchema" elementFormDefault="qualified" targetNamespace="'.$ICconfig['tokenserviceurl'].'">';
					$buf .= '<xs:complexType name="MessageBody">';
						$buf .= '<xs:sequence>';
							$buf .= '<xs:any maxOccurs="unbounded" minOccurs="0" namespace="##any"/>';
						$buf .= '</xs:sequence>';
					$buf .= '</xs:complexType>';
				$buf .= '</xs:schema>';
			$buf .= '</MetadataSection>';
		
		$buf .= '</Metadata>';	
	$buf .= '</S:Body>';
	
$buf .= '</S:Envelope>';


print($buf);

?>













