<?php
/* 
 * The configuration of simpleSAMLphp
 * 
 * $Id$
 */

$config = array (

	/**
	 * Setup the following parameters to match the directory of your installation.
	 * See the user manual for more details.
	 */
	'baseurlpath'           => 'simplesaml/',
	'certdir'               => 'cert/',
	'loggingdir'            => 'log/',
	'datadir'               => 'data/',

	/*
	 * A directory where simpleSAMLphp can save temporary files.
	 *
	 * SimpleSAMLphp will attempt to create this directory if it doesn't exist.
	 */
	'tempdir'               => '/tmp/simplesaml',
	

	/**
	 * If you set the debug parameter to true, all SAML messages will be visible in the
	 * browser, and require the user to click the submit button. If debug is set to false,
	 * Browser/POST SAML messages will be automaticly submitted.
	 */
	'debug'                 =>	FALSE,
	'showerrors'            =>	TRUE,

	/**
	 * This option allows you to enable validation of XML data against its
	 * schemas. A warning will be written to the log if validation fails.
	 */
	'debug.validatexml' => FALSE,

	/**
	 * This password must be kept secret, and modified from the default value 123.
	 * This password will give access to the installation page of simpleSAMLphp with
	 * metadata listing and diagnostics pages.
	 */
	'auth.adminpassword'		=> '123',
	'admin.protectindexpage'	=> false,
	'admin.protectmetadata'		=> false,

	/**
	 * This is a secret salt used by simpleSAMLphp when it needs to generate a secure hash
	 * of a value. It must be changed from its default value to a secret value. The value of
	 * 'secretsalt' can be any valid string of any length.
	 *
	 * A possible way to generate a random salt is by running the following command from a unix shell:
	 * tr -c -d '0123456789abcdefghijklmnopqrstuvwxyz' </dev/urandom | dd bs=32 count=1 2>/dev/null;echo
	 */
	'secretsalt' => 'defaultsecretsalt',
	
	/*
	 * Some information about the technical persons running this installation.
	 * The email address will be used as the recipient address for error reports, and
	 * also as the technical contact in generated metadata.
	 */
	'technicalcontact_name'     => 'Administrator',
	'technicalcontact_email'    => 'na@example.org',

	/*
	 * The timezone of the server. This option should be set to the timezone you want
	 * simpleSAMLphp to report the time in. The default is to guess the timezone based
	 * on your system timezone.
	 *
	 * See this page for a list of valid timezones: http://php.net/manual/en/timezones.php
	 */
	'timezone' => NULL,

	/*
	 * Logging.
	 * 
	 * define the minimum log level to log
	 *		LOG_ERR				No statistics, only errors
	 *		LOG_WARNING			No statistics, only warnings/errors
	 *		LOG_NOTICE			Statistics and errors 
	 *		LOG_INFO			Verbose logs
	 *		LOG_DEBUG			Full debug logs - not reccomended for production
	 * 
	 * Choose logging handler.
	 * 
	 * Options: [syslog,file,errorlog]
	 * 
	 */
	'logging.level'         => LOG_NOTICE,
	'logging.handler'       => 'syslog',

	/*
	 * Choose which facility should be used when logging with syslog.
	 *
	 * These can be used for filtering the syslog output from simpleSAMLphp into its
	 * own file by configuring the syslog daemon.
	 *
	 * See the documentation for openlog (http://php.net/manual/en/function.openlog.php) for available
	 * facilities. Note that only LOG_USER is valid on windows.
	 *
	 * The default is to use LOG_LOCAL5 if available, and fall back to LOG_USER if not.
	 */
	'logging.facility' => defined('LOG_LOCAL5') ? constant('LOG_LOCAL5') : LOG_USER,

	/*
	 * The process name that should be used when logging to syslog.
	 * The value is also written out by the other logging handlers.
	 */
	'logging.processname' => 'simplesamlphp',

	/* Logging: file - Logfilename in the loggingdir from above.
	 */
	'logging.logfile'		=> 'simplesamlphp.log',
	
	

	/*
	 * Enable
	 * 
	 * Which functionality in simpleSAMLphp do you want to enable. Normally you would enable only 
	 * one of the functionalities below, but in some cases you could run multiple functionalities.
	 * In example when you are setting up a federation bridge.
	 */
	'enable.saml20-idp'		=> false,
	'enable.shib13-idp'		=> false,
	'enable.wsfed-sp'		=> false,
	'enable.authmemcookie' => false,

	/* 
	 * This value is the duration of the session in seconds. Make sure that the time duration of
	 * cookies both at the SP and the IdP exceeds this duration.
	 */
	'session.duration'		=>  8 * (60*60), // 8 hours.
	'session.requestcache'	=>  4 * (60*60), // 4 hours

	/*
	 * Sets the duration, in seconds, data should be stored in the datastore. As the datastore is used for
	 * login and logout requests, thid option will control the maximum time these operations can take.
	 * The default is 4 hours (4*60*60) seconds, which should be more than enough for these operations.
	 */
	'session.datastore.timeout' => (4*60*60), // 4 hours
	
	/*
	 * Options to override the default settings for php sessions.
	 */
	'session.phpsession.cookiename'  => null,
	'session.phpsession.limitedpath' => false,
	'session.phpsession.savepath'    => null,
	
	/*
	 * Languages available and what language is default
	 */
	'language.available'	=> array('en', 'no', 'nn', 'se', 'fi', 'da', 'sv', 'de', 'es', 'fr', 'nl', 'lb', 'hr', 'hu', 'pl', 'sl', 'pt', 'pt-BR', 'tr'),
	'language.default'		=> 'en',
	
	/*
	 * Which theme directory should be used?
	 */
	'theme.use' 		=> 'default',

	
	/*
	 * Default IdP for WS-Fed.
	 */
	'default-wsfed-idp'	=> 'urn:federation:pingfederate:localhost',

	/*
	 * Whether the discovery service should allow the user to save his choice of IdP.
	 */
	'idpdisco.enableremember' => TRUE,
	'idpdisco.rememberchecked' => TRUE,
	
	// Disco service only accepts entities it knows.
	'idpdisco.validate' => TRUE,
	
	'idpdisco.extDiscoveryStorage' => NULL, 

	/*
	 * IdP Discovery service look configuration. 
	 * Wether to display a list of idp or to display a dropdown box. For many IdP' a dropdown box  
	 * gives the best use experience.
	 * 
	 * When using dropdown box a cookie is used to highlight the previously chosen IdP in the dropdown.  
	 * This makes it easier for the user to choose the IdP
	 * 
	 * Options: [links,dropdown]
	 * 
	 */
	'idpdisco.layout' => 'dropdown',

	/*
	 * Whether simpleSAMLphp should sign the response or the assertion in SAML 1.1 authentication
	 * responses.
	 *
	 * The default is to sign the assertion element, but that can be overridden by setting this
	 * option to TRUE. It can also be overridden on a pr. SP basis by adding an option with the
	 * same name to the metadata of the SP.
	 */
	'shib13.signresponse' => TRUE,
	
	
	
	/*
	 * Authentication processing filters that will be executed for all IdPs
	 * Both Shibboleth and SAML 2.0
	 */
	'authproc.idp' => array(
		/* Enable the authproc filter below to add URN Prefixces to all attributes
 		10 => array(
 			'class' => 'core:AttributeMap', 'addurnprefix'
 		), */
 		/* Enable the authproc filter below to automatically generated eduPersonTargetedID. 
 		20 => 'core:TargetedID',
 		*/

		// Adopts language from attribute to use in UI
 		30 => 'core:LanguageAdaptor',
 		
		/* Add a realm attribute from edupersonprincipalname
		40 => 'core:AttributeRealm',
		 */
		45 => array(
			'class' => 'core:StatisticsWithAttribute',
			'attributename' => 'realm',
			'type' => 'saml20-idp-SSO',
		),

		/* When called without parameters, it will fallback to filter attributes ‹the old way›
		 * by checking the 'attributes' parameter in metadata on IdP hosted and SP remote.
		 */
		50 => 'core:AttributeLimit', 

		/* 
		 * Search attribute "distinguishedName" for pattern and replaces if found

		60 => array(
			'class'		=> 'core:AttributeAlter',
			'pattern'	=> '/OU=studerende/',
			'replacement'	=> 'Student',
			'subject'	=> 'distinguishedName',
			'%replace',	
		),
		 */

		/*
		 * Consent module is enabled (with no permanent storage, using cookies).

		90 => array(
			'class' 	=> 'consent:Consent', 
			'store' 	=> 'consent:Cookie', 
			'focus' 	=> 'yes', 
			'checked' 	=> TRUE
		),
		 */
		// If language is set in Consent module it will be added as an attribute.
 		99 => 'core:LanguageAdaptor',
	),
	/*
	 * Authentication processing filters that will be executed for all IdPs
	 * Both Shibboleth and SAML 2.0
	 */
	'authproc.sp' => array(
		/*
		10 => array(
			'class' => 'core:AttributeMap', 'removeurnprefix'
		),
		*/

		/* When called without parameters, it will fallback to filter attributes ‹the old way›
		 * by checking the 'attributes' parameter in metadata on SP hosted and IdP remote.
		 */
		50 => 'core:AttributeLimit', 

		/*
		 * Generate the 'group' attribute populated from other variables, including eduPersonAffiliation.
		 */
 		60 => array('class' => 'core:GenerateGroups', 'eduPersonAffiliation'),
 		// All users will be members of 'users' and 'members' 	
 		61 => array('class' => 'core:AttributeAdd', 'groups' => array('users', 'members')),
 		
		// Adopts language from attribute to use in UI
 		90 => 'core:LanguageAdaptor',

	),
	

	/*
	 * This option configures the metadata sources. The metadata sources is given as an array with
	 * different metadata sources. When searching for metadata, simpleSAMPphp will search through
	 * the array from start to end.
	 *
	 * Each element in the array is an associative array which configures the metadata source.
	 * The type of the metadata source is given by the 'type' element. For each type we have
	 * different configuration options.
	 *
	 * Flat file metadata handler:
	 * - 'type': This is always 'flatfile'.
	 * - 'directory': The directory we will load the metadata files from. The default value for
	 *                this option is the value of the 'metadatadir' configuration option, or
	 *                'metadata/' if that option is unset.
	 *
	 * XML metadata handler:
	 * This metadata handler parses an XML file with either an EntityDescriptor element or an
	 * EntitiesDescriptor element. The XML file may be stored locally, or (for debugging) on a remote
	 * web server.
	 * The XML hetadata handler defines the following options:
	 * - 'type': This is always 'xml'.
	 * - 'file': Path to the XML file with the metadata.
	 * - 'url': The url to fetch metadata from. THIS IS ONLY FOR DEBUGGING - THERE IS NO CACHING OF THE RESPONSE.
	 *
	 *
	 * Examples:
	 *
	 * This example defines two flatfile sources. One is the default metadata directory, the other
	 * is a metadata directory with autogenerated metadata files.
	 *
	 * 'metadata.sources' => array(
	 *     array('type' => 'flatfile'),
	 *     array('type' => 'flatfile', 'directory' => 'metadata-generated'),
	 *     ),
	 *
	 * This example defines a flatfile source and an XML source.
	 * 'metadata.sources' => array(
	 *     array('type' => 'flatfile'),
	 *     array('type' => 'xml', 'file' => 'idp.example.org-idpMeta.xml'),
	 *     ),
	 *
	 *
	 * Default:
	 * 'metadata.sources' => array(
	 *     array('type' => 'flatfile')
	 *     ),
	 */
	'metadata.sources' => array(
		array('type' => 'flatfile'),
	),


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


	/*
	 * This value is the duration data should be stored in memcache. Data
	 * will be dropped from the memcache servers when this time expires.
	 * The time will be reset every time the data is written to the
	 * memcache servers.
	 *
	 * This value should always be larger than the 'session.duration'
	 * option. Not doing this may result in the session being deleted from
	 * the memcache servers while it is still in use.
	 *
	 * Set this value to 0 if you don't want data to expire.
	 *
	 * Note: The oldest data will always be deleted if the memcache server
	 * runs out of storage space.
	 */
	'memcache_store.expires' =>  36 * (60*60), // 36 hours.


	/*
	 * Should signing of generated metadata be enabled by default.
	 *
	 * Metadata signing can also be enabled for a individual SP or IdP by setting the
	 * same option in the metadata for the SP or IdP.
	 */
	'metadata.sign.enable' => FALSE,

	/*
	 * The default key & certificate which should be used to sign generated metadata. These
	 * are files stored in the cert dir.
	 * These values can be overridden by the options with the same names in the SP or
	 * IdP metadata.
	 *
	 * If these aren't specified here or in the metadata for the SP or IdP, then
	 * the 'certificate' and 'privatekey' option in the metadata will be used.
	 * if those aren't set, signing of metadata will fail.
	 */
	'metadata.sign.privatekey' => NULL,
	'metadata.sign.privatekey_pass' => NULL,
	'metadata.sign.certificate' => NULL,

	/*
	 * This is the default URL to a MetaShare service where a SAML 2.0 IdP can register its metadata.
	 * This is a highly experimentar feature.
	 */
	'metashare.publishurl' => NULL,

);


?>