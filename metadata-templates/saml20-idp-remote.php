<?php
/* 
 * SAML 2.0 Meta data for simpleSAMLphp
 *
 * The SAML 2.0 IdP Remote config is used by the SAML 2.0 SP to identify trusted SAML 2.0 IdPs.
 *
 */
 
$metadata = array();

$metadata['https://openidp.feide.no'] = array(
	'name'                 => array(
		'en' => 'Feide OpenIdP - guest users',
		'no' => 'Feide Gjestebrukere',
	),
	'description'          => 'Here you can login with your account on Feide RnD OpenID. If you do not already have an account on this identity provider, you can create a new one by following the create new account link and follow the instructions.',
	
	'send_metadata_email'  => 'moria-support@uninett.no',
	'SingleSignOnService'  => 'https://openidp.feide.no/simplesaml/saml2/idp/SSOService.php',
	'SingleLogoutService'  => 'https://openidp.feide.no/simplesaml/saml2/idp/SingleLogoutService.php',
	'certFingerprint'      => 'c9ed4dfb07caf13fc21e0fec1572047eb8a7a4cb'
);
	
$metadata['max.feide.no'] = array(
	'name'					=>	array(
		'en' => 'Feide Test environment',
		'no' => 'Feide testmiljø',
	),
	'description'			=> 'Feide test environment (max.feide.no). Authenticate with your identity from a school or university in Norway.',
	'send_metadata_email' 	=> 'moria-support@uninett.no',
	'SingleSignOnService'	=>	'https://max.feide.no/amserver/SSORedirect/metaAlias/idp',
	'SingleLogoutService'	=>	'https://max.feide.no/amserver/IDPSloRedirect/metaAlias/idp',
	'certFingerprint'		=>	'5dd3196bdb2fb7e75380fe234a3f4f2d1e8d6d84',
	'base64attributes'		=>	TRUE,
	'hint.cidr'				=> '158.38.0.0/16'
);

$metadata['sam.feide.no'] = array(
	'name'					=>	'Feide',
	'description'			=> array(
		'en' => 'Authenticate with your identity from a school or university in Norway.',
		'no' => 'Logg inn med din identitet fra skolen eller universitetet du er tilknyttet (i Norge).',
	),
	'send_metadata_email' 	=> 'moria-support@uninett.no',
	'SingleSignOnService'	=>	'https://sam.feide.no/amserver/SSORedirect/metaAlias/idp',
	'SingleLogoutService'	=>	'https://sam.feide.no/amserver/IDPSloRedirect/metaAlias/idp',
	'certFingerprint'		=>	'f6:72:c5:e7:04:fb:86:5e:93:6b:3b:cd:45:b0:49:2e:94:f5:f0:95',
	'base64attributes'		=>	TRUE,
	'hint.cidr'				=> '158.38.0.0/16'
);

$metadata['https://wayf.wayf.dk'] = array(
	'name'                 => array(
		'en' => 'DK-WAYF Production server',
		'da' => 'DK-WAYF Produktionsmiljøet',
	),
	'description'          => 'Login with your identity from a danish school, university or library.',
	'send_metadata_email' 	=> 'sekretariat@wayf.dk',
	'SingleSignOnService'  => 'https://wayf.wayf.dk/saml2/idp/SSOService.php',
	'SingleLogoutService'  => 'https://wayf.wayf.dk/saml2/idp/SingleLogoutService.php',
	'certFingerprint'      => 'c215d7bf9d51c7805055239f66b957d9a72ff44b'
);

$metadata['https://betawayf.wayf.dk'] = array(
	'name'                 => array(
		'en' => 'DK-WAYF Quality Assurance',
		'da' => 'DK-WAYF Quality Assurance miljøet',
	),
	'description'          => 'Login with your identity from a danish school, university or library.',
	'send_metadata_email' 	=> 'sekretariat@wayf.dk',
	'SingleSignOnService'  => 'https://betawayf.wayf.dk/saml2/idp/SSOService.php',
	'SingleLogoutService'  => 'https://betawayf.wayf.dk/saml2/idp/SingleLogoutService.php',
	'certFingerprint'      => 'c215d7bf9d51c7805055239f66b957d9a72ff44b'
);

$metadata['https://testidp.wayf.dk'] = array(
	'name'                 => array(
		'en' => 'DK-WAYF Test Server',
		'da' => 'DK-WAYF Test Miljøet',
	),
	'description'          => 'Login with your identity from a danish school, university or library.',
	'send_metadata_email' 	=> 'sekretariat@wayf.dk',
	'SingleSignOnService'  => 'https://testidp.wayf.dk/saml2/idp/SSOService.php',
	'SingleLogoutService'  => 'https://testidp.wayf.dk/saml2/idp/SingleLogoutService.php',
	'certFingerprint'      => '04b3b08bce004c27458b3e85b125273e67ef062b'
);


?>