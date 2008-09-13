<?php

require_once('../../_include.php');

session_start();







/*
 * CONFIGURATION
 */



/**
 * Initialize an OpenID store
 *
 * @return object $store an instance of OpenID store (see the
 * documentation for how to create one)
 */
function getOpenIDStore()
{
    
	$config = SimpleSAML_Configuration::getInstance();
    return new Auth_OpenID_FileStore($config->getValue('openid.filestore'));
}

/**
 * Trusted sites is an array of trust roots.
 *
 * Sites in this list will not have to be approved by the user in
 * order to be used. It is OK to leave this value as-is.
 *
 * In a more robust server, this should be a per-user setting.
 */
$trusted_sites = array(
);






/*
 * ACTIONS
 */



/**
 * Handle a standard OpenID server request
 */
function action_default()
{
    $server =& getServer();
    $method = $_SERVER['REQUEST_METHOD'];
    $request = null;
    if ($method == 'GET') {
        $request = $_GET;
    } else {
        $request = $_POST;
    }

    $request = Auth_OpenID::fixArgs($request);
    $request = $server->decodeRequest($request);

    if (!$request) {

		$config = SimpleSAML_Configuration::getInstance();
		$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
		
		$t = new SimpleSAML_XHTML_Template($config, 'openid-about.php');
		$t->data['openidserver'] = $metadata->getGenerated('server', 'openid-provider');
		
		
		$session = SimpleSAML_Session::getInstance();
		
		$useridfield = $config->getValue('openid.userid_attributename');
		$delegationprefix = $config->getValue('openid.delegation_prefix');
		
		$username = 'your_username';
		if (isset($session) && $session->isValid() ) {
			$attributes = $session->getAttributes();
			$username = $attributes[$useridfield][0];
			$t->data['userid'] = $username;
		}
		$idpmeta = $metadata->getMetaDataCurrent('openid-provider');
		
		$relaystate = SimpleSAML_Utilities::selfURLNoQuery() . '?RelayState=' . urlencode($_GET['RelayState']) .
			'&RequestID=' . urlencode($requestid);
		$authurl = SimpleSAML_Utilities::addURLparameter('/' . $config->getBaseURL() . $idpmeta['auth'], 
			array('RelayState' => $relaystate));
		
		$t->data['initssourl'] 			= $authurl;
		$t->data['openiddelegation'] 	= $delegationprefix . $username;
		
		
		
		$t->show();    
		exit(0);
    }

    setRequestInfo($request);

    if (in_array($request->mode,
                 array('checkid_immediate', 'checkid_setup'))) {

        if (isTrusted($request->identity, $request->trust_root)) {
            $response =& $request->answer(true);
            $sreg = getSreg($request->identity);
            if (is_array($sreg)) {
                foreach ($sreg as $k => $v) {
                    $response->addField('sreg', $k, $v);
                }
            }
        } else if ($request->immediate) {
            $response =& $request->answer(false, getServerURL());
        } else {
            if (!getLoggedInUser()) {
            	// TODO Login
                //return login_render();
                check_authenticated_user();
            }
            
			$config = SimpleSAML_Configuration::getInstance();
			$t = new SimpleSAML_XHTML_Template($config, 'openid-trust.php');
			
			$t->data['openidurl'] = getLoggedInUser();
			$t->data['siteurl'] = htmlspecialchars($request->trust_root);;
			$t->data['trusturl'] = buildURL('trust', true);
			
			$t->show();    
			exit(0);
            
            //return trust_render($request);
        }
    } else {
        $response =& $server->handleRequest($request);
    }

    $webresponse =& $server->encodeResponse($response);

    foreach ($webresponse->headers as $k => $v) {
        header("$k: $v");
    }

    header(header_connection_close);
    print $webresponse->body;
    exit(0);
}

/**
 * Log out the currently logged in user
 */
function action_logout()
{
    setLoggedInUser(null);
    setRequestInfo(null);
    return authCancel(null);
}

/**
 * Check the input values for a login request
 */
function _login_checkInput($input)
{
    $openid_url = false;
    $errors = array();

    if (!isset($input['openid_url'])) {
        $errors[] = 'Enter an OpenID URL to continue';
    }
    if (!isset($input['password'])) {
        $errors[] = 'Enter a password to continue';
    }
    if (count($errors) == 0) {
        $openid_url = $input['openid_url'];
        $openid_url = Auth_OpenID::normalizeUrl($openid_url);
        $password = $input['password'];
        if (!checkLogin($openid_url, $password)) {
            $errors[] = 'The entered password does not match the ' .
                'entered identity URL.';
        }
    }
    return array($errors, $openid_url);
}




