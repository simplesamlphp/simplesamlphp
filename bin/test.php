#!/usr/bin/env php
<?php

/*
 * This script can be used to test a login-logout sequence to a specific IdP.
 * It is configured from the config/test.php file. A template for that file
 * can be found in config/test-template.php.
 */

$tests = array();

/* The configuration file is relative to this script. */
$configFile = dirname(dirname(__FILE__)) . '/config/test.php';

/* Check if the configuration file exists. */
if(!file_exists($configFile)) {
	echo('Missing configuration file: ' . $configFile . "\n");
	echo('Maybe you need to copy config/test-template.php to config/test.php and update it?.' . "\n");
	exit(1);
}

/* Load the configuration file. */
require_once($configFile);

/**
 * This function creates a curl handle and initializes it.
 *
 * @return A curl handler.
 */
function curlCreate() {

	$ch = curl_init($url);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_COOKIEFILE, '');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

	return $ch;
}


/**
 * This function requests a url with a GET request.
 *
 * @param $curl        The curl handle which should be used.
 * @param $url         The url which should be requested.
 * @param $parameters  Associative array with parameters which should be appended to the url.
 * @return The content of the returned page.
 */
function urlGet($curl, $url, $parameters = array()) {

	$p = '';
	foreach($parameters as $k => $v) {
		if($p != '') {
			$p .= '&';
		}

		$p .= urlencode($k) . '=' . urlencode($v);
	}

	if(strpos($url, '?') === FALSE) {
		$url .= '?' . $p;
	} else {
		$url .= '&' . $p;
	}

	curl_setopt($curl, CURLOPT_HTTPGET, TRUE);
	curl_setopt($curl, CURLOPT_URL, $url);

	$curl_scraped_page = curl_exec($curl);
	if($curl_scraped_page === FALSE) {
		echo('Failed to get url: ' . $url . "\n");
		echo('Curl error: ' . curl_error($curl) . "\n");
		return FALSE;
	}

	return $curl_scraped_page;
}


/**
 * This function posts data to a specific url.
 *
 * @param $curl   The curl handle which should be used for the request.
 * @param $url    The url the POST request should be directed to.
 * @param $post   Associative array with the post parameters.
 * $return The returned page.
 */
function urlPost($curl, $url, $post) {

	$postparams = '';

	foreach($post as $k => $v) {
		if($postparams != '') {
			$postparams .= '&';
		}

		$postparams .= urlencode($k) . '=' . urlencode($v);
	}


	curl_setopt($curl, CURLOPT_POSTFIELDS, $postparams);
	curl_setopt($curl, CURLOPT_POST, TRUE);
	curl_setopt($curl, CURLOPT_URL, $url);

	$curl_scraped_page = curl_exec($curl);
	if($curl_scraped_page === FALSE) {
		echo('Failed to get url: ' . $url . "\n");
		echo('Curl error: ' . curl_error($curl) . "\n");
		return FALSE;
	}

	return $curl_scraped_page;
}


/**
 * This function parses a simpleSAMLphp HTTP-REDIRECT debug page.
 *
 * @param $page The content of the page.
 * @return FALSE if $page isn't a HTTP-REDIRECT debug page, destination url if it is.
 */
function parseSimpleSamlHttpRedirectDebug($page) {
	if(strpos($page, '<h2>Sending a SAML message using HTTP-REDIRECT</h2>') === FALSE) {
		return FALSE;
	}

	if(!preg_match('/<a id="sendlink" href="([^"]*)">send SAML message<\\/a>/', $page, $matches)) {
		echo('Invalid simpleSAMLphp debug page. Missing link.' . "\n");
		return FALSE;
	}

	$url = $matches[1];
	$url = html_entity_decode($url);

	return $url;
}


/**
 * This function parses a simpleSAMLphp HTTP-POST page.
 *
 * @param $page The content of the page.
 * @return FALSE if $page isn't a HTTP-POST page. If it is a HTTP-POST page, it will return an associative array with
 *         the post destination in 'url' and the post arguments as an associative array in 'post'.
 */
