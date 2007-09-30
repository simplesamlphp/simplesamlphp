<?php
/* 
 * The configuration of simpleSAMLphp
 * 
 * 
 */

$config = array (

	/*
	 * Setup the following parameters to match the directory of your installation.
	 * See the user manual for more details.
	 */
	'basedir' 				=> '/var/simplesamlphp/',
	'baseurlpath'			=> 'simplesamlphp/',
	'templatedir'			=> 'templates/',
	'metadatadir'			=> 'metadata/',
	
	/*
	 * If you set the debug parameter to true, all SAML messages will be visible in the
	 * browser, and require the user to click the submit button. If debug is set to false,
	 * Browser/POST SAML messages will be automaticly submitted.
	 */
	'debug'					=>	false,
	
	/* 
	 * This value is the duration of the session in seconds. Make sure that the time duration of
	 * cookies both at the SP and the IdP exceeds this duration.
	 */
	'session.duration'		=>  8 * (60*60), // 8 hours.
	
	/*
	 * Default IdPs. If you do not enter an idpentityid in the SSO initialization endpoints,
	 * the default IdP configured here will be used.
	 */
	'default-saml20-idp'	=> 'sam.feide.no',
	'default-shib13-idp'	=> 'urn:mace:switch.ch:aaitest:dukono.switch.ch',
	
	/*
	 * LDAP configuration. This is only relevant if you use the LDAP authentication plugin.
	 */
	'auth.ldap.dnpattern'	=> 'uid=%username%,dc=feide,dc=no,ou=feide,dc=uninett,dc=no',
	'auth.ldap.hostname'	=> 'ldap.uninett.no',
	'auth.ldap.attributes'	=> 'objectclass=*',
	
	/*
	 * Radius authentication. This is only relevant if you use the Radius authentication plugin.
	 */
	'auth.radius.hostname'	=> 'radius.example.org',
	'auth.radius.port'		=> '1812',
	'auth.radius.secret'	=> 'topsecret'
	
);


?>