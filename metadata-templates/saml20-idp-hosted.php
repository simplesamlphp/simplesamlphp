<?php
/* 
 * SAML 2.0 Meta data for simpleSAMLphp
 *
 * The SAML 2.0 IdP Hosted config is used by the SAML 2.0 IdP to identify itself.
 *
 * Required parameters:
 *   - host
 *   - privatekey
 *   - certificate
 *   - auth
 *   - authority
 *
 * Optional Parameters:
 *
 *
 * Request signing (optional paramters)
 *    When request.signing is true the privatekey and certificate of the SP
 *    will be used to sign/verify all messages received/sent with the HTTPRedirect binding.
 *    The certificate and privatekey from above will be used for signing and 
 *    verification purposes.  
 *
 *   - request.signing
 *
 */


$metadata = array( 

	// The SAML entity ID is the index of this config.
	'idp.example.org' => array(
	
		// The hostname of the server (VHOST) that this SAML entity will use.
		'host'				=>	'sp.example.org',
		
		// X.509 key and certificate. Relative to the cert directory.
		'privatekey'		=>	'server.pem',
		'certificate'		=>	'server.crt',
		
		// Authentication plugin to use. login.php is the default one that uses LDAP.
		'auth'				=>	'auth/login.php'
	)

);

?>
