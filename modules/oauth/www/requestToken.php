<?php

require_once(dirname(dirname(__FILE__)) . '/libextinc/OAuth.php');


try {
	
	$store = new sspmod_oauth_OAuthStore();
	$server = new sspmod_oauth_OAuthServer($store);

	$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
	$plaintext_method = new OAuthSignatureMethod_PLAINTEXT();
	$rsa_method = new sspmod_oauth_OAuthSignatureMethodRSASHA1();

	$server->add_signature_method($hmac_method);
	$server->add_signature_method($plaintext_method);
	$server->add_signature_method($rsa_method);

	$req = OAuthRequest::from_request();
	$token = $server->fetch_request_token($req, null, $req->get_version());

	// OAuth1.0-revA adds oauth_callback_confirmed to token
	echo $token;
	if ($req->get_version() == '1.0a') {
	  echo "&oauth_callback_confirmed=true";
	}
	
} catch (Exception $e) {
	
	header('Content-type: text/plain; utf-8', TRUE, 500);
	header('OAuth-Error: ' . $e->getMessage());

	print_r($e);
	
}