function parseSimpleSamlHttpPost($page) {
	if(strpos($page, '<title>SAML 2.0 POST</title>') === FALSE
		&& strpos($page, '<title>SAML Response Debug-mode</title>') === FALSE
		&& strpos($page, '<title>SAML (Shibboleth 1.3) Response Debug-mode</title>') === FALSE) {
		return FALSE;
	}

	if(!preg_match('/<form method="post" action="([^"]*)">/', $page, $matches)) {
		echo('Invalid simpleSAMLphp HTTP-POST page. Missing form target.' . "\n");
		return FALSE;
	}
	$url = html_entity_decode($matches[1]);

	$params = array();

	if(!preg_match('/<input type="hidden" name="SAMLResponse" value="([^"]*)" \\/>/', $page, $matches)) {
		echo('Invalid simpleSAMLphp HTTP-POST page. Missing SAMLResponse.' . "\n");
		return FALSE;
	}
	$params['SAMLResponse'] = html_entity_decode($matches[1]);

	if(preg_match('/<input type="hidden" name="RelayState" value="([^"]*)" \\/>/', $page, $matches)) {
		$params['RelayState'] = html_entity_decode($matches[1]);
	}

	if(preg_match('/<input type="hidden" name="TARGET" value="([^"]*)" \\/>/', $page, $matches)) {
		$params['TARGET'] = html_entity_decode($matches[1]);
	}


	return array('url' => $url, 'post' => $params);
}


/**
 * This function parses a simpleSAMLphp HTTP-POST debug page.
 *
 * @param $page The content of the page.
 * @return FALSE if $page isn't a HTTP-POST page. If it is a HTTP-POST page, it will return an associative array with
 *         the post destination in 'url' and the post arguments as an associative array in 'post'.
 */
function parseSimpleSamlHttpPostDebug($page) {
	if(strpos($page, '<title>SAML Response Debug-mode</title>') === FALSE) {
		return FALSE;
	}

	if(!preg_match('/<form method="post" action="([^"]*)">/', $page, $matches)) {
		echo('Invalid simpleSAMLphp HTTP-POST page. Missing form target.' . "\n");
		return FALSE;
	}
	$url = html_entity_decode($matches[1]);

	if(!preg_match('/<input type="hidden" name="SAMLResponse" value="([^"]*)" \\/>/', $page, $matches)) {
		echo('Invalid simpleSAMLphp HTTP-POST page. Missing SAMLResponse.' . "\n");
		return FALSE;
	}
	$samlResponse = html_entity_decode($matches[1]);

	if(!preg_match('/<input type="hidden" name="RelayState" value="([^"]*)" \\/>/', $page, $matches)) {
		echo('Invalid simpleSAMLphp HTTP-POST page. Missing RelayState.' . "\n");
		return FALSE;
	}
	$relayState = html_entity_decode($matches[1]);


	return array('url' => $url, 'post' => array('SAMLResponse' => $samlResponse, 'RelayState' => $relayState));
}


/**
 * This function parses a simpleSAMLphp login page.
 *
 * @param $curl The curl handle the page was fetched with.
 * @param $page The content of the login page.
 * @return FALSE if $page isn't a login page, associative array with the destination url in 'url' and the relaystate in 'relaystate'.
 */
function parseSimpleSamlLoginPage($curl, $page) {

	$url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);

	$pos = strpos($url, '?');
	if($pos === FALSE) {
		echo('Unexpected login page url: ' . $url);
		return FALSE;
	}
	$url = substr($url, 0, $pos + 1);


	if(!preg_match('/<input type="hidden" name="RelayState" value="([^"]*)" \\/>/', $page, $matches)) {
		echo('Could not find relaystate in simpleSAMLphp login page.' . "\n");
		return FALSE;
	}

	$relaystate = $matches[1];
	$relaystate = html_entity_decode($relaystate);

	return array('url' => $url, 'relaystate' => $relaystate);
}


/**
 * This function parses a FEIDE login page.
 *
 * @param $curl The curl handle the page was fetched with.
 * @param $page The content of the login page.
 * @return FALSE if $page isn't a login page, associative array with the destination url in 'url' and the goto attribute in 'goto'.
 */
function parseFeideLoginPage($curl, $page) {

	if(strpos($page, '<title> Moria-innlogging </title>') === FALSE) {
		echo('Not a FEIDE login page.' . "\n");
		return FALSE;
	}

	$url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);

	$pos = strpos($url, '/amserver/UI/Login');
	if($pos === FALSE) {
		echo('Unexpected login page url: ' . $url);
		return FALSE;
	}
	$url = substr($url, 0, $pos) . '/amserver/UI/Login';

	if(!preg_match('/<input type="hidden" name="goto" value="([^"]*)"\\/>/', $page, $matches)) {
		echo('Could not find goto in FEIDE login page.' . "\n");
		return FALSE;
	}

	$goto = $matches[1];
	$goto = html_entity_decode($goto);

	return array('url' => $url, 'goto' => $goto);
}


/**
 * This function parses the FEIDE HTTP-POST page.
 *
 * @param $page The content of the page.
 * @return FALSE if $page isn't a HTTP-POST page. If it is a HTTP-POST page, it will return an associative array with
 *         the post destination in 'url' and the post arguments as an associative array in 'post'.
 */
