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
	

	/*
	 * This configuration option allows you to select which session handler
	 * SimpleSAMLPHP should use to store the session information. Currently
	 * we have two session handlers:
	 * - 'phpsession': The default PHP session handler.
	 * - 'memcache': Stores the session information in one or more
	 *   memcache servers by using the MemcacheStore class.
	 *
	 * The default session handler is 'phpsession'.
	 */
	'session.handler'       => 'phpsession',


	/*
	 * Configuration for the MemcacheStore class. This allows you to store
	 * multiple redudant copies of sessions on different memcache servers.
	 *
	 * 'memcache_store.servers' is an array of server groups. Every data
	 * item will be mirrored in every server group.
	 *
	 * Each server group is an array of servers. The data items will be
	 * load-balanced between all servers in each server group.
	 *
	 * Each server is an array of parameters for the server. The following
	 * options are available:
	 *  - 'hostname': This is the hostname or ip address where the
	 *    memcache server runs. This is the only required option.
	 *  - 'port': This is the port number of the memcache server. If this
	 *    option isn't set, then we will use the 'memcache.default_port'
	 *    ini setting. This is 11211 by default.
	 *  - 'weight': This sets the weight of this server in this server
	 *    group. http://php.net/manual/en/function.Memcache-addServer.php
	 *    contains more information about the weight option.
	 *  - 'timeout': The timeout for this server. By default, the timeout
	 *    is 3 seconds.
	 *
	 * Example of redudant configuration with load balancing:
	 * This configuration makes it possible to lose both servers in the
	 * a-group or both servers in the b-group without losing any sessions.
	 * Note that sessions will be lost if one server is lost from both the
	 * a-group and the b-group.
	 *
	 * 'memcache_store.servers' => array(
	 *     array(
	 *         array('hostname' => 'mc_a1'),
	 *         array('hostname' => 'mc_a2'),
	 *     ),
	 *     array(
	 *         array('hostname' => 'mc_b1'),
	 *         array('hostname' => 'mc_b2'),
	 *     ),
	 * ),
	 *
	 * Example of simple configuration with only one memcache server,
	 * running on the same computer as the web server:
	 * Note that all sessions will be lost if the memcache server crashes.
	 *
	 * 'memcache_store.servers' => array(
	 *     array(
	 *         array('hostname' => 'localhost'),
	 *     ),
	 * ),
	 *
	 */
	'memcache_store.servers' => array(
		array(
			array('hostname' => 'localhost'),
		),
	),


);


?>