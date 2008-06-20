<?php

require_once('_include.php');

/* Load simpleSAMLphp, configuration */
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

/* Check if valid local session exists.. */
if ($config->getValue('admin.protectindexpage', false)) {
	if (!isset($session) || !$session->isValid('login-admin') ) {
		SimpleSAML_Utilities::redirect('/' . $config->getBaseURL() . 'auth/login-admin.php',
			array('RelayState' => SimpleSAML_Utilities::selfURL())
		);
	}
}

$warnings = array();

if (SimpleSAML_Utilities::getSelfProtocol() != 'https') {
	$warnings[] = 'warnings_https';
}

	
$links = array();


if ($config->getValue('enable.saml20-sp') === true)
	$links[] = array(
		'href' => 'example-simple/saml2-example.php', 
		'text' => 'link_saml2example');

if ($config->getValue('enable.shib13-sp') === true)
	$links[] = array(
		'href' => 'example-simple/shib13-example.php', 
		'text' => 'link_shib13example');

if ($config->getValue('enable.openid-provider') === true)
	$links[] = array(
		'href' => 'openid/provider/server.php', 
		'text' => 'link_openidprovider');

$links[] = array(
	'href' => 'example-simple/hostnames.php?dummy=1', 
	'text' => 'link_diagnostics');

$links[] = array(
	'href' => 'admin/phpinfo.php', 
	'text' => 'link_phpinfo');

$links[] = array(
	'href' => 'admin/config.php',
	'text' => 'link_configcheck',
	);

if($config->getBoolean('idpdisco.enableremember', FALSE)) {
	$links[] = array(
		'href' => 'cleardiscochoices.php',
		'text' => 'link_cleardiscochoices',
		);
}

if ($config->getValue('enable.saml20-idp') === TRUE) {
	$publishURL = $config->getString('metashare.publishurl', NULL);
	if ($publishURL !== NULL) {
		$metadataURL = SimpleSAML_Utilities::resolveURL('saml2/idp/metadata.php');
		$publishURL = SimpleSAML_Utilities::addURLparameter($publishURL, 'url=' . urlencode($metadataURL));
		$links[] = array(
			'href' => $publishURL,
			'text' => 'link_publish',
			);
	}
}


$linksmeta = array();

$linksmeta[] = array(
	'href' => 'admin/metadata.php', 
	'text' => 'link_meta_overview');

if ($config->getValue('enable.saml20-sp') === true)
	$linksmeta[] = array(
		'href' => 'saml2/sp/metadata.php?output=xhtml', 
		'text' => 'link_meta_saml2sphosted');

if ($config->getValue('enable.saml20-idp') === true)
	$linksmeta[] = array(
		'href' => 'saml2/idp/metadata.php?output=xhtml', 
		'text' => 'link_meta_saml2idphosted');

if ($config->getValue('enable.shib13-sp') === true)
	$linksmeta[] = array(
		'href' => 'shib13/sp/metadata.php?output=xhtml', 
		'text' => 'link_meta_shib13sphosted');

if ($config->getValue('enable.shib13-idp') === true)
	$linksmeta[] = array(
		'href' => 'shib13/idp/metadata.php?output=xhtml', 
		'text' => 'link_meta_shib13idphosted');


$linksmeta[] = array(
	'href' => 'admin/metadata-converter.php',
	'text' => 'link_xmlconvert',
	);



$linksdoc = array();

$linksdoc[] = array(
	'href' => 'http://rnd.feide.no/content/installing-simplesamlphp', 
	'text' => 'link_doc_install');

if ($config->getValue('enable.saml20-sp', false ) || $config->getValue('enable.shib13-sp', false))
	$linksdoc[] = array(
		'href' => 'http://rnd.feide.no/content/using-simplesamlphp-service-provider', 
		'text' => 'link_doc_sp');

