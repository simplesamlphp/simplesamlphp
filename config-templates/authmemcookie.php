<?php

/**
 * This is the configuration file for the Auth MemCookie example.
 */

$config = array(

	/*
	 * This is the name of the cookie we should save the session id in. The value of this option must match the
	 * Auth_memCookie_CookieName option in the Auth MemCookie configuration. The default value is 'AuthMemCookie'.
	 *
	 * Default:
	 *  'cookiename' => 'AuthMemCookie',
	 */
	'cookiename' => 'AuthMemCookie',

	/*
	 * This option specifies the name of the attribute which contains the username of the user. It must be set to
	 * a valid attribute name.
	 *
	 * Examples:
	 *  'username' => 'uid', // LDAP attribute for user id.
	 *  'username' => 'mail', // LDAP attribute for email address.
	 *
	 * Default:
	 *  No default value.
	 */
	'username' => NULL,

	/*
	 * This option specifies the name of the attribute which contains the groups of the user. Set this option to
	 * NULL if you don't want to include any groups.
	 *
	 * Example:
	 *  'groups' => 'edupersonaffiliation',
	 *
	 * Default:
	 *  'groups' => NULL,
	 */
	'groups' => NULL,

	/*
	 * This option contains the hostname of IP address of the memcache server where we should store the
	 * authentication information. Thos option should match the address part of the
	 * Auth_memCookie_Memcached_AddrPort option in the Auth MemCookie configuration.
	 *
	 * Examples:
	 *  'memcache.host' => '192.168.93.52',
	 *  'memcache.host' => 'memcache.example.org',
	 *
	 * Default:
	 *  'memcache.host' => '127.0.0.1',
	 */
	'memcache.host' => '127.0.0.1',

	/*
	 * This option contains the port number of the memcache server where we should store the
	 * authentication information. This option should match the port part of the
	 * Auth_memCookie_Memcached_AddrPort option in the Auth MemCookie configuration.
	 *
	 * Default:
	 *  'memcache.port' => 11211,
	 */
	'memcache.port' => 11211,

);

?>