function parseFeideHttpPost($page) {

	if(strpos($page, '<TITLE>Access rights validated</TITLE>') === FALSE) {
		return FALSE;
	}

	if(!preg_match('/<FORM METHOD="POST" ACTION="([^"]*)">/', $page, $matches)) {
		echo('Invalid FEIDE HTTP-POST page. Missing form target.' . "\n");
		return FALSE;
	}
	$url = html_entity_decode($matches[1]);

	if(!preg_match('/<INPUT TYPE="HIDDEN" NAME="SAMLResponse" VALUE="([^"]*)">/m', $page, $matches)) {
		echo('Invalid FEIDE HTTP-POST page. Missing SAMLResponse.' . "\n");
		return FALSE;
	}
	$samlResponse = html_entity_decode($matches[1]);

	if(!preg_match('/<INPUT TYPE="HIDDEN" NAME="RelayState" VALUE="([^"]*)">/', $page, $matches)) {
		echo('Invalid FEIDE HTTP-POST page. Missing RelayState.' . "\n");
		return FALSE;
	}
	$relayState = html_entity_decode($matches[1]);


	return array('url' => $url, 'post' => array('SAMLResponse' => $samlResponse, 'RelayState' => $relayState));
}


/**
 * This function handles simpleSAMLphp debug pages, and follows redirects in them.
 *
 * @param $curl The curl handle we should use.
 * @param $page The page which may be a simpleSAMLphp debug page. $page may be FALSE, in which case this function
 *              will return FALSE.
 * @return $page if $page isn't a debug page, or the result from following the redirect if not.
 *         FALSE will be returned on failure.
 */
function skipDebugPage($curl, $page) {

	if($page === FALSE) {
		return FALSE;
	}

	$url = parseSimpleSamlHttpRedirectDebug($page);
	if($url !== FALSE) {
		$page = urlGet($curl, $url);
	}

	return $page;
}


/**
 * This function contacts the test page to initialize SSO.
 *
 * @param $test The test we are running.
 * @param $curl The curl handle we should use.
 * @return TRUE on success, FALSE on failure.
 */
function initSSO($test, $curl) {
	if(!array_key_exists('url', $test)) {
		echo('Missing required attribute url in test.' . "\n");
		return FALSE;
	}

	$params = array('op' => 'login');
	if(array_key_exists('idp', $test)) {
		$params['idp'] = $test['idp'];
	}

	/* Add the protocol which simpleSAMLphp should use to authenticate. */
	if(array_key_exists('protocol', $test)) {
		$params['protocol'] = $test['protocol'];
	}

	/* Add attribute tests. */
	if(array_key_exists('attributes', $test)) {
		$i = 0;
		foreach($test['attributes'] as $name => $values) {
			if(!is_array($values)) {
				$values = array($values);
			}

			foreach($values as $value) {
				$params['attr_test_' . $i] = $name . ':' . $value;
				$i++;
			}
		}
	}

	echo('Initializing SSO.' . "\n");
	$loginPage = urlGet($curl, $test['url'], $params);
	if($loginPage === FALSE) {
		echo('Failed to initialize SSO.' . "\n");
		return FALSE;
	}

	/* Skip HTTP-REDIRECT debug page if it appears. */
	$loginPage = skipDebugPage($curl, $loginPage);

	return $loginPage;
}


/**
 * This function handles login to a simpleSAMLphp login page.
 *
 * @param $test The current test.
 * @param $curl The curl handle in use.
 * @param $page The login page.
 * @return FALSE on failure, or the resulting page on success.
 */
function doSimpleSamlLogin($test, $curl, $page) {

	if(!array_key_exists('username', $test)) {
		echo('Missing username in test.' . "\n");
		return FALSE;
	}

	if(!array_key_exists('password', $test)) {
		echo('Missing password in test.' . "\n");
		return FALSE;
	}

	$info = parseSimpleSamlLoginPage($curl, $page);
	if($info === FALSE) {
		return FALSE;
	}

	$post = array();
	$post['username'] = $test['username'];
	$post['password'] = $test['password'];
	$post['RelayState'] = $info['relaystate'];

	$page = urlPost($curl, $info['url'], $post);


	/* Follow HTTP-POST redirect. */
	$pi = parseSimpleSamlHttpPost($page);
	if($pi === FALSE) {
		echo($page);
		echo('Didn\'t get a simpleSAMLphp post redirect page.' . "\n");
		return FALSE;
	}

	$page = urlPost($curl, $pi['url'], $pi['post']);

	return $page;
}


