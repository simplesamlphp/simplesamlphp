<?php

/**
 * A-Select protocol support for simpleSAMLphp
 *
 * @author Hans Zandbelt, SURFnet BV. <hans.zandbelt@surfnet.nl>
 * @package simpleSAMLphp
 * @version $Id$
 * 
 * This code can be used:
 *   a. to hook up an A-Select Agent or a "local" A-Select server
 *      (=SP) and bridge to a SAML 2.0 IdP, 
 * or
 *   b. as a bridge from simpleSAMLphp local/bridge "auth" to a
 *      "remote" A-Select server.
 *
 * Connect A-Select Agent:
 *   configure the agent.xml as follows (example):
 *     <aselect-server-id>default.aselect.org</aselect-server-id>
 *     <url>https: *localhost/simplesaml/aselect/handler.php</url>
 *
 * Connect "local" A-Select Server:
 *   configure simpleSAMLphp as a "remote" aselect server as follows (example):
 *			<organization id="simpleSAMLphp"
 *				server="default.aselect.org"
 *				friendly_name="simpleSAMLphp (TEST)"
 *				resourcegroup="remote_simplesamlphp_resources" />
 *
 *			<resourcegroup id="remote_simplesamlphp_resources"
 *				interval="30">
 *				<resource id="simpleSAMLphp1">
 *					<url>https://localhost/simplesaml/aselect/handler.php</url>
 *				</resource>
 *			</resourcegroup>
 *
 * Bridge to "remote" A-Select Server:
 *   configure simpleSAMLphp as a "local" aselect server as follows (example):
 *			<organization id="simpleSAMLphp" server="default.aselect.org">
 *				<level>1</level>
 *				<forced_authenticate>false</forced_authenticate>
 *				<attribute_policy>policyA</attribute_policy>
 *			</organization>
 *
 *   set the "auth" handler in the idp-hosted metadata as follows:
 *		'auth'				=>	'aselect/handler.php?request=bridge',
 *
 * TODO:
 * - separate metadata configuration into metadata/aselect-*-*.php
 * - remote IDP discovery handling (similar to saml2: with optional default)
 * - add robustness/error-handling/error-reporting
 * - factor out common, app/local server and remote server code
 * 
 * - dynamic bridging after IDP discovery across all protocols (core feature)
 */

require_once('../../www/_include.php');
require_once('xmlseclibs.php');
require_once('SimpleSAML/Logger.php');
require_once('SimpleSAML/Configuration.php');

$config = SimpleSAML_Configuration::getInstance();

$as_metadata = array(
	'idp' => array(
		'hosted' => array(
			'organization' => 'simpleSAMLphp',
			'server_id' => 'default.aselect.org',
			'key' => $config->getBaseDir() . '/cert/server.pem',
			'cert' => $config->getBaseDir() . '/cert/server.crt',
			'authsp_level' => '10',
			'authsp' => 'simpleSAMLphp',
			'app_level' => '10',
			'tgt_exp_time' => '1194590521000',
#			'auth' => '/' . $config->getValue('baseurlpath') . '/auth/login.php',
#			'logout' => '/' . $config->getValue('baseurlpath') . 'logout.html',
			'auth' => '/' . $config->getValue('baseurlpath') . '/saml2/sp/initSSO.php',
			'logout' => '/' . $config->getValue('baseurlpath') . '/saml2/sp/initSLO.php',
			'loggedout_url' => '/' . $config->getValue('baseurlpath') . 'logout.html',
		),
		'remote' => array(
			// so far the IDP bridging is statically configured to the first one in
			// this list; IDP discovery should be implemented
			'testorg' => array(
				'server_id' => 'default.aselect.org',
				'server_url' => 'https://localhost/aselectserver/server',
				'sign_requests' => true,
				// fixed required authentication level per remote IDP, because this
				// requestor concept cannot be mapped from the other protocols
				'app_level' => '10',
			),
		),
	),
	'sp' => array(
		'hosted' => array(
			'organization' => 'simpleSAMLphp',
			'server_id' => 'default.aselect.org',
			'key' => $config->getBaseDir() . '/cert/agent.key',
		),
		'remote' => array(
			'testorg' => array(
				'require_signing' => true,
				'cert' => $config->getBaseDir() . '/cert/aselect.crt',
			),
		),
	),
	'app' => array(
		'app1' => array(
			'require_signing' => false,
		),
		'federatiedemo' => array(
			'require_signing' => true,
			'cert' => $config->getBaseDir() . '/cert/app.crt',
		),
	),
);

// some work to put a browser request into the corresponding session that was
// started by the original "authenticate" request of the agent or local server 
if ($_GET['local_rid']) session_id($_GET['local_rid']); else if ($_GET['rid']) session_id($_GET['rid']);

session_start();

// log an error and throw an exception
function as_error_exception($msg) {
	SimpleSAML_Logger::info(array('1', 'aselect', 'handler', 'request', 'access', $msg));
	throw new Exception($msg);
}

// read certificates and keys from files
function as_read_pem($cert_key_file) {
	if (!file_exists($cert_key_file)) {
		as_error_exception('Could not find certificate/key file: ' . $cert_key_file);
	}
	$fp = fopen($cert_key_file, "r");
	$contents = fread($fp, 8192);
	fclose($fp);	
	return $contents;
}

// verify a signature on an incoming request
function as_verify_signature($parms, $publickey) {
	global $as_metadata;
	
	$signature = base64_decode($_GET['signature']);
	$data = '';
	foreach ($parms as $p) {
		if (array_key_exists($p, $_GET)) $data .= $_GET[$p];
	}
	if (openssl_verify ($data, $signature, as_read_pem($publickey)) != 1) {
		as_error_exception('Signature verification failed: ' . openssl_error_string());
	}
}

// handle authenticate request from an agent or a local server
function as_request_authenticate() {
	global $as_metadata;	

	$app_id = $_GET['app_id'];
	$local_organization = $_GET['local_organization'];
	
	if ($app_id) {
		$md = $as_metadata['app'][$app_id];
		if ($md['require_signing']) {
			as_verify_signature(array(
					'a-select-server','app_id', 'app_url', 'country',
					'forced_logon', 'language', 'remote_organization', 'uid'
				),
				$md['cert']
			);
		}
		$_SESSION['app_id'] = $app_id;	
		$_SESSION['return_url'] = $_GET['app_url'];
	} else if ($local_organization) {
		$md = $as_metadata['sp']['remote'][$local_organization];
		if ($md['require_signing']) {
			as_verify_signature(array(
					'a-select-server', 'country', 'forced_logon', 'language',
					'local_as_url', 'local_organization', 'required_level', 'uid'
				),
				$md['cert']
			);
		}		
		$_SESSION['local_organization'] = $local_organization;	
		$_SESSION['return_url'] = $_GET['local_as_url'];
	} else {
		as_error_exception('Invalid "authenticate" request: no "app_id" or "local_organization" parameter found.');
	}
	
	print 'result_code=0000' . 
          '&a-select-server=' . $as_metadata['idp']['hosted']['server_id'] .
          '&rid=' . session_id() . 
          '&as_url=' . $_SERVER['PHP_SELF'] . '?request=login';
}

// handle browser login redirect from agent or local server
function as_request_login() {
	global $as_metadata;
	
	$return_url = $_SERVER['PHP_SELF'] . '?request=login_return';
	header('Location: ' .
		$as_metadata['idp']['hosted']['auth'] .
		'?RelayState=' . $return_url);
}

