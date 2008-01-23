<?php

// Work-in-Progress:
//
// This code can be used:
//   a. to hook up an A-Select Agent or a "local" A-Select server
//      (=SP) and bridge to a SAML 2.0 IdP, 
// or
//   b. as a bridge from simpleSAMLphp local/bridge "auth" to a
//      "remote" A-Select server.
//
// Connect A-Select Agent:
//   configure the agent.xml as follows (example):
//     <aselect-server-id>default.aselect.org</aselect-server-id>
//     <url>https://localhost/simplesaml/aselect/handler.php</url>
//
// Connect "local" A-Select Server:
//   configure simpleSAMLphp as a "remote" aselect server as follows (example):
//			<organization id="simplSAMLphp"
//				server="localhost"
//				friendly_name="simpleSAMLphp (TEST)"
//				resourcegroup="remote_simplsamlphp_resources" />
//
//			<resourcegroup id="remote_simplsamlphp_resources"
//				interval="30">
//				<resource id="simpleSAMLphp1">
//					<url>https://localhost/simplesaml/aselect/handler.php</url>
//				</resource>
//			</resourcegroup>
//
// Bridge to "remote" A-Select Server:
//   configure simpleSAMLphp as a "local" aselect server as follows (example):
//			<organization id="simplSAMLphp" server="localhost">
//				<level>1</level>
//				<forced_authenticate>false</forced_authenticate>
//				<attribute_policy>policyA</attribute_policy>
//			</organization>
//
//   set the "auth" handler in the idp-hosted metadata as follows:
//		'auth'				=>	'aselect/handler.php?request=bridge',
//
// TODO:
// - extend config possibilities and move it to config/config.php
//   and metadata/aselect-sp-hosted.php
// - add robustness/error-handling/error-reporting
// - generic bridging
// - check the crypto related stuff (is encrypting rid enough?)
// - more checks on parameters (really needed?)

require_once('../../www/_include.php');
require_once('SimpleSAML/Logger.php');
require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');

$logger = new SimpleSAML_Logger();
$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

$as_config = array(
	'server_id' => 'default.aselect.org',
	'organization_id' => 'simplSAMLphp',
	'authsp_level' => '10',
	'authsp' => 'simplSAMLphp',
	'app_level' => '10',
	'tgt_exp_time' => '1194590521000',
	'metadata' => $metadata->getMetaData('localhost', 'saml20-sp-hosted'),
	'saml20' => array(
		'sp_url_sso' => '/' . $config->getValue('baseurlpath') . '/saml2/sp/initSSO.php',
		'sp_url_slo' => '/' . $config->getValue('baseurlpath') . '/saml2/sp/initSLO.php',
	),
	'logout_url' => '/' . $config->getValue('baseurlpath') . 'logout.html',
	'remote_server_id' => 'default.aselect.org',
	'remote_organization_id' => 'testorg',
	'remote_server_url' => 'https://localhost/aselectserver/server'
);

if ($_GET['local_rid']) session_id($_GET['local_rid']); else if ($_GET['rid']) session_id($_GET['rid']);

session_start();

// handle authenticate request from an agent or a local server
function as_request_authenticate() {
	global $as_config;
	$_SESSION['return_url'] = array_key_exists('app_url', $_GET) ? $_GET['app_url'] : $_GET['local_as_url'];
	$_SESSION['app_id'] = $_GET['app_id'];
	print 'result_code=0000' . 
          '&a-select-server=' . $as_config['server_id'] .
          '&rid=' . session_id() . 
          '&as_url=' . $_SERVER['PHP_SELF'] . '?request=login';
}

// handle browser login redirect from agent or local server
function as_request_login() {
	global $as_config;
	$return_url = $_SERVER['PHP_SELF'] . '?request=return';
	header('Location: ' .
		$as_config['saml20']['sp_url_sso'] .
		'?RelayState=' . $return_url);
}

