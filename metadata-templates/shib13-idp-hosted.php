<?php
/* 
 * Shibboleth 1.3 IdP Meta data for simpleSAMLphp
 *
 *
 *
 */


$metadata = array(
	'__DYNAMIC:1__'	=> array(

		'host'							=>	'__DEFAULT__',
		'audience'						=> 'urn:mace:feide:shiblab',
		
		// X.509 key and certificate. Relative to the cert directory.
		'privatekey'		=>	'server.pem',
		'certificate'		=>	'server.crt',
		
		// Authentication plugin to use. login.php is the default one that uses LDAP.
		'auth'				=>	'auth/login.php'
	)
);

?>