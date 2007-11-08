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
	'baseurlpath'			=> 'simplesaml/',
	'templatedir'			=> 'templates/default/',
	'metadatadir'			=> 'metadata/',
	'attributenamemapdir'	=> 'attributemap/',
	
	/*
	 * If you set the debug parameter to true, all SAML messages will be visible in the
	 * browser, and require the user to click the submit button. If debug is set to false,
	 * Browser/POST SAML messages will be automaticly submitted.
	 */
	'debug'					=>	false,
	
	/*
	 * Logging.
	 * 
	 * Choose a syslog facility to use for logging.
	 * And define the minimum log level to log
	 *		LOG_ERR				No statistics, only errors
	 *		LOG_WARNING			No statistics, only warnings/errors
	 *		LOG_NOTICE			Statistics and errors 
	 *		LOG_INFO			Verbose logs
	 *		LOG_DEBUG			Full debug logs - not reccomended for production
	 */
	'logging.facility'		=> LOG_LOCAL5,
	'logging.level'			=> LOG_NOTICE,
	
	/* 
	 * This value is the duration of the session in seconds. Make sure that the time duration of
	 * cookies both at the SP and the IdP exceeds this duration.
	 */
	'session.duration'		=>  8 * (60*60), // 8 hours.
	
	'language.available'	=> array('en', 'no'),
	'language.default'		=> 'en',
	
	/*
	 * Default IdPs. If you do not enter an idpentityid in the SSO initialization endpoints,
	 * the default IdP configured here will be used.
	 *
	 * To enable the SAML 2.0 IdP Discovery service for a SAML 2.0 SP, you need to set the
	 * default-saml20-idp to be null, like this:
	 *
	 * 		'default-saml20-idp'	=> null,
	 *
	 */
	'default-saml20-idp'	=> 'max.feide.no',
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
	
	/*
	 * These parameters are only relevant if you setup an OpenID Provider.
	 */
	'openid.userid_attributename'		=>	'eduPersonPrincipalName',
	'openid.delegation_prefix'			=>	'https://openid.feide.no/',
	'openid.filestore'					=>	'/tmp/openidstore',
	
);


?>