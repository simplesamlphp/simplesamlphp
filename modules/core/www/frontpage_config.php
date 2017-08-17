<?php



// Load SimpleSAMLphp, configuration
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getSessionFromRequest();

// Check if valid local session exists.
if ($config->getBoolean('admin.protectindexpage', false)) {
    SimpleSAML\Utils\Auth::requireAdmin();
}
$loginurl = SimpleSAML\Utils\Auth::getAdminLoginURL();
$isadmin = SimpleSAML\Utils\Auth::isAdmin();


$warnings = array();

if (!\SimpleSAML\Utils\HTTP::isHTTPS()) {
	$warnings[] = '{core:frontpage:warnings_https}';
}

if ($config->getValue('secretsalt') === 'defaultsecretsalt') {
	$warnings[] = '{core:frontpage:warnings_secretsalt}';
}

if (extension_loaded('suhosin')) {
	$suhosinLength = ini_get('suhosin.get.max_value_length');
	if (empty($suhosinLength) || (int)$suhosinLength < 2048) {
		$warnings[] = '{core:frontpage:warnings_suhosin_url_length}';
	}
}





$links = array();
$links_welcome = array();
$links_config = array();
$links_auth = array();
$links_federation = array();



$links_config[] = array(
	'href' => \SimpleSAML\Utils\HTTP::getBaseURL() . 'admin/hostnames.php',
	'text' => '{core:frontpage:link_diagnostics}'
);

$links_config[] = array(
	'href' => \SimpleSAML\Utils\HTTP::getBaseURL() . 'admin/phpinfo.php',
	'text' => '{core:frontpage:link_phpinfo}'
);

$allLinks = array(
	'links'      => &$links,
	'welcome'    => &$links_welcome,
	'config'     => &$links_config,
	'auth'       => &$links_auth,
	'federation' => &$links_federation,
);
SimpleSAML\Module::callHooks('frontpage', $allLinks);

// Check for updates. Store the remote result in the session so we
// don't need to fetch it on every access to this page.
$current = $config->getVersion();
if ($config->getBoolean('admin.checkforupdates', true) && $current !== 'master') {
	$latest = $session->getData("core:latest_simplesamlphp_version", "version");

	if (!$latest) {
		$api_url = 'https://api.github.com/repos/simplesamlphp/simplesamlphp/releases';
		$ch = curl_init($api_url.'/latest');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 'SimpleSAMLphp');
		curl_setopt($ch, CURLOPT_TIMEOUT, 2);
		$response = curl_exec($ch);

		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) {
			$latest = json_decode($response, true);
			$session->setData("core:latest_simplesamlphp_version", "version", $latest);
		}
		curl_close($ch);
	}

	if ($latest && version_compare($current, ltrim($latest['tag_name'], 'v'), 'lt')) {
		$outdated = true;
		$warnings[] = array(
			'{core:frontpage:warnings_outdated}',
			array('%LATEST_URL%' => $latest['html_url'])
		);
	}
}

$enablematrix = array(
	'saml20-idp' => $config->getBoolean('enable.saml20-idp', false),
	'shib13-idp' => $config->getBoolean('enable.shib13-idp', false),
);


$functionchecks = array(
	'time'             => array('required', 'Date/Time Extension'),
	'hash'             => array('required',  'Hashing function'),
	'gzinflate'        => array('required',  'ZLib'),
	'openssl_sign'     => array('required',  'OpenSSL'),
	'dom_import_simplexml' => array('required', 'XML DOM'),
	'preg_match'       => array('required',  'RegEx support'),
	'json_decode'      => array('required', 'JSON support'),
	'class_implements' => array('required', 'Standard PHP Library (SPL)'),
	'mb_strlen'        => array('required', 'Multibyte String Extension'),
	'curl_init'        => array('optional', 'cURL (required if automatic version checks are used, also by some modules.'),
	'session_start'  => array('optional', 'Session Extension (required if PHP sessions are used)'),
	'pdo_drivers'    => array('optional',  'PDO Extension (required if a database backend is used)'),
);
if (SimpleSAML\Module::isModuleEnabled('ldap')) {
	$functionchecks['ldap_bind'] = array('optional',  'LDAP Extension (required if an LDAP backend is used)');
}
if (SimpleSAML\Module::isModuleEnabled('radius')) {
        $functionchecks['radius_auth_open'] = array('optional',  'Radius Extension (required if a Radius backend is used)');
}

$funcmatrix = array();
$funcmatrix[] = array(
	'required' => 'required', 
	'descr' => 'PHP Version >= 5.4. You run: ' . phpversion(),
	'enabled' => version_compare(phpversion(), '5.4', '>='));
foreach ($functionchecks AS $func => $descr) {
	$funcmatrix[] = array('descr' => $descr[1], 'required' => $descr[0], 'enabled' => function_exists($func));
}

$funcmatrix[] = array(
    'required' => 'optional',
    'descr' => 'predis/predis (required if the redis data store is used)',
    'enabled' => class_exists('\Predis\Client'),
);

$funcmatrix[] = array(
    'required' => 'optional',
    'descr' => 'Memcache or Memcached Extension (required if a Memcached backend is used)',
    'enabled' => class_exists('Memcache') || class_exists('Memcached'),
);

/* Some basic configuration checks */

if($config->getString('technicalcontact_email', 'na@example.org') === 'na@example.org') {
	$mail_ok = FALSE;
} else {
	$mail_ok = TRUE;
}
$funcmatrix[] = array(
	'required' => 'recommended',
	'descr' => 'technicalcontact_email option set',
	'enabled' => $mail_ok
	);
if($config->getString('auth.adminpassword', '123') === '123') {
	$password_ok = FALSE;
} else {
	$password_ok = TRUE;
}
$funcmatrix[] = array(
	'required' => 'required',
	'descr' => 'auth.adminpassword option set',
	'enabled' => $password_ok
);

$funcmatrix[] = array(
	'required' => 'recommended',
	'descr' => 'Magic Quotes should be turned off',
	'enabled' => (get_magic_quotes_runtime() == 0)
);


$t = new SimpleSAML_XHTML_Template($config, 'core:frontpage_config.tpl.php');
$t->data['pageid'] = 'frontpage_config';
$t->data['isadmin'] = $isadmin;
$t->data['loginurl'] = $loginurl;
$t->data['warnings'] = $warnings;


$t->data['links'] = $links;
$t->data['links_welcome'] = $links_welcome;
$t->data['links_config'] = $links_config;
$t->data['links_auth'] = $links_auth;
$t->data['links_federation'] = $links_federation;



$t->data['enablematrix'] = $enablematrix;
$t->data['funcmatrix'] = $funcmatrix;
$t->data['requiredmap'] = array(
    'recommended' => $t->noop('{core:frontpage:recommended}'),
    'required' => $t->noop('{core:frontpage:required}'),
    'optional' => $t->noop('{core:frontpage:optional}'),
);
$t->data['version'] = $config->getVersion();
$t->data['directory'] = dirname(dirname(dirname(dirname(__FILE__))));

$t->show();


