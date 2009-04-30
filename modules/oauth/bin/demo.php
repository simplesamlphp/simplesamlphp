#!/usr/bin/env php
<?php

/* This is the base directory of the simpleSAMLphp installation. */
$baseDir = dirname(dirname(dirname(dirname(__FILE__))));

/* Add library autoloader. */
require_once($baseDir . '/lib/_autoload.php');


require_once(dirname(dirname(__FILE__)) . '/libextinc/OAuth.php');

$baseurl = (isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : 'https://foodle.feide.no/simplesaml');
$key = (isset($_SERVER['argv'][2]) ? $_SERVER['argv'][1] : 'key');
$secret = (isset($_SERVER['argv'][3]) ? $_SERVER['argv'][1] : 'secret');

echo 'Welcome to the OAuth CLI client' . "\n";
$consumer = new sspmod_oauth_Consumer($key, $secret);

// Get the request token
$requestToken = $consumer->getRequestToken($baseurl . '/module.php/oauth/requestToken.php');
echo "Got a request token from the OAuth service provider [" . $requestToken->key . "] with the secret [" . $requestToken->secret . "]\n";

// Authorize the request token
$consumer->getAuthorizeRequest($baseurl . '/module.php/oauth/authorize.php', $requestToken);

// Replace the request token with an access token
$accessToken = $consumer->getAccessToken( $baseurl . '/module.php/oauth/accessToken.php', $requestToken);
echo "Got an access token from the OAuth service provider [" . $accessToken->key . "] with the secret [" . $accessToken->secret . "]\n";

$userdata = $consumer->getUserInfo($baseurl . '/module.php/oauth/getUserInfo.php', $accessToken);


echo 'You are successfully authenticated to this Command Line CLI. ' . "\n";
echo 'Got data [' . join(', ', array_keys($userdata)) . ']' . "\n";
echo 'Your user ID is :  ' . $userdata['eduPersonPrincipalName'][0] . "\n";







