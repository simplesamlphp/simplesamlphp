<?php
/* 
 * The configuration of simpleSAMLphp
 * 
 * 
 */

$ldapmulti = array (

	'feide.no' => array(
		'description'	=> 'Feide',
		'dnpattern'		=> 'uid=%username%,dc=feide,dc=no,ou=feide,dc=uninett,dc=no',
		'hostname'		=> 'ldap.uninett.no',
		'attributes'	=> 'objectclass=*',
	),
	'uninett.no' => array(
		'description'	=> 'UNINETT',
		'dnpattern'		=> 'uid=%username%,ou=people,dc=uninett,dc=no',
		'hostname'		=> 'ldap.uninett.no',
		'attributes'	=> 'objectclass=*',
	)
	
);



?>