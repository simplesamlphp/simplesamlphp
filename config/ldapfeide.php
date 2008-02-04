<?php
/* 
 * The configuration of simpleSAMLphp
 * 
 * 
 */




$ldapfeide = array (

	'uio.no' => array(
		'description'	=> 'UiO',
		'searchbase'	=> 'cn=people,dc=uio,dc=no',
		'hostname'	=> 'ldaps://ldap.uio.no',
		'attributes'	=> 'objectclass=*',
	),
	'uninett.no' => array(
		'description'	=> 'UNINETT',
		'searchbase'	=> 'cn=internal,cn=people,dc=uninett,dc=no',
		'hostname'	=> 'ldap://ldap.uninett.no',
		'attributes'	=> 'objectclass=*',
	)
	
);



?>