// handle browser return redirect from bridged IDP (SAML 2.0 for now)
function as_request_return() {
	global $as_config, $config;
	$rid = session_id();
	$publickey = $config->getBaseDir() . '/cert/' . $as_config['metadata']['certificate'];
	if (!file_exists($publickey)) {
		throw new Exception('Could not find public key file [' . $publickey . '] which is needed to encrypt the credentials.');
	}
	$xmlseckey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'public'));
	$xmlseckey->loadKey($publickey,TRUE);
	//TODO; why does xmlseclibs not work!?
	//$credentials = $xmlseckey->encryptData($rid);
	if (openssl_public_encrypt($rid, $credentials, $xmlseckey->key) == FALSE) {
		$logger->log(LOG_INFO, '1', 'aselect', 'handler', 'request', 'access', 'Could not encrypt aselect_credentials: ' . $rid . ' : ' . openssl_error_string());
		throw new Exception("Could not encrypt credentials!");	
	}
	$redirect = $_SESSION['return_url'];
	if (!strrchr($redirect, '?')) {
		$redirect .= '?';
	} else {
		$redirect .= '&';
	}
	$redirect .=
		'rid=' . $rid .
		 '&a-select-server=' . $as_config['server_id'] .
		 '&aselect_credentials=' . urlencode(base64_encode($credentials));
	header('Location: ' . 	$redirect);
}

// handle verify credentials request from agent or local server
function as_request_verify_credentials() {
	global $as_config, $config, $logger;
	// NB: accomodate for weird a-select behaviour: agent and local a-select server pass in credentials in different ways...
	$credentials = array_key_exists('local_organization', $_GET) ? base64_decode(urldecode($_GET['aselect_credentials'])) : base64_decode($_GET['aselect_credentials']);
	$privatekey = $config->getBaseDir() . '/cert/' . $as_config['metadata']['privatekey'];
	if (!file_exists($privatekey)) {
		throw new Exception('Could not find private key file [' . $privatekey . '] which is needed to verify the credentials.');
	}
	$xmlseckey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'private'));
	$xmlseckey->loadKey($privatekey,TRUE);
	//TODO; why does xmlseclibs not work!?
	//$decrypted = $xmlseckey->decryptData($credentials);
	if (openssl_private_decrypt($credentials, $decrypted, $xmlseckey->key) == FALSE) {
		$logger->log(LOG_INFO, '1', 'aselect', 'handler', 'request', 'access', 'Could not decrypt aselect_credentials: ' . $credentials . ' : ' . openssl_error_string());
		throw new Exception("Could not decrypt credentials!");
	}
	if ($decrypted != session_id()) {
		$logger->log(LOG_INFO, '1', 'aselect', 'handler', 'request', 'access', 'Credentials incorrect or tampered with: ' . $decrypted . ' != ' . session_id());
		throw new Exception("Incorrect credentials!");
	}
	$session = SimpleSAML_Session::getInstance();
	$serialized = '';
	foreach ($session->getAttributes() as $name => $values) {
		foreach ($values as $value) {
			if ($serialized != '') $serialized .= '&';
			$serialized .= urlencode($name) . '=' . urlencode($value);
		}
	}
	print 'result_code=0000' .
		'&app_id=' . $_SESSION['app_id'] . 
		'&uid=' . $session->getNameID() .
		'&organization=' . $as_config['organization_id'] .
		'&authsp_level=' . $as_config['authsp_level'] .
		'&authsp=' . $as_config['authsp'] .
		'&app_level=' . $as_config['app_level'] .
		'&a-select-server=' . $as_config['server_id'] .
		'&tgt_exp_time=' . $as_config['tgt_exp_time'];	
	if ($serialized != '') {
		print '&attributes=' . base64_encode($serialized);
	}
}

// handle browser logout redirect from agent or local server
function as_request_logout() {
	global $as_config;
	header('Location: ' .
		$as_config['saml20']['sp_url_slo'] .
		'?RelayState=' . $as_config['logout_url']);
}

// helper function for sending a non-browser request to a remote server
function as_call($url) {
	global $logger;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_URL, $url);
	$result = curl_exec($ch);
	$error = curl_error($ch);
	curl_close($ch);
	if ($result == FALSE) {
		$logger->log(LOG_INFO, '1', 'aselect', 'handler', 'request', 'access', 'Request on remote server failed [' . $url . '] : ' . $error);
		throw new Exception('Request on remote server failed: ' . $error);
	}
	$parms = array();
	foreach (explode('&', $result) as $p) {
		$a = explode('=', $p);
		$parms[$a[0]] = urldecode($a[1]);
	}
	if ($parms['result_code'] != '0000') {
		$logger->log(LOG_INFO, '1', 'aselect', 'handler', 'request', 'access', 'Request on remote server returned error: ' . $result);
		throw new Exception('Request on remote server returned error: ' . $result);
	}
	return $parms;
}

// handle bridged authentication request from simpleSAMLphp to remote server
function as_request_bridge() {
	global $as_config, $logger;
	// perform authenticate
	$_SESSION['relaystate'] = $_GET['RelayState'];
	$url = $as_config['remote_server_url'] .
		'?request=authenticate' .
		'&required_level=' . $as_config['app_level'] .
		'&local_organization=' . $as_config['organization_id'] .
		'&a-select-server=' . $as_config['remote_server_id'] .
		'&local_as_url=' . urlencode($_SERVER['PHP_SELF'] .
						'?request=bridge_return' .
						'&local_rid=' . session_id());
	$parms = as_call($url);
	header('Location: ' .
		$parms['as_url'] .
			'&rid=' . $parms['rid'] .
			'&a-select-server=' . $as_config['remote_server_id']);
}

// handle browser return redirect from (bridged) remote server
function as_request_bridge_return() {
	global $as_config, $logger;
	if ((array_key_exists('aselect_credentials', $_GET) == FALSE)
		||
		(array_key_exists('rid', $_GET) == FALSE))
		{
		$logger->log(LOG_INFO, '1', 'aselect', 'handler', 'request', 'access', 'Error on return from login at remote server!');
		throw new Exception('Error on return from login at remote server!');		
	}
	$url = $as_config['remote_server_url'] .
		'?request=verify_credentials' .
		'&rid=' . $_GET['rid'] .
		'&local_organization=' . $as_config['organization_id'] .
		'&a-select-server=' . $as_config['remote_server_id'] .
		'&aselect_credentials=' . $_GET['aselect_credentials'];
	$parms = as_call($url);
	
	SimpleSAML_Session::init('aselect', $_GET['rid'], true);
	$session = SimpleSAML_Session::getInstance();
	
	if (array_key_exists('attributes', $parms)) {
		$parm = base64_decode($parms['attributes']);
		$attributes = array();
		foreach (explode('&', $parm) as $p) {
			$a = explode('=', $p);
			if (array_key_exists($a[0], $attributes)) {
				$attributes[$a[0]] = array();
			}
			$attributes[$a[0]][] = urldecode($a[1]);
		}
		$session->setAttributes($attributes);
	}
	#$session->setNameID('test');
	header('Location: ' . 	$_SESSION['relaystate']);
}

// demultiplex incoming request
try {
	$logger->log(LOG_INFO, '1', 'aselect', 'handler', 'request', 'access', $_SERVER['REQUEST_URI']);
	if ($_GET['request']) {
		$handler = 'as_request_' . $_GET['request'];
		$handler();
	} else {
		// no request:
		//   a. present status page with logout button posting to "request=logout""
		//      (mimic a-select behaviour),		
		print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?request=logout' . '"><input type="submit" value="Logout"></form>';		
		// or
		//   b. assume that an empty request will always mean "logout"
		//      (potential problem when users accidentally browse here?)
	}
} catch (Exception $e) {
	print 'result_code=0001&error=' . urlencode($e);
}

?>
