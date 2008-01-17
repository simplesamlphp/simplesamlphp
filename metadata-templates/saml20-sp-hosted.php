<?php
/* 
 * SAML 2.0 Meta data for simpleSAMLphp
 *
 * The SAML 2.0 IdP Remote config is used by the SAML 2.0 SP to identify itself.
 *
 */
 
$metadata = array( 

	/*
	 * Example of a hosted SP 
	 */
	'sp.example.org' => array(
		'host'							=>	'sp.example.org',
		'spNameQualifier' 				=>	'sp.example.org',
		'NameIDFormat'					=>	'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
		'ForceAuthn'					=>	'false',

		/*
		 * This option configures the name of a file which contains a
		 * RSA key for this service provider. The file must be located
		 * in the cert-directory of the SimpleSAMLPHP installation.
		 *
		 * This key will be used to sign all outgoing authentication-
		 * requests, logoutrequests and logoutresponses (everything
		 * that uses the HTTP-Redirect binding).
		 *
		 * To enable signing, set this option to a private key file
		 * and enable the 'binding.httpredirect.sign' global option.
		 */
		'privatekey' => 'server.pem',

	)

);


?>