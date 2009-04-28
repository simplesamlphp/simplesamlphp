<?php
/**
 * Config file for consentAdmin
 *
 * @author Jacob Christiansen, <jach@wayf.dk>
 * @package simpleSAMLphp
 * @version $Id$
 */
$config = array(
	/*
	 * Configuration for the database connection.
	 */
	'consentadmin'  => array(
		'consent:Database',
		'dsn'		=>	'mysql:host=DBHOST;dbname=DBNAME',
		'username'	=>	'USERNAME', 
		'password'	=>	'PASSWORD',
	),
	
	// Hash attributes including values or not
	'attributes.hash' => TRUE,

	// Where to direct the user after logout
	'relaystate' => 'www.wayf.dk',
);
