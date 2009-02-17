<?php


$config = array(

	'store' 	=> array(
		'consent:Database', 
		'dsn' => 'pgsql:host=sql.uninett.no;dbname=andreas_consent',
		'username' => 'simplesaml',
		'password' => 'xxxx',
	),

	'auth' => 'example-static',
	'userid', 'eduPersonPrincipalName',

);