/**
 * This function handles login to the FEIDE login page.
 *
 * @param $test The current test.
 * @param $curl The curl handle in use.
 * @param $page The login page.
 * @return FALSE on failure, or the resulting page on success.
 */
function doFeideLogin($test, $curl, $page) {

	if(!array_key_exists('username', $test)) {
		echo('Missing username in test.' . "\n");
		return FALSE;
	}

	if(!array_key_exists('password', $test)) {
		echo('Missing password in test.' . "\n");
		return FALSE;
	}

	if(!array_key_exists('organization', $test)) {
		echo('Missing organization in test.' . "\n");
		return FALSE;
	}


	$info = parseFeideLoginPage($curl, $page);
	if($info === FALSE) {
		return FALSE;
	}

	$post = array();
	$post['username'] = $test['username'];
	$post['password'] = $test['password'];
	$post['organization'] = $test['organization'];
	$post['goto'] = $info['goto'];

	$page = urlPost($curl, $info['url'], $post);


	/* Follow HTTP-POST redirect. */
	$pi = parseFeideHttpPost($page);
	if($pi === FALSE) {
		echo('Unable to parse FEIDE HTTP-POST redirect page.' . "\n");
		return FALSE;
	}

	$page = urlPost($curl, $pi['url'], $pi['post']);

	return $page;
}

/**
 * This function logs in using the configuration of the given test.
 *
 * @param $test The current test.
 * @param $curl The curl handle in use.
 * @param $page The login page.
 * @return FALSE on failure, or the resulting page on success.
 */
function doLogin($test, $curl, $page) {
	if(!array_key_exists('logintype', $test)) {
		echo('Missing option \'logintype\' in test configuration.' . "\n");
		return FALSE;
	}

	switch($test['logintype']) {
	case 'simplesaml':
		return doSimpleSamlLogin($test, $curl, $page);
	case 'feide':
		return doFeideLogin($test, $curl, $page);
	default:
		echo('Unknown login type: ' . $test['logintype'] . "\n");
		echo($page);
		return FALSE;
	}
}


/**
 * This function contacts the test page to initialize SSO.
 *
 * @param $test The test we are running.
 * @param $curl The curl handle we should use.
 * @return TRUE on success, FALSE on failure.
 */
function doLogout($test, $curl) {
	if(!array_key_exists('url', $test)) {
		echo('Missing required attribute url in test.' . "\n");
		return FALSE;
	}

	$params = array('op' => 'logout');

	$page = urlGet($curl, $test['url'], $params);
	if($page === FALSE) {
		echo('Failed to log out.' . "\n");
		return FALSE;
	}

	/* Skip HTTP-REDIRECT debug pagess. */
	while(TRUE) {
		$newPage = skipDebugPage($curl, $page);
		if($newPage === $page) {
			break;
		}
		$page = $newPage;
	}

	return $page;
}


/**
 * This function runs the specified test.
 *
 * @param $test  Associative array with the test parameters.
 * @return TRUE on success, FALSE on failure.
 */
function doTest($test) {
	$curl = curlCreate();

	$res = TRUE;

	/* Initialize SSO. */
	do {
		$loginPage = initSSO($test, $curl);
		if($loginPage === FALSE) {
			$res = FALSE;
			break;
		}

		echo('Logging in.' . "\n");

		$result = doLogin($test, $curl, $loginPage);
		if($result !== "OK") {
			if(is_string($result)) {
				echo('Failed to log in. Result from SP: ' . $result . "\n");
			} else {
				echo('Failed to log in.' . "\n");
			}
			$res = FALSE;
			break;
		}

		echo('Logged in, attributes OK' . "\n");

		if(array_key_exists('protocol', $test) && $test['protocol'] === 'shib13') {
			echo('Shib13: Logout not implemented.' . "\n");
			break;
		}

		echo('Logging out.' . "\n");

		$result = doLogout($test, $curl);
		if($result !== "OK") {
			if(is_string($result)) {
				echo('Failed to log out. Result from SP: ' . $result . "\n");
			} else {
				echo('Failed to log out.' . "\n");
			}
			$res = FALSE;
			break;
		}

		echo('Logged out.' . "\n");

	} while(0);

	curl_close($curl);

	return $res;
}


$ret = 0;
/* Run the tests. */
foreach($tests as $i => $test) {
	echo('############################################################' . "\n");
	echo('Running test #' . ($i + 1) . '.' . "\n");

	$res = doTest($test);

	if($res === FALSE) {
		$ret = 1;
		echo('Test #' . ($i + 1) . ' failed.' . "\n");
	} else {
		echo('Test #' . ($i + 1) . ' succeeded.' . "\n");
	}
}
echo('############################################################' . "\n");

exit($ret);

?>