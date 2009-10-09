<?php
/**
 * SAML 1.1 IdP configuration for simpleSAMLphp.
 *
 * See: https://rnd.feide.no/content/idp-hosted-metadata-reference
 */

$metadata['__DYNAMIC:1__'] = array(

	/*
	 * The hostname of the server (VHOST) that will use this SAML entity.
	 *
	 * Can be '__DEFAULT__', to use this entry by default.
	 */
	'host' => '__DEFAULT__',

	/* X.509 key and certificate. Relative to the cert directory. */
	'privatekey' => 'server.pem',
	'certificate' => 'server.crt',

	/*
	 * Authentication source to use. Must be one that is configured in
	 * 'config/authsources.php'.
	 */
	'auth' => 'example-userpass',
);
