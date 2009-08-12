<?php

require_once(dirname(dirname(__FILE__)) . '/libextinc/OAuth.php');

if(!array_key_exists('oauth_token', $_REQUEST)) {
	throw new Exception('Required URL parameter [oauth_token] is missing.');
}
$requestToken = $_REQUEST['oauth_token'];

$store = new sspmod_oauth_OAuthStore();
$server = new sspmod_oauth_OAuthServer($store);

$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
$plaintext_method = new OAuthSignatureMethod_PLAINTEXT();

$server->add_signature_method($hmac_method);
$server->add_signature_method($plaintext_method);




$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

$as = 'saml2';
if (!$session->isValid($as)) {
	SimpleSAML_Auth_Default::initLogin($as, SimpleSAML_Utilities::selfURL());
}

$attributes = $session->getAttributes();

#print_r($attributes);

$store->authorize($requestToken, $attributes);

if (isset($_REQUEST['oauth_callback'])) {
	
	SimpleSAML_Utilities::redirect($_REQUEST['oauth_callback']);
	
} else {


	$t = new SimpleSAML_XHTML_Template($config, 'oauth:authorized.php');

	$t->data['header'] = '{status:header_saml20_sp}';
	$t->data['remaining'] = $session->remainingTime();
	$t->data['sessionsize'] = $session->getSize();
	$t->data['attributes'] = $attributes;
	$t->data['logouturl'] = SimpleSAML_Utilities::selfURLNoQuery() . '?logout';
	$t->data['icon'] = 'bino.png';
	$t->show();
}



// 
// $req = OAuthRequest::from_request();
// $token = $server->fetch_request_token($req);
// echo $token;
