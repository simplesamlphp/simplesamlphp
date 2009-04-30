<?php

require_once(dirname(dirname(__FILE__)) . '/libextinc/OAuth.php');

$store = new sspmod_oauth_OAuthStore();
$server = new sspmod_oauth_OAuthServer($store);


$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
$plaintext_method = new OAuthSignatureMethod_PLAINTEXT();

$server->add_signature_method($hmac_method);
$server->add_signature_method($plaintext_method);

$req = OAuthRequest::from_request();
$token = $server->fetch_request_token($req);

echo $token;
