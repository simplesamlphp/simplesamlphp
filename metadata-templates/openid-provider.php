<?php
/* 
 * OpenID Provider configuration
 *
 */


$metadata = array( 

	// Use the hostname as the array key
	'openidserver.example.org' => array(
	
		// The hostname of the server (VHOST) that this SAML entity will use.
		'host'				=>	'openidserver.example.org',
		
		// Authentication plugin to use. auth/login.php is the default one that uses LDAP.
		'auth'				=>	'saml2/sp/initSSO.php'
	)

);

?>
