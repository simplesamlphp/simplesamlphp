<?php
/* 
 * The configuration of simpleSAMLphp
 * 
 * 
 */

$ldapfeide = array (

	'example1.com' => array(
		'description'	=> 'Example Org 1',
		'searchbase'	=> 'cn=people,dc=example1,dc=com',
		'hostname'	=> 'ldaps://ldap.example1.com',
		'attributes'	=> null,
	),
	'example2.com' => array(
		'description'	=> 'Example Org 2',
		'searchbase'	=> 'cn=people,dc=example2,dc=com',
		'hostname'	=> 'ldaps://ldap.example2.com',
		'attributes'	=> array('mail', 'street'),
	)
	
);



?>
