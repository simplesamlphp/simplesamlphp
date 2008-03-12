<?php
/* 
 * Configuration for the LDAP authentication module.
 * 
 * $Id: $
 */

$config = array (

	/**
	 * LDAP configuration. This is only relevant if you use the LDAP authentication plugin.
	 *
	 * The attributes parameter is a list of attributes that should be retrieved.
	 * If the attributes parameter is set to null, all attributes will be retrieved.
	 */
	'auth.ldap.dnpattern'  => 'uid=%username%,dc=feide,dc=no,ou=feide,dc=uninett,dc=no',
	'auth.ldap.hostname'   => 'ldap.uninett.no',
	'auth.ldap.attributes' => null,
	'auth.ldap.enable_tls' => false,
	
);

?>