// handle browser return redirect from a bridged IDP
function as_request_login_return() {
	global $as_metadata;

	$rid = session_id();	
	$md = $as_metadata['idp']['hosted'];
	$credentials = '';
	if (openssl_public_encrypt($rid, $credentials, as_read_pem($md['cert'])) == FALSE) {
		as_error_exception('Could not encrypt aselect_credentials: ' . $rid . ' : ' . openssl_error_string());
	}
	$redirect = $_SESSION['return_url'];
	if (!strrchr($redirect, '?')) {
		$redirect .= '?';
	} else {
		$redirect .= '&';
	}
	$redirect .=
		'rid=' . $rid .
		 '&a-select-server=' . $md['server_id'] .
		 '&aselect_credentials=' . urlencode(base64_encode($credentials));
	header('Location: ' . 	$redirect);
}

// handle verify credentials request from agent or local server
function as_request_verify_credentials() {
	global $as_metadata;
	
	$app_id = $_SESSION['app_id'];
	$local_organization = $_GET['local_organization'];
	
	if ($app_id) {
		$md = $as_metadata['app'][$app_id];
		if ($md['require_signing']) {
			as_verify_signature(array(
					'a-select-server', 'aselect_credentials', 'rid'
				),
				$md['cert']
			);
		}
		// NB: different handling of credentials between agent and server requests!
		$credentials = base64_decode($_GET['aselect_credentials']);
	} else if ($local_organization) {
		$md = $as_metadata['sp']['remote'][$local_organization];
		if ($md['require_signing']) {
			as_verify_signature(array(
					'a-select-server', 'aselect_credentials', 'local_organization', 'rid'
				),
				$md['cert']
			);
		}
		// NB: different handling of credentials between agent and server requests!
		$credentials = base64_decode(urldecode($_GET['aselect_credentials']));
	} else {
		as_error_exception('Could not process verify_credentials request: no "app_id" parameter found in the session and no "local_organization" parameter found in the request.');
	}
	
	$md = $as_metadata['idp']['hosted'];
	$decrypted = '';	
	if (openssl_private_decrypt($credentials, $decrypted, as_read_pem($md['key'])) == FALSE) {
		as_error_exception('Could not decrypt aselect_credentials: ' . $_GET['aselect_credentials'] . ' : ' . openssl_error_string());
	}
	if ($decrypted != session_id()) {
		as_error_exception('Credentials incorrect or tampered with: ' . $decrypted . ' != ' . session_id());
	}

	$session = SimpleSAML_Session::getInstance();
	$serialized = '';
	foreach ($session->getAttributes() as $name => $values) {
		foreach ($values as $value) {
			if ($serialized != '') $serialized .= '&';
			$serialized .= urlencode($name) . '=' . urlencode($value);
		}
	}
	$nameid = $session->getNameID();

	print 'result_code=0000' .
		'&uid=' . $nameid['value'] .
		'&organization=' . $md['organization'] .
		'&authsp_level=' . $md['authsp_level'] .
		'&authsp=' . $md['authsp'] .
		'&app_level=' . $md['app_level'] .
		'&a-select-server=' . $md['server_id'] .
		'&tgt_exp_time=' . $md['tgt_exp_time'];	
	if ($app_id) {
		print '&app_id=' . $app_id;
	}
	if ($serialized != '') {
		print '&attributes=' . base64_encode($serialized);
	}
}

// handle browser logout redirect from agent or local server
function as_request_logout() {
	global $as_metadata;

	header('Location: ' .
		$as_metadata['idp']['hosted']['logout'] .
		'?RelayState=' . urlencode($as_metadata['idp']['hosted']['loggedout_url']));
}

// helper function for sending a non-browser request to a remote server
function as_call($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_URL, $url);
	$result = curl_exec($ch);
	$error = curl_error($ch);
	curl_close($ch);
	if ($result == FALSE) {
		as_error_exception('Request on remote server failed: ' . $error);
	}
	$parms = array();
	foreach (explode('&', $result) as $parm) {
		$tuple = explode('=', $parm);
		$parms[urldecode($tuple[0])] = urldecode($tuple[1]);
	}
	if ($parms['result_code'] != '0000') {
		as_error_exception('Request on remote server returned error: ' . $result);
	}
	return $parms;
}

// calculate signature on request parameters
function as_compute_signature($parms, $privatekey) {
	$data = '';
	foreach ($parms as $p) {
		$data .= $p;	
	}
	$signature = '';
	if (openssl_sign($data, $signature, as_read_pem($privatekey)) != TRUE) {
		as_error_exception('Signing the request failed: ' . openssl_error_string());
	}
	return urlencode(base64_encode($signature));
}

// handle bridged authentication request from simpleSAMLphp to remote server
function as_request_bridge() {
	global $as_metadata;
	
	$_SESSION['relaystate'] = $_GET['RelayState'];
	
	$local_md = $as_metadata['sp']['hosted'];
	$local_as_url = $_SERVER['PHP_SELF'] .
						'?request=bridge_return' .
						'&local_rid=' . session_id();

	// TODO: perform remote IDP discovery
	$idps = array_keys($as_metadata['idp']['remote']);
	$remote_organization = $idps[0];
	$_SESSION['remote_organization'] = $remote_organization;
	$remote_md = $as_metadata['idp']['remote'][$remote_organization];
	
	$url = $remote_md['server_url'] .
		'?request=authenticate' .
		'&required_level=' . $remote_md['app_level'] .
		'&local_organization=' . $local_md['organization'] .
		'&a-select-server=' . $remote_md['server_id'] .
		'&local_as_url=' . urlencode($local_as_url);

	if ($remote_md['sign_requests']) {
		$url .= '&signature=' . as_compute_signature(array(
						$remote_md['server_id'],
						$local_as_url,
						$local_md['organization'],
						$remote_md['app_level']
					),
					$local_md['key']
				);
	}
	$parms = as_call($url);

	header('Location: ' .
		$parms['as_url'] .
			'&rid=' . $parms['rid'] .
			'&a-select-server=' . $remote_md['server_id']);
}

// handle browser return redirect from (bridged) remote server
function as_request_bridge_return() {
	global $as_metadata;
	
	$credentials = $_GET['aselect_credentials'];
	$rid = $_GET['rid'];
	$relaystate = $_SESSION['relaystate'];

	if ( (!credentials) || (!rid) ) {
		as_error_exception('Error on return from login at remote server!');		
	}
	
	$local_md = $as_metadata['sp']['hosted'];
	$remote_organization = $_SESSION['remote_organization'];
	$remote_md = $as_metadata['idp']['remote'][$remote_organization];
	
	$url = $remote_md['server_url'] .
		'?request=verify_credentials' .
		'&rid=' . $rid .
		'&local_organization=' . $local_md['organization'] .
		'&a-select-server=' . $remote_md['server_id'] .
		'&aselect_credentials=' . $credentials;
		
	if ($remote_md['sign_requests']) {
		$url .= '&signature=' . as_compute_signature(array(
						$remote_md['server_id'],
						$credentials,
						$local_md['organization'],
						$rid
					),
					$local_md['key']
				);
	}
	$parms = as_call($url);
	
	$session = SimpleSAML_Session::getInstance(true);
	$session->setAuthenticated(true, 'aselect');
	
	if (array_key_exists('attributes', $parms)) {
		$decoded = base64_decode($parms['attributes']);
		$attributes = array();
		foreach (explode('&', $decoded) as $parm) {
			$tuple = explode('=', $parm);
			$name = urldecode($tuple[0]);
			if (array_key_exists($name, $attributes)) {
				$attributes[$name] = array();
			}
			$attributes[$name][] = urldecode($tuple[1]);
		}
		$session->setAttributes($attributes);
	}
	#$session->setNameID('test');

	header('Location: ' . 	$relaystate);
}

// demultiplex incoming request
try {
	SimpleSAML_Logger::info(array('1', 'aselect', 'handler', 'request', 'access', $_SERVER['REQUEST_URI']));
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
