<?php

$config = array(

	'example-sql' => array(
		'sqlauth:SQL',
		'dsn' => 'pgsql:host=sql.example.org;port=5432;dbname=simplesaml',
		'username' => 'simplesaml',
		'password' => 'secretpassword',
		'query' => 'SELECT "username", "name", "email" FROM "users" WHERE "username" = :username AND "password" = :password',
	),

	'example-static' => array(
		'exampleauth:Static',
		'uid' => 'testuser',
		'eduPersonAffiliation' => array('member', 'employee'),
		'cn' => array('Test User'),
	),
	
	// Requires you to enable the OpenID module.
	'openid' => array(
		'openid:OpenIDConsumer',
	),

	'example-userpass' => array(
		'exampleauth:UserPass',
		'student:studentpass' => array(
			'uid' => 'student',
			'eduPersonAffiliation' => array('member', 'student'),
		),
		'employee:employeepass' => array(
			'uid' => 'employee',
			'eduPersonAffiliation' => array('member', 'employee'),
		),
	),

);

?>