function check_authenticated_user() {

	//session_start();
	
	$config = SimpleSAML_Configuration::getInstance();
	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
	$session = SimpleSAML_Session::getInstance();
	
	$idpentityid = $metadata->getMetaDataCurrentEntityID('openid-provider');
	$idpmeta = $metadata->getMetaDataCurrent('openid-provider');


	/* Check if valid local session exists.. */
	if (!isset($session) || !$session->isValid() ) {
	
		
		
		$relaystate = SimpleSAML_Utilities::selfURLNoQuery() . '/login';
		$authurl = SimpleSAML_Utilities::addURLparameter('/' . $config->getBaseURL() . $idpmeta['auth'], 
			array('RelayState' => $relaystate));
		
		SimpleSAML_Utilities::redirect($authurl);
	}
	
	$attributes = $session->getAttributes();
	$info = getRequestInfo();
	
	
	$useridfield = $config->getValue('openid.userid_attributename');
	$delegationprefix = $config->getValue('openid.delegation_prefix');
	
	$username = $attributes[$useridfield][0];

		
	$openid_url = $delegationprefix . $username;

	error_log('set logged in user to be [' .$delegationprefix. '][' . $username . ']' );
	setLoggedInUser($openid_url);

}


/**
 * Log in a user and potentially continue the requested identity approval
 */
function action_login()
{

	error_log('action login');
	
	//session_start();
	
	check_authenticated_user();
	
	$info = getRequestInfo();
	
	return doAuth($info);

}





/**
 * Ask the user whether he wants to trust this site
 */
function action_trust()
{
    $info = getRequestInfo();
    $trusted = isset($_POST['trust']);
    if ($info && isset($_POST['remember'])) {
        $sites = getSessionSites();
        $sites[$info->trust_root] = $trusted;
        setSessionSites($sites);
    }
    return doAuth($info, $trusted, true);
}

function action_sites()
{
    $sites = getSessionSites();
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['forget'])) {
            $sites = null;
            setSessionSites($sites);
        } elseif (isset($_POST['remove'])) {
            foreach ($_POST as $k => $v) {
                if (preg_match('/^site[0-9]+$/', $k) && isset($sites[$v])) {
                    unset($sites[$v]);
                }
            }
            setSessionSites($sites);
        }
    }
    
	$config = SimpleSAML_Configuration::getInstance();
	$t = new SimpleSAML_XHTML_Template($config, 'openid-sites.php');
	
	$t->data['openidurl'] = getLoggedInUser();
	$t->data['sites'] = $sites;
	
	$t->show();    
	exit(0);
    
    
    // TODO Render sites
    //return sites_render($sites);
}



/**
 * Return an HTTP redirect response
 */
function redirect_render($redir_url)
{
	SimpleSAML_Utilities::redirect($redir_url);
}



























/*
 * SESSION
 */



/**
 * Get the URL of the current script
 */
function getServerURL()
{
    $path = $_SERVER['SCRIPT_NAME'];
    $host = $_SERVER['HTTP_HOST'];
    $port = $_SERVER['SERVER_PORT'];
    $s = $_SERVER['HTTPS'] ? 's' : '';
    if (($s && $port == "443") || (!$s && $port == "80")) {
        $p = '';
    } else {
        $p = ':' . $port;
    }
    
    return "http$s://$host$p$path";
}

/**
 * Build a URL to a server action
 */
function buildURL($action=null, $escaped=true)
{
    $url = getServerURL();
    if ($action) {
        $url .= '/' . $action;
    }
    return $escaped ? htmlspecialchars($url, ENT_QUOTES) : $url;
}

/**
 * Extract the current action from the request
 */
function getAction()
{
    $path_info = @$_SERVER['PATH_INFO'];
    $action = ($path_info) ? substr($path_info, 1) : '';
    $function_name = 'action_' . $action;
    return $function_name;
}

/**
 * Write the response to the request
 */
function writeResponse($resp)
{
    list ($headers, $body) = $resp;
    array_walk($headers, 'header');
    header(header_connection_close);
    print $body;
}

/**
 * Instantiate a new OpenID server object
 */
function getServer()
{
    static $server = null;
    if (!isset($server)) {
        $server =& new Auth_OpenID_Server(getOpenIDStore());
    }
    return $server;
}

/**
 * Return whether the trust root is currently trusted
 */
function isTrusted($identity_url, $trust_root)
{
    // from config.php
    global $trusted_sites;

    if ($identity_url != getLoggedInUser()) {
        return false;
    }

    if (in_array($trust_root, $trusted_sites)) {
        return true;
    }

    $sites = getSessionSites();
    return isset($sites[$trust_root]) && $sites[$trust_root];
}

