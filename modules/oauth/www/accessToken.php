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


	$requestToken = $req->get_parameter('oauth_token');
	$verifier = $req->get_parameter("oauth_verifier"); if ($verifier == null) $verifier = '';

	if (!$store->isAuthorized($requestToken, $verifier)) {
		throw new Exception('Your request was not authorized. Request token [' . $requestToken . '] not found.');
	}

	$accessToken = $server->fetch_access_token($req);
	$data = $store->moveAuthorizedData($requestToken, $verifier, $accessToken->key);

	echo $accessToken;

} catch (Exception $e) {
	
	header('Content-type: text/plain; utf-8', TRUE, 500);
	header('OAuth-Error: ' . $e->getMessage());

	print_r($e);
	
}
