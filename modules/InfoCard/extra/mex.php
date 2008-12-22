<?php
/*
 *   Copyright (C) 2007 Carillon Information Security Inc.
 *
 * WS-MetadataExchange responder for the Carillon STS.  Everything is
 * pretty much hard-coded -- the only things that get customized are the
 * tokenservice URL and the certificate.
 *
 */
 
/*
* COAUTHOR: Samuel Muñoz Hidalgo
* EMAIL: samuel.mh@gmail.com
* LAST REVISION: 22-DEC-08
* DESCRIPTION: InfoCard module metadata exchange
*/


$method = $_SERVER["REQUEST_METHOD"];
if ($method == "POST")
    $use_soap = true;
else
    $use_soap = false;

if ($use_soap)
    Header('Content-Type: application/soap+xml;charset=utf-8');
else
    Header('Content-Type: application/xml;charset=utf-8');

$config = SimpleSAML_Configuration::getInstance();
$autoconfig = $config->copyFromBase('logininfocard', 'config-login-infocard.php');
$ICconfig['tokenserviceurl'] = $autoconfig->getValue('tokenserviceurl');
$ICconfig['certificates'] = $autoconfig->getValue('certificates');


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

if ($use_soap)
{
    $buf .= '<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope" xmlns:a="http://www.w3.org/2005/08/addressing">';
    $buf .= '<s:Header>';
    $buf .= '<a:Action s:mustUnderstand="1">http://schemas.xmlsoap.org/ws/2004/09/transfer/GetResponse</a:Action>';
    if ($request_id)
        $buf .= "<a:RelatesTo>$request_id</a:RelatesTo>";
    $buf .= '</s:Header>';
    $buf .= '<s:Body>';
}
$buf .= '<Metadata xmlns="http://schemas.xmlsoap.org/ws/2004/09/mex" xmlns:wsx="http://schemas.xmlsoap.org/ws/2004/09/mex">';
$buf .= '<wsx:MetadataSection xmlns="" Dialect="http://schemas.xmlsoap.org/wsdl/" Identifier="http://schemas.xmlsoap.org/ws/2005/02/trust">';
$buf .= '<wsdl:definitions xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/" xmlns:tns="http://schemas.xmlsoap.org/ws/2005/02/trust" xmlns:wsa="http://schemas.xmlsoap.org/ws/2004/08/addressing" xmlns:wsp="http://schemas.xmlsoap.org/ws/2004/09/policy" xmlns:wsap="http://schemas.xmlsoap.org/ws/2004/08/addressing/policy" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:msc="http://schemas.microsoft.com/ws/2005/12/wsdl/contract" xmlns:wsaw="http://www.w3.org/2006/05/addressing/wsdl" xmlns:soap12="http://schemas.xmlsoap.org/wsdl/soap12/" xmlns:wsa10="http://www.w3.org/2005/08/addressing" targetNamespace="http://schemas.xmlsoap.org/ws/2005/02/trust">';
$buf .= '<wsdl:types>';
$buf .= '<xsd:schema targetNamespace="http://schemas.xmlsoap.org/ws/2005/02/trust/Imports">';
$buf .= '<xsd:import namespace="http://schemas.microsoft.com/Message"/>';
$buf .= '</xsd:schema>';
$buf .= '</wsdl:types>';
$buf .= '<wsdl:message name="IWSTrustContract_Cancel_InputMessage">';
$buf .= '<wsdl:part xmlns:q1="http://schemas.microsoft.com/Message" name="request" type="q1:MessageBody"/>';
$buf .= '</wsdl:message>';
$buf .= '<wsdl:message name="IWSTrustContract_Cancel_OutputMessage">';
$buf .= '<wsdl:part xmlns:q2="http://schemas.microsoft.com/Message" name="CancelResult" type="q2:MessageBody"/>';
$buf .= '</wsdl:message>';
$buf .= '<wsdl:message name="IWSTrustContract_Issue_InputMessage">';
$buf .= '<wsdl:part xmlns:q3="http://schemas.microsoft.com/Message" name="request" type="q3:MessageBody"/>';
$buf .= '</wsdl:message>';
$buf .= '<wsdl:message name="IWSTrustContract_Issue_OutputMessage">';
$buf .= '<wsdl:part xmlns:q4="http://schemas.microsoft.com/Message" name="IssueResult" type="q4:MessageBody"/>';
$buf .= '</wsdl:message>';
$buf .= '<wsdl:message name="IWSTrustContract_Renew_InputMessage">';
$buf .= '<wsdl:part xmlns:q5="http://schemas.microsoft.com/Message" name="request" type="q5:MessageBody"/>';
$buf .= '</wsdl:message>';
$buf .= '<wsdl:message name="IWSTrustContract_Renew_OutputMessage">';
$buf .= '<wsdl:part xmlns:q6="http://schemas.microsoft.com/Message" name="RenewResult" type="q6:MessageBody"/>';
$buf .= '</wsdl:message>';
$buf .= '<wsdl:message name="IWSTrustContract_Validate_InputMessage">';
$buf .= '<wsdl:part xmlns:q7="http://schemas.microsoft.com/Message" name="request" type="q7:MessageBody"/>';
$buf .= '</wsdl:message>';
$buf .= '<wsdl:message name="IWSTrustContract_Validate_OutputMessage">';
$buf .= '<wsdl:part xmlns:q8="http://schemas.microsoft.com/Message" name="ValidateResult" type="q8:MessageBody"/>';
$buf .= '</wsdl:message>';
$buf .= '<wsdl:portType name="IWSTrustContract">';
$buf .= '<wsdl:operation name="Cancel">';
$buf .= '<wsdl:input wsaw:Action="http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Cancel" message="tns:IWSTrustContract_Cancel_InputMessage"/>';
$buf .= '<wsdl:output wsaw:Action="http://schemas.xmlsoap.org/ws/2005/02/trust/RSTR/Cancel" message="tns:IWSTrustContract_Cancel_OutputMessage"/>';
$buf .= '</wsdl:operation>';
$buf .= '<wsdl:operation name="Issue">';
$buf .= '<wsdl:input wsaw:Action="http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Issue" message="tns:IWSTrustContract_Issue_InputMessage"/>';
$buf .= '<wsdl:output wsaw:Action="http://schemas.xmlsoap.org/ws/2005/02/trust/RSTR/Issue" message="tns:IWSTrustContract_Issue_OutputMessage"/>';
$buf .= '</wsdl:operation>';
$buf .= '<wsdl:operation name="Renew">';
$buf .= '<wsdl:input wsaw:Action="http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Renew" message="tns:IWSTrustContract_Renew_InputMessage"/>';
$buf .= '<wsdl:output wsaw:Action="http://schemas.xmlsoap.org/ws/2005/02/trust/RSTR/Renew" message="tns:IWSTrustContract_Renew_OutputMessage"/>';
$buf .= '</wsdl:operation>';
$buf .= '<wsdl:operation name="Validate">';
$buf .= '<wsdl:input wsaw:Action="http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Validate" message="tns:IWSTrustContract_Validate_InputMessage"/>';
$buf .= '<wsdl:output wsaw:Action="http://schemas.xmlsoap.org/ws/2005/02/trust/RSTR/Validate" message="tns:IWSTrustContract_Validate_OutputMessage"/>';
$buf .= '</wsdl:operation>';
$buf .= '</wsdl:portType>';
$buf .= '</wsdl:definitions>';
$buf .= '</wsx:MetadataSection>';
$buf .= '<wsx:MetadataSection xmlns="" Dialect="http://schemas.xmlsoap.org/wsdl/" Identifier="http://tempuri.org/">';
$buf .= '<wsdl:definitions xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/" xmlns:tns="http://tempuri.org/" xmlns:wsa="http://schemas.xmlsoap.org/ws/2004/08/addressing" xmlns:wsp="http://schemas.xmlsoap.org/ws/2004/09/policy" xmlns:i0="http://schemas.xmlsoap.org/ws/2005/02/trust" xmlns:wsap="http://schemas.xmlsoap.org/ws/2004/08/addressing/policy" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:msc="http://schemas.microsoft.com/ws/2005/12/wsdl/contract" xmlns:wsaw="http://www.w3.org/2006/05/addressing/wsdl" xmlns:soap12="http://schemas.xmlsoap.org/wsdl/soap12/" xmlns:wsa10="http://www.w3.org/2005/08/addressing" name="STS" targetNamespace="http://tempuri.org/">';
$buf .= '<wsp:Policy wsu:Id="CustomBinding_IWSTrustContract_policy">';
$buf .= '<wsp:ExactlyOne>';
$buf .= '<wsp:All>';
$buf .= '<sp:TransportBinding xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<wsp:Policy>';
$buf .= '<sp:TransportToken>';
$buf .= '<wsp:Policy>';
$buf .= '<sp:X509Token sp:IncludeToken="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy/IncludeToken/Never">';
$buf .= '<wsp:Policy>';
$buf .= '<sp:RequireThumbprintReference/>';
$buf .= '<sp:WssX509V3Token10/>';
$buf .= '</wsp:Policy>';
$buf .= '</sp:X509Token>';
$buf .= '</wsp:Policy>';
$buf .= '</sp:TransportToken>';
$buf .= '<sp:AlgorithmSuite>';
$buf .= '<wsp:Policy>';
$buf .= '<sp:Basic128/>';
$buf .= '</wsp:Policy>';
$buf .= '</sp:AlgorithmSuite>';
$buf .= '<sp:Layout>';
$buf .= '<wsp:Policy>';
$buf .= '<sp:Strict/>';
$buf .= '</wsp:Policy>';
$buf .= '</sp:Layout>';
if ($_GET['auth'] == 'x509')
    $buf .= '<sp:IncludeTimestamp/>';
$buf .= '</wsp:Policy>';
$buf .= '</sp:TransportBinding>';

// is this metadata for an infocard that wants an x509-authenticated 
// token, or a username/password token?
if ($_GET['auth'] == 'x509')
{
    $buf .= '<sp:EndorsingSupportingTokens xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
    $buf .= '<wsp:Policy>';
    $buf .= '<sp:X509Token sp:IncludeToken="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy/IncludeToken/AlwaysToRecipient">';
    $buf .= '<wsp:Policy>';
    $buf .= '<sp:RequireThumbprintReference/>';
    $buf .= '<sp:WssX509V3Token10/>';
    $buf .= '</wsp:Policy>';
    $buf .= '</sp:X509Token>';
    $buf .= '</wsp:Policy>';
    $buf .= '</sp:EndorsingSupportingTokens>';
}
else
{
    $buf .= '<sp:SignedSupportingTokens xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
    $buf .= '<wsp:Policy>';
    $buf .= '<sp:UsernameToken sp:IncludeToken="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy/IncludeToken/AlwaysToRecipient">';
    $buf .= '<wsp:Policy>';
    $buf .= '<sp:WssUsernameToken10/>';
    $buf .= '</wsp:Policy>';
    $buf .= '</sp:UsernameToken>';
    $buf .= '</wsp:Policy>';
    $buf .= '</sp:SignedSupportingTokens>';
}

$buf .= '<sp:Wss11 xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<wsp:Policy>';
$buf .= '<sp:MustSupportRefKeyIdentifier/>';
$buf .= '<sp:MustSupportRefIssuerSerial/>';
$buf .= '<sp:MustSupportRefThumbprint/>';
$buf .= '<sp:MustSupportRefEncryptedKey/>';
$buf .= '</wsp:Policy>';
$buf .= '</sp:Wss11>';
$buf .= '<sp:Trust10 xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<wsp:Policy>';
$buf .= '<sp:MustSupportIssuedTokens/>';
$buf .= '<sp:RequireServerEntropy/>';
$buf .= '</wsp:Policy>';
$buf .= '</sp:Trust10>';
$buf .= '</wsp:All>';
$buf .= '</wsp:ExactlyOne>';
$buf .= '</wsp:Policy>';
$buf .= '<wsp:Policy wsu:Id="CustomBinding_IWSTrustContract_Cancel_Input_policy">';
$buf .= '<wsp:ExactlyOne>';
$buf .= '<wsp:All>';
$buf .= '<sp:SignedParts xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<sp:Body/>';
$buf .= '<sp:Header Name="To" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="From" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="FaultTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="ReplyTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="MessageID" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="RelatesTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="Action" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '</sp:SignedParts>';
$buf .= '<sp:EncryptedParts xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<sp:Body/>';
$buf .= '</sp:EncryptedParts>';
$buf .= '</wsp:All>';
$buf .= '</wsp:ExactlyOne>';
$buf .= '</wsp:Policy>';
$buf .= '<wsp:Policy wsu:Id="CustomBinding_IWSTrustContract_Cancel_output_policy">';
$buf .= '<wsp:ExactlyOne>';
$buf .= '<wsp:All>';
$buf .= '<sp:SignedParts xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<sp:Body/>';
$buf .= '<sp:Header Name="To" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="From" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="FaultTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="ReplyTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="MessageID" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="RelatesTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="Action" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '</sp:SignedParts>';
$buf .= '<sp:EncryptedParts xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<sp:Body/>';
$buf .= '</sp:EncryptedParts>';
$buf .= '</wsp:All>';
$buf .= '</wsp:ExactlyOne>';
$buf .= '</wsp:Policy>';
$buf .= '<wsp:Policy wsu:Id="CustomBinding_IWSTrustContract_Issue_policy">';
$buf .= '<wsp:ExactlyOne>';
$buf .= '<wsp:All>';
$buf .= '<sp:EndorsingSupportingTokens xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<wsp:Policy>';
$buf .= '<mssp:RsaToken xmlns:mssp="http://schemas.microsoft.com/ws/2005/07/securitypolicy" sp:IncludeToken="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy/IncludeToken/Never" wsp:Optional="true"/>';
$buf .= '</wsp:Policy>';
$buf .= '</sp:EndorsingSupportingTokens>';
$buf .= '</wsp:All>';
$buf .= '</wsp:ExactlyOne>';
$buf .= '</wsp:Policy>';
$buf .= '<wsp:Policy wsu:Id="CustomBinding_IWSTrustContract_Issue_Input_policy">';
$buf .= '<wsp:ExactlyOne>';
$buf .= '<wsp:All>';
$buf .= '<sp:SignedParts xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<sp:Body/>';
$buf .= '<sp:Header Name="To" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="From" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="FaultTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="ReplyTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="MessageID" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="RelatesTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="Action" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '</sp:SignedParts>';
$buf .= '<sp:EncryptedParts xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<sp:Body/>';
$buf .= '</sp:EncryptedParts>';
$buf .= '</wsp:All>';
$buf .= '</wsp:ExactlyOne>';
$buf .= '</wsp:Policy>';
$buf .= '<wsp:Policy wsu:Id="CustomBinding_IWSTrustContract_Issue_output_policy">';
$buf .= '<wsp:ExactlyOne>';
$buf .= '<wsp:All>';
$buf .= '<sp:SignedParts xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<sp:Body/>';
$buf .= '<sp:Header Name="To" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="From" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="FaultTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="ReplyTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="MessageID" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="RelatesTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="Action" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '</sp:SignedParts>';
$buf .= '<sp:EncryptedParts xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<sp:Body/>';
$buf .= '</sp:EncryptedParts>';
$buf .= '</wsp:All>';
$buf .= '</wsp:ExactlyOne>';
$buf .= '</wsp:Policy>';
$buf .= '<wsp:Policy wsu:Id="CustomBinding_IWSTrustContract_Renew_Input_policy">';
$buf .= '<wsp:ExactlyOne>';
$buf .= '<wsp:All>';
$buf .= '<sp:SignedParts xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<sp:Body/>';
$buf .= '<sp:Header Name="To" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="From" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="FaultTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="ReplyTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="MessageID" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="RelatesTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="Action" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '</sp:SignedParts>';
$buf .= '<sp:EncryptedParts xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<sp:Body/>';
$buf .= '</sp:EncryptedParts>';
$buf .= '</wsp:All>';
$buf .= '</wsp:ExactlyOne>';
$buf .= '</wsp:Policy>';
$buf .= '<wsp:Policy wsu:Id="CustomBinding_IWSTrustContract_Renew_output_policy">';
$buf .= '<wsp:ExactlyOne>';
$buf .= '<wsp:All>';
$buf .= '<sp:SignedParts xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<sp:Body/>';
$buf .= '<sp:Header Name="To" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="From" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="FaultTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="ReplyTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="MessageID" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="RelatesTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="Action" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '</sp:SignedParts>';
$buf .= '<sp:EncryptedParts xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<sp:Body/>';
$buf .= '</sp:EncryptedParts>';
$buf .= '</wsp:All>';
$buf .= '</wsp:ExactlyOne>';
$buf .= '</wsp:Policy>';
$buf .= '<wsp:Policy wsu:Id="CustomBinding_IWSTrustContract_Validate_Input_policy">';
$buf .= '<wsp:ExactlyOne>';
$buf .= '<wsp:All>';
$buf .= '<sp:SignedParts xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<sp:Body/>';
$buf .= '<sp:Header Name="To" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="From" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="FaultTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="ReplyTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="MessageID" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="RelatesTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="Action" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '</sp:SignedParts>';
$buf .= '<sp:EncryptedParts xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<sp:Body/>';
$buf .= '</sp:EncryptedParts>';
$buf .= '</wsp:All>';
$buf .= '</wsp:ExactlyOne>';
$buf .= '</wsp:Policy>';
$buf .= '<wsp:Policy wsu:Id="CustomBinding_IWSTrustContract_Validate_output_policy">';
$buf .= '<wsp:ExactlyOne>';
$buf .= '<wsp:All>';
$buf .= '<sp:SignedParts xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<sp:Body/>';
$buf .= '<sp:Header Name="To" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="From" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="FaultTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="ReplyTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="MessageID" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="RelatesTo" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '<sp:Header Name="Action" Namespace="http://www.w3.org/2005/08/addressing"/>';
$buf .= '</sp:SignedParts>';
$buf .= '<sp:EncryptedParts xmlns:sp="http://schemas.xmlsoap.org/ws/2005/07/securitypolicy">';
$buf .= '<sp:Body/>';
$buf .= '</sp:EncryptedParts>';
$buf .= '</wsp:All>';
$buf .= '</wsp:ExactlyOne>';
$buf .= '</wsp:Policy>';
$buf .= '<wsdl:import namespace="http://schemas.xmlsoap.org/ws/2005/02/trust" location=""/>';
$buf .= '<wsdl:types/>';
$buf .= '<wsdl:binding name="CustomBinding_IWSTrustContract" type="i0:IWSTrustContract">';
$buf .= '<wsp:PolicyReference URI="#CustomBinding_IWSTrustContract_policy"/>';
$buf .= '<soap12:binding transport="http://schemas.xmlsoap.org/soap/http"/>';
$buf .= '<wsdl:operation name="Cancel">';
$buf .= '<soap12:operation soapAction="http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Cancel" style="document"/>';
$buf .= '<wsdl:input>';
$buf .= '<wsp:PolicyReference URI="#CustomBinding_IWSTrustContract_Cancel_Input_policy"/>';
$buf .= '<soap12:body use="literal"/>';
$buf .= '</wsdl:input>';
$buf .= '<wsdl:output>';
$buf .= '<wsp:PolicyReference URI="#CustomBinding_IWSTrustContract_Cancel_output_policy"/>';
$buf .= '<soap12:body use="literal"/>';
$buf .= '</wsdl:output>';
$buf .= '</wsdl:operation>';
$buf .= '<wsdl:operation name="Issue">';
$buf .= '<wsp:PolicyReference URI="#CustomBinding_IWSTrustContract_Issue_policy"/>';
$buf .= '<soap12:operation soapAction="http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Issue" style="document"/>';
$buf .= '<wsdl:input>';
$buf .= '<wsp:PolicyReference URI="#CustomBinding_IWSTrustContract_Issue_Input_policy"/>';
$buf .= '<soap12:body use="literal"/>';
$buf .= '</wsdl:input>';
$buf .= '<wsdl:output>';
$buf .= '<wsp:PolicyReference URI="#CustomBinding_IWSTrustContract_Issue_output_policy"/>';
$buf .= '<soap12:body use="literal"/>';
$buf .= '</wsdl:output>';
$buf .= '</wsdl:operation>';
$buf .= '<wsdl:operation name="Renew">';
$buf .= '<soap12:operation soapAction="http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Renew" style="document"/>';
$buf .= '<wsdl:input>';
$buf .= '<wsp:PolicyReference URI="#CustomBinding_IWSTrustContract_Renew_Input_policy"/>';
$buf .= '<soap12:body use="literal"/>';
$buf .= '</wsdl:input>';
$buf .= '<wsdl:output>';
$buf .= '<wsp:PolicyReference URI="#CustomBinding_IWSTrustContract_Renew_output_policy"/>';
$buf .= '<soap12:body use="literal"/>';
$buf .= '</wsdl:output>';
$buf .= '</wsdl:operation>';
$buf .= '<wsdl:operation name="Validate">';
$buf .= '<soap12:operation soapAction="http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Validate" style="document"/>';
$buf .= '<wsdl:input>';
$buf .= '<wsp:PolicyReference URI="#CustomBinding_IWSTrustContract_Validate_Input_policy"/>';
$buf .= '<soap12:body use="literal"/>';
$buf .= '</wsdl:input>';
$buf .= '<wsdl:output>';
$buf .= '<wsp:PolicyReference URI="#CustomBinding_IWSTrustContract_Validate_output_policy"/>';
$buf .= '<soap12:body use="literal"/>';
$buf .= '</wsdl:output>';
$buf .= '</wsdl:operation>';
$buf .= '</wsdl:binding>';
$buf .= '<wsdl:service name="STS">';
$buf .= '<wsdl:port name="CustomBinding_IWSTrustContract" binding="tns:CustomBinding_IWSTrustContract">';
$buf .= "<soap12:address location=\"".$ICconfig['tokenserviceurl']."\"/>";
$buf .= '<wsa10:EndpointReference>';
$buf .= "<wsa10:Address>".$ICconfig['tokenserviceurl']."</wsa10:Address>";
$buf .= '<Identity xmlns="http://schemas.xmlsoap.org/ws/2006/02/addressingidentity">';
$buf .= '<KeyInfo xmlns="http://www.w3.org/2000/09/xmldsig#">';
$buf .= '<X509Data>';
$buf .= '<X509Certificate>'.sspmod_InfoCard_Utils::takeCert($ICconfig['certificates'][0]).'</X509Certificate>';
$buf .= '</X509Data>';
$buf .= '</KeyInfo>';
$buf .= '</Identity>';
$buf .= '</wsa10:EndpointReference>';
$buf .= '</wsdl:port>';
$buf .= '</wsdl:service>';
$buf .= '</wsdl:definitions>';
$buf .= '</wsx:MetadataSection>';
$buf .= '<wsx:MetadataSection xmlns="" Dialect="http://www.w3.org/2001/XMLSchema" Identifier="http://schemas.microsoft.com/Message">';
$buf .= '<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:tns="http://schemas.microsoft.com/Message" elementFormDefault="qualified" targetNamespace="http://schemas.microsoft.com/Message">';
$buf .= '<xs:complexType name="MessageBody">';
$buf .= '<xs:sequence>';
$buf .= '<xs:any minOccurs="0" maxOccurs="unbounded" namespace="##any"/>';
$buf .= '</xs:sequence>';
$buf .= '</xs:complexType>';
$buf .= '</xs:schema>';
$buf .= '</wsx:MetadataSection>';
$buf .= '</Metadata>';

if ($use_soap)
{
    $buf .= '</s:Body>';
    $buf .= '</s:Envelope>';
}



print($buf);

?>
