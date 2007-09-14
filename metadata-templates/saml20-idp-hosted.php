<?php
/* 
 * SAML 2.0 Meta data for simpleSAMLphp
 *
 * The SAML 2.0 IdP Hosted config is used by the SAML 2.0 IdP to identify itself.
 *
 */


$metadata = array( 

	// The SAML entity ID is the index of this config.
	'dev2.andreas.feide.no' => array(
	
		// The hostname of the server (VHOST) that this SAML entity will use.
		'host'				=>	'dev2.andreas.feide.no',
		
		// SAML endpoints.
		'SingleSignOnUrl'	=>	"http://dev2.andreas.feide.no/saml2/idp/SSOService.php",
		'SingleLogOutUrl'	=>	"http://dev2.andreas.feide.no/saml2/idp/LogoutService.php",
		
		// X.509 key and certificate. Relative to the cert directory.
		'privatekey'		=>	'server.pem',
		'certificate'		=>	'server.crt',
		
		/* If base64attributes is set to true, then all attributes will be base64 encoded. Make sure
		 * that you set the SP to have the same value for this.
		 */
		'base64attributes'	=>	false,
		
		// Authentication plugin to use. login.php is the default one that uses LDAP.
		'auth'				=>	'auth/login.php'
	)

);

?>