if ($config->getValue('enable.saml20-idp', false ) || $config->getValue('enable.shib13-idp', false))
	$linksdoc[] = array(
		'href' => 'http://rnd.feide.no/content/using-simplesamlphp-identity-provider', 
		'text' => 'link_doc_idp');

if ($config->getValue('enable.shib13-idp', false))
	$linksdoc[] = array(
		'href' => 'http://rnd.feide.no/content/configure-shibboleth-13-sp-work-simplesamlphp-idp', 
		'text' => 'link_doc_shibsp');

if ($config->getValue('enable.saml20-idp', false ))
	$linksdoc[] = array(
		'href' => 'http://rnd.feide.no/content/simplesamlphp-idp-google-apps-education', 
		'text' => 'link_doc_googleapps');

$linksdoc[] = array(
	'href' => 'http://rnd.feide.no/content/simplesamlphp-advanced-features', 
	'text' => 'link_doc_advanced',
);



$linksdoc[] = array(
	'href' => 'http://rnd.feide.no/content/simplesamlphp-maintenance-and-configuration', 
	'text' => 'link_doc_maintenance');

$enablematrix = array(
	'saml20-sp' => $config->getValue('enable.saml20-sp', false),
	'saml20-idp' => $config->getValue('enable.saml20-idp', false),
	'shib13-sp' => $config->getValue('enable.shib13-sp', false),
	'shib13-idp' => $config->getValue('enable.shib13-idp', false),
);


$functionchecks = array(
	'hash'             => array('required',  'Hashing function'),
	'gzinflate'        => array('required',  'ZLib'),
	'openssl_sign'     => array('required',  'OpenSSL'),
	'simplexml_import_dom' => array('required', 'SimpleXML'),
	'dom_import_simplexml' => array('required', 'XML DOM'),
	'preg_match'       => array('required',  'RegEx support'),
	'ldap_bind'        => array('required_ldap',  'LDAP Extension'),
	'radius_auth_open' => array('required_radius',  'Radius Extension'),
	'mcrypt_module_open'=> array('optional',  'MCrypt'),
	'mysql_connect'    => array('optional',  'MySQL support'),
);
$funcmatrix = array();
$funcmatrix[] = array(
	'required' => 'required', 
	'descr' => 'PHP Version >= 5.1.2. You run: ' . phpversion(), 
	'enabled' => version_compare(phpversion(), '5.1.2', '>='));
$funcmatrix[] = array(
	'required' => 'reccomended', 
	'descr' => 'PHP Version >= 5.2 (Required for Shibboleth 1.3 SP)', 
	'enabled' => version_compare(phpversion(), '5.2', '>='));
foreach ($functionchecks AS $func => $descr) {
	$funcmatrix[] = array('descr' => $descr[1], 'required' => $descr[0], 'enabled' => function_exists($func));
}


/* Some basic configuration checks */

if($config->getValue('technicalcontact_email', 'na@example.org') === 'na@example.org') {
	$mail_ok = FALSE;
} else {
	$mail_ok = TRUE;
}
$funcmatrix[] = array(
	'required' => 'reccomended',
	'descr' => 'technicalcontact_email option set',
	'enabled' => $mail_ok
	);
if($config->getValue('auth.adminpassword', '123') === '123') {
	$password_ok = FALSE;
} else {
	$password_ok = TRUE;
}
$funcmatrix[] = array(
	'required' => 'required',
	'descr' => 'auth.adminpassword option set',
	'enabled' => $password_ok
	);



$t = new SimpleSAML_XHTML_Template($config, 'frontpage.php', 'frontpage.php');
$t->data['header'] = 'simpleSAMLphp installation page';
$t->data['icon'] = 'compass_l.png';
$t->data['warnings'] = $warnings;
$t->data['links'] = $links;
$t->data['links_meta'] = $linksmeta;
$t->data['links_doc'] = $linksdoc;
$t->data['enablematrix'] = $enablematrix;
$t->data['funcmatrix'] = $funcmatrix;

$t->show();



?>