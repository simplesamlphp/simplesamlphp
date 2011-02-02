<?php

require_once(dirname(dirname(__FILE__)) . '/libextinc/OAuth.php');

try {
	


	$oauthconfig = SimpleSAML_Configuration::getOptionalConfig('module_oauth.php');

	if(!array_key_exists('oauth_token', $_REQUEST)) {
		throw new Exception('Required URL parameter [oauth_token] is missing.');
	}
	$requestToken = $_REQUEST['oauth_token'];

	$store = new sspmod_oauth_OAuthStore();
	$server = new sspmod_oauth_OAuthServer($store);

	$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
	$plaintext_method = new OAuthSignatureMethod_PLAINTEXT();
	$rsa_method = new sspmod_oauth_OAuthSignatureMethodRSASHA1();

	$server->add_signature_method($hmac_method);
	$server->add_signature_method($plaintext_method);
	$server->add_signature_method($rsa_method);


	$config = SimpleSAML_Configuration::getInstance();
	$session = SimpleSAML_Session::getInstance();

	$as = $oauthconfig->getString('auth');
	if (!$session->isValid($as)) {
		SimpleSAML_Auth_Default::initLogin($as, SimpleSAML_Utilities::selfURL());
	}


	if (!empty($_REQUEST['consent'])) {
		$consumer = $store->lookup_consumer_by_requestToken($requestToken);
	
		$t = new SimpleSAML_XHTML_Template($config, 'oauth:consent.php');
		$t->data['header'] = '{status:header_saml20_sp}';
		$t->data['consumer'] = $consumer;	// array containint {name, description, key, secret, owner} keys
		$t->data['urlAgree'] = SimpleSAML_Utilities::addURLparameter( SimpleSAML_Utilities::selfURL(), array("consent" => "yes") );
		$t->data['logouturl'] = SimpleSAML_Utilities::selfURLNoQuery() . '?logout';
	
		$t->show();
	
		exit();	// and be done.
	}

	$attributes = $session->getAttributes();

	// Assume user consent at this point and proceed with authorizing the token
	list($url, $verifier) = $store->authorize($requestToken, $attributes);


	if ($url) {
		// If authorize() returns a URL, take user there (oauth1.0a)
		SimpleSAML_Utilities::redirect($url);
	} 
	else if (isset($_REQUEST['oauth_callback'])) {
		// If callback was provided in the request (oauth1.0)
		SimpleSAML_Utilities::redirect($_REQUEST['oauth_callback']);
	
	} else {
		// No callback provided, display standard template

		$t = new SimpleSAML_XHTML_Template($config, 'oauth:authorized.php');

		$t->data['header'] = '{status:header_saml20_sp}';
		$t->data['remaining'] = $session->remainingTime();
		$t->data['sessionsize'] = $session->getSize();
		$t->data['attributes'] = $attributes;
		$t->data['logouturl'] = SimpleSAML_Utilities::selfURLNoQuery() . '?logout';
		$t->data['oauth_verifier'] = $verifier;
		$t->show();
	}

} catch (Exception $e) {
	
	header('Content-type: text/plain; utf-8', TRUE, 500);
	header('OAuth-Error: ' . $e->getMessage());

	print_r($e);
	
}


// 
// $req = OAuthRequest::from_request();
// $token = $server->fetch_request_token($req);
// echo $token;