/**
 * Return a hashed form of the user's password
 */
function hashPassword($password)
{
    return bin2hex(Auth_OpenID_SHA1($password));
}

/**
 * Check the user's login information
 */
function checkLogin($openid_url, $password)
{
    // from config.php
    global $openid_users;
    $hash = hashPassword($password);

    return isset($openid_users[$openid_url])
        && $hash == $openid_users[$openid_url];
}

/**
 * Get the openid_url out of the cookie
 *
 * @return mixed $openid_url The URL that was stored in the cookie or
 * false if there is none present or if the cookie is bad.
 */
function getLoggedInUser()
{
    return isset($_SESSION['openid_url'])
        ? $_SESSION['openid_url']
        : false;
}

/**
 * Set the openid_url in the cookie
 *
 * @param mixed $identity_url The URL to set. If set to null, the
 * value will be unset.
 */
function setLoggedInUser($identity_url=null)
{
    if (!isset($identity_url)) {
        unset($_SESSION['openid_url']);
    } else {
        $_SESSION['openid_url'] = $identity_url;
    }
}

function setSessionSites($sites=null)
{
    if (!isset($sites)) {
        unset($_SESSION['session_sites']);
    } else {
        $_SESSION['session_sites'] = serialize($sites);
    }
}

function getSessionSites()
{
    return isset($_SESSION['session_sites'])
        ? unserialize($_SESSION['session_sites'])
        : false;
}

function getRequestInfo()
{
    return isset($_SESSION['request'])
        ? unserialize($_SESSION['request'])
        : false;
}

function setRequestInfo($info=null)
{
    if (!isset($info)) {
        unset($_SESSION['request']);
    } else {
        $_SESSION['request'] = serialize($info);
    }
}


function getSreg($identity)
{
    // from config.php
    global $openid_sreg;

    if (!is_array($openid_sreg)) {
        return null;
    }

    return $openid_sreg[$identity];

}














/*
 * OpenID Transactions
 */ 



function authCancel($info)
{
    if ($info) {
        setRequestInfo();
        $url = $info->getCancelURL();
    } else {
        $url = getServerURL();
    }
    redirect_render($url);
}

function doAuth($info, $trusted=null, $fail_cancels=false)
{
    if (!$info) {
        // There is no authentication information, so bail
        authCancel(null);
    }

    $req_url = $info->identity;
    $user = getLoggedInUser();
    setRequestInfo($info);

    if ($req_url != $user) {
			
		$config = SimpleSAML_Configuration::getInstance();
		$session = SimpleSAML_Session::getInstance();
		
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'OPENID', 
			new Exception('OpenID: simpleSAMLphp doauth():' . 'Your identity [' . $user . 
			'] does not match the requested identity from the OpenID consumer, which was: [' . $req_url . ']'));
		
		/*
		$t = new SimpleSAML_XHTML_Template($config, 'error.php');

		$t->data['header'] = 'OpenID identity mismatch';
		$t->data['message'] = 'Your identity ' . htmlspecialchars($user) . ' does not match the requested identity from the
			OpenID consumer, which was: ' . htmlspecialchars($req_url);
		$t->data['e'] = new Exception('OpenID Error');
		
		$t->show();    
		exit(0);
		*/
    }

    $sites = getSessionSites();
    $trust_root = $info->trust_root;
    $fail_cancels = $fail_cancels || isset($sites[$trust_root]);
    $trusted = isset($trusted) ? $trusted : isTrusted($req_url, $trust_root);
    
    if ($trusted) {
        setRequestInfo();
        $server =& getServer();
        $response =& $info->answer(true);
        $webresponse =& $server->encodeResponse($response);

        $new_headers = array();

        foreach ($webresponse->headers as $k => $v) {
            $new_headers[] = $k.": ".$v;
        }


		array_walk($new_headers, 'header');
		header(header_connection_close);
		print $webresponse->body;
        
        
    } elseif ($fail_cancels) {
        authCancel($info);
    } else {

		$config = SimpleSAML_Configuration::getInstance();
		$t = new SimpleSAML_XHTML_Template($config, 'openid-trust.php');
		
		$t->data['openidurl'] = getLoggedInUser();
		$t->data['siteurl'] = htmlspecialchars($request->trust_root);;
		$t->data['trusturl'] = buildURL('trust', true);
		
		$t->show();    
		exit(0);


    }
}




/*
 * Handle actions
 */


//init();
$action = getAction();
if (!function_exists($action)) {
	$action = 'action_default';
}
$action();



?>