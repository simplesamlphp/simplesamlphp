<?php
/**
 * SAML 2.0 remote IdP metadata for simpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://rnd.feide.no/content/idp-remote-metadata-reference
 */

/*
 * Guest IdP. allows users to sign up and register. Great for testing!
 */
$metadata['https://openidp.feide.no'] = array(
	'name' => array(
		'en' => 'Feide OpenIdP - guest users',
		'no' => 'Feide Gjestebrukere',
	),
	'description'          => 'Here you can login with your account on Feide RnD OpenID. If you do not already have an account on this identity provider, you can create a new one by following the create new account link and follow the instructions.',

	'SingleSignOnService'  => 'https://openidp.feide.no/simplesaml/saml2/idp/SSOService.php',
	'SingleLogoutService'  => 'https://openidp.feide.no/simplesaml/saml2/idp/SingleLogoutService.php',
	'certFingerprint'      => 'c9ed4dfb07caf13fc21e0fec1572047eb8a7a4cb'
);


/*
 * Feide, the norwegian federation. Test and production metadata.
 */
$metadata['https://idp-test.feide.no'] = array(
	'name' => array(
		'en' => 'Feide Test environment',
		'no' => 'Feide testmiljø',
	),
	'description'                  => 'Feide test environment (idp-test.feide.no). Authenticate with your identity from a school or university in Norway.',

	'SingleSignOnService'          => 'https://idp-test.feide.no/simplesaml/saml2/idp/SSOService.php',
	'SingleLogoutService'          => 'https://idp-test.feide.no/simplesaml/saml2/idp/SingleLogoutService.php',

	'certFingerprint'              => 'fa982efdb69f26e8073c8f815a82a0c5885960a2',
	'hint.cidr'                    => '158.38.0.0/16',
);

$metadata['https://idp.feide.no'] = array(
	'name' => 'Feide',
	'description' => array(
		'en' => 'Authenticate with your identity from a school or university in Norway.',
		'no' => 'Logg inn med din identitet fra skolen eller universitetet du er tilknyttet (i Norge).',
	),
	'SingleSignOnService'          => 'https://idp.feide.no/simplesaml/saml2/idp/SSOService.php',
	'SingleLogoutService'          => 'https://idp.feide.no/simplesaml/saml2/idp/SingleLogoutService.php',
	'certFingerprint'              => 'cde69e332fa7dd0eaa99ee0ddf06916e8942ac53',
	'hint.cidr'                    => '158.38.0.0/16',
);
