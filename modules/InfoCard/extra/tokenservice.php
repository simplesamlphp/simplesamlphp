<?php
/*
 *   Copyright (C) 2007 Carillon Information Security Inc.
 *
 * Token responder for the Carillon STS.  Accepts a SOAP token request from
 * a relying party (or an infocard client, more likely) and produces a
 * token with the proper attributes, as stored in the database of issued
 * infocards.
 *
 */

/*
* COAUTHOR: Samuel Muñoz Hidalgo
* EMAIL: samuel.mh@gmail.com
* LAST REVISION: 22-DEC-08
* DESCRIPTION: InfoCard module token generator
*/



// Windows CardSpace doesn't support using the infocard's certificate as
// the SSL cert for transport binding... so we make it sign a timestamp in
// the token request, and validate the signature on that.
function validate_embedded_cert()
{
    global $doc, $row;
    global $db_usertable;
    global $uidnum, $uname, $fullname;
    global $HTTP_RAW_POST_DATA;

    // FIXME: Add error checking to this!

    // get the signed part (the timestamp) in a horribly cheating way for
    // now
    // first grab the namespace for u
    $begin = 'xmlns:u="';
    $end = 'xsd"';
    $xmlnsu = $HTTP_RAW_POST_DATA;
    $xmlnsu = substr($xmlnsu, strpos($xmlnsu, $begin));
    $xmlnsu = substr($xmlnsu, 0, strpos($xmlnsu, $end)+strlen($end));
    $begin = '<u:Timestamp ';
    $end = '</u:Timestamp>';
    $tmp = $HTTP_RAW_POST_DATA;
    $tmp = substr($tmp, strpos($tmp, $begin));
    $tmp = substr($tmp, 0, strpos($tmp, $end)+strlen($end));
    $tmp1 = substr($tmp, 0, strpos($tmp, ' '));
    $tmp2 = substr($tmp, strpos($tmp, ' ')+1);
    $timestamp = $tmp1." $xmlnsu ".$tmp2;

    // canonicalize the timestamp and digest it
    $canonical_timestamp = sspmod_InfoCard_Utils::canonicalize($timestamp);
    $myhash = sha1($canonical_timestamp,TRUE);
    $mydigest = base64_encode($myhash);

    // grab the digest from the request
    $elements = $doc->getElementsByTagname('DigestValue');
    $request_digest = $elements->item(0)->nodeValue;

    // if the digests don't match, we fail
    if ($mydigest != $request_digest)
        return false;

    // get the SignedInfo in a horribly cheating way for now
    $begin = '<SignedInfo';
    $end = '</SignedInfo>';
    $sinfo = $HTTP_RAW_POST_DATA;
    $sinfo = substr($sinfo, strpos($sinfo, $begin));
    $sinfo = substr($sinfo, 0, strpos($sinfo, $end)+strlen($end));

    // grab the signing certificate and PEM-encode it to satisfy openssl
    $elements = $doc->getElementsByTagname('BinarySecurityToken');
    $cert = $elements->item(0)->nodeValue;
    $certpem = "-----BEGIN CERTIFICATE-----\n";
    $offset = 0;
    while ($segment=substr($cert, $offset, 64))
    {
        $certpem .= $segment."\n";
        $offset += 64;
    }
    $certpem .= "-----END CERTIFICATE-----\n";

    $pubkey = openssl_pkey_get_public($certpem);

    // canonicalize the signed info
    $canonical_sinfo = sspmod_InfoCard_Utils::canonicalize($sinfo);

    // grab the signature from the request
    $elements = $doc->getElementsByTagname('SignatureValue');
    $request_sig = $elements->item(0)->nodeValue;

    $request_sig = base64_decode($request_sig);

    // try to verify the signature... if we can't, we fail.
    if (openssl_verify($canonical_sinfo, $request_sig, $pubkey) == false)
        return false;

    // so, the signature is OK.  Was it the right cert?  Check its
    // thumbprint against the cert we recorded in the infocard...
    $thumb = sspmod_InfoCard_Utils::thumbcert($cert);
    if ($row['x509thumb'] != $thumb)
        return false;

    // at this point we've succeeded, but we need to populate some fields
    // based on the usertable to create a card...
    $arr = openssl_x509_parse($certpem);
    $who = $arr['subject']['CN'];
    $query = "SELECT * FROM $db_usertable WHERE full_name='$who'";
    $userrow = pg_fetch_assoc(do_query($query));
    if ($userrow['status'] == "1")
    {
        $uidnum = $userrow['id'];
        $uname = $userrow['userid'];
        $fullname = $userrow['full_name'];
        return true;
    }
    return false;
}



/*
* claimValues ( 'claim'('value','displayTag'), 'claim'('value','displayTag'), ... )
*/
function create_token($claimValues,$config){
    // build a SAML assertion
    $now = gmdate('Y-m-d').'T'.gmdate('H:i:s').'Z';
    $later = gmdate('Y-m-d', time()+3600).'T'.gmdate('H:i:s', time()+3600).'Z';
    $assertionid = uniqid('uuid-');

    $saml = "<saml:Assertion MajorVersion=\"1\" MinorVersion=\"0\" AssertionID=\"$assertionid\" Issuer=\"".$config['issuer']."\" IssueInstant=\"$now\" xmlns:saml=\"urn:oasis:names:tc:SAML:1.0:assertion\">";
    $saml .= "<saml:Conditions NotBefore=\"$now\" NotOnOrAfter=\"$later\" />";

    $saml .= "<saml:AttributeStatement>";
    $saml .= "<saml:Subject>";
    $saml .= "<saml:SubjectConfirmation>";
    $saml .= "<saml:ConfirmationMethod>urn:oasis:names:tc:SAML:1.0:cm:holder-of-key</saml:ConfirmationMethod>";
    
    // proof key
    $saml .= "<dsig:KeyInfo xmlns:dsig=\"http://www.w3.org/2000/09/xmldsig#\">";
    $saml .= "<dsig:X509Data>";
    $saml .= "<dsig:X509Certificate>".sspmod_InfoCard_Utils::takeCert($config['sts_crt'])."</dsig:X509Certificate>";
    $saml .= "</dsig:X509Data>";
    $saml .= "</dsig:KeyInfo>";

    $saml .= "</saml:SubjectConfirmation>";
    $saml .= "</saml:Subject>";

		
		foreach ($claimValues as $claim=>$data) {  
        $saml .= "<saml:Attribute AttributeName=\"$claim\" AttributeNamespace=\"".$config['InfoCard']['schema']."/claims\">";
        $saml .= "<saml:AttributeValue>".$data['value']."</saml:AttributeValue>";
        $saml .= "</saml:Attribute>";
    }

    $saml .= "</saml:AttributeStatement>";


    // calculate the digest for the signature...
    $canonicalbuf = sspmod_InfoCard_Utils::canonicalize($saml."</saml:Assertion>");
    $myhash = sha1($canonicalbuf,TRUE);
    $samldigest = base64_encode($myhash);


    // construct a SignedInfo block
    $signedinfo = "<dsig:SignedInfo xmlns:dsig=\"http://www.w3.org/2000/09/xmldsig#\">";
    $signedinfo .= "<dsig:CanonicalizationMethod Algorithm=\"http://www.w3.org/2001/10/xml-exc-c14n#\" />";
    $signedinfo .= "<dsig:SignatureMethod Algorithm=\"http://www.w3.org/2000/09/xmldsig#rsa-sha1\" />";
    $signedinfo .= "<dsig:Reference URI=\"#$assertionid\">";
    $signedinfo .= "<dsig:Transforms>";
    $signedinfo .= "<dsig:Transform Algorithm=\"http://www.w3.org/2000/09/xmldsig#enveloped-signature\" />";
    $signedinfo .= "<dsig:Transform Algorithm=\"http://www.w3.org/2001/10/xml-exc-c14n#\" />";
    $signedinfo .= "</dsig:Transforms>";
    $signedinfo .= "<dsig:DigestMethod Algorithm=\"http://www.w3.org/2000/09/xmldsig#sha1\" />";
    $signedinfo .= "<dsig:DigestValue>$samldigest</dsig:DigestValue>";
    $signedinfo .= "</dsig:Reference>";
    $signedinfo .= "</dsig:SignedInfo>";

    // compute the signature of hte canonicalized digest
    $canonicalbuf = sspmod_InfoCard_Utils::canonicalize($signedinfo);
		$privkey = openssl_pkey_get_private(file_get_contents($config['sts_key']));
    $signature = '';
    openssl_sign($canonicalbuf, &$signature, $privkey);
    openssl_free_key($privkey);
    $samlsignature = base64_encode($signature);

	
    // now put it all together
    $saml .= "<dsig:Signature xmlns:dsig=\"http://www.w3.org/2000/09/xmldsig#\">";
    $saml .= $signedinfo;
    $saml .= "<dsig:SignatureValue>$samlsignature</dsig:SignatureValue>";

    $saml .= "<dsig:KeyInfo>";
   	$saml .= "<dsig:X509Data>";
  	$saml .= "<dsig:X509Certificate>".sspmod_InfoCard_Utils::takeCert($config['sts_crt'])."</dsig:X509Certificate>";
   	$saml .= "</dsig:X509Data>";
    $saml .= "</dsig:KeyInfo>";
    $saml .= "</dsig:Signature>";

    $saml .= "</saml:Assertion>";


    // cram the SAML assertion in a SOAP envelope
    $buf = '<?xml version="1.0"?>';
    $buf .= "<soap:Envelope xmlns:ic=\"http://schemas.xmlsoap.org/ws/2005/05/identity\" xmlns:soap=\"http://www.w3.org/2003/05/soap-envelope\" xmlns:wsa=\"http://www.w3.org/2005/08/addressing\" xmlns:wsse=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd\" xmlns:wst=\"http://schemas.xmlsoap.org/ws/2005/02/trust\" xmlns:wsu=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd\">";
    if ($include_timestamp) {
        $buf .= "<soap:Header>";
        $buf .= "<wsse:Security>";
        $buf .= "<wsu:Timestamp>";
        $buf .= "<wsu:Created>$now</wsu:Created>";
        $buf .= "<wsu:Expires>$later</wsu:Expires>";
        $buf .= "</wsu:Timestamp>";
        $buf .= "</wsse:Security>";
        $buf .= "</soap:Header>";
    } else
        $buf .= "<soap:Header />";

    $buf .= "<soap:Body>";
    $buf .= "<wst:RequestSecurityTokenResponse Context=\"ProcessRequestSecurityToken\">";
    $buf .= "<wst:TokenType>urn:oasis:names:tc:SAML:1.0:assertion</wst:TokenType>";
    $buf .= "<wst:RequestType>http://schemas.xmlsoap.org/ws/2005/02/trust/Issue</wst:RequestType>";
    $buf .= "<wst:RequestedSecurityToken>";

    $buf .= $saml;

    $buf .= "</wst:RequestedSecurityToken>";

    // references
    $buf .= "<wst:RequestedAttachedReference>";
    $buf .= "<wsse:SecurityTokenReference>";
    $buf .= "<wsse:KeyIdentifier ValueType=\"http://docs.oasis-open.org/wss/oasis-wss-saml-token-profile-1.0#SAMLAssertionID\">$assertionid</wsse:KeyIdentifier>";
    $buf .= "</wsse:SecurityTokenReference>";
    $buf .= "</wst:RequestedAttachedReference>";
    $buf .= "<wst:RequestedUnattachedReference>";
    $buf .= "<wsse:SecurityTokenReference>";
    $buf .= "<wsse:KeyIdentifier ValueType=\"http://docs.oasis-open.org/wss/oasis-wss-saml-token-profile-1.0#SAMLAssertionID\">$assertionid</wsse:KeyIdentifier>";
    $buf .= "</wsse:SecurityTokenReference>";
    $buf .= "</wst:RequestedUnattachedReference>";

    // display token
    $buf .= "<ic:RequestedDisplayToken>";
    $buf .= "<ic:DisplayToken xml:lang=\"en\">";
    
    foreach ($claimValues as $claim=>$data) { 
        $buf .= "<ic:DisplayClaim Uri=\"".$config['InfoCard']['schema']."/claims/".$claim."\">";
        $buf .= "<ic:DisplayTag>".$data['displayTag']."</ic:DisplayTag>";
        $buf .= "<ic:DisplayValue>".$data['value']."</ic:DisplayValue>";
        $buf .= "</ic:DisplayClaim>";
    }

    $buf .= "</ic:DisplayToken>";
    $buf .= "</ic:RequestedDisplayToken>";

    // the end
    $buf .= "</wst:RequestSecurityTokenResponse>";
    $buf .= "</soap:Body>";
    $buf .= "</soap:Envelope>";

    return $buf;
}




// grab the important parts of the token request.  these are the username,
// password, and cardid.

Header('Content-Type: application/soap+xml;charset=utf-8');


$token = new DOMDocument();
$token->loadXML($HTTP_RAW_POST_DATA);
$doc = $token->documentElement;
$username = $doc->getElementsByTagname('Username')->item(0)->nodeValue;
$password = $doc->getElementsByTagname('Password')->item(0)->nodeValue;
$cardId  =  $doc->getElementsByTagname('CardId')->item(0)->nodeValue;


if (sspmod_InfoCard_UserFunctions::validateUser($username,$password)){
	$config = SimpleSAML_Configuration::getInstance();
	$autoconfig = $config->copyFromBase('logininfocard', 'config-login-infocard.php');
	$ICconfig['InfoCard'] = $autoconfig->getValue('InfoCard');
	$ICconfig['issuer'] = $autoconfig->getValue('issuer');
	$ICconfig['sts_crt'] = $autoconfig->getValue('sts_crt');
	$ICconfig['sts_key'] = $autoconfig->getValue('sts_key');
	
	$requiredClaims = sspmod_InfoCard_Utils::extractClaims($ICconfig['InfoCard']['schema'], $doc->getElementsByTagname('ClaimType'));
	$claimValues = sspmod_InfoCard_UserFunctions::fillClaims($username, $ICconfig['InfoCard']['requiredClaims'], $ICconfig['InfoCard']['optionalClaims'],$requiredClaims);
	$buf = create_token($claimValues,$ICconfig);
	Header('Content-length: '.strlen($buf)+1);
	print($buf);
}else{
	$bad = true;
	print("");
}

?>