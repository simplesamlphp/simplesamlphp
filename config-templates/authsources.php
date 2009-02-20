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
		'uid' => array('testuser'),
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
			'uid' => array('test'),
			'eduPersonAffiliation' => array('member', 'student'),
		),
		'employee:employeepass' => array(
			'uid' => array('employee'),
			'eduPersonAffiliation' => array('member', 'employee'),
		),
	),
	
	'yubikey' => array(
		'authYubiKey:YubiKey',
 		'id' => '000',
// 		'key' => '012345678',
	),
	
	'openid' => array(
		'openid:OpenIDConsumer',
	),

	'feide' => array(
		'feide:Feide',
	),
	
	'papi' => array(
		'authpapi:PAPI',
	),
	
	'saml2' => array(
		'saml2:SP',
	),
	
	'facebook' => array(
		'authfacebook:Facebook',
		'api_key' => 'xxxxxxxxxxxxxxxx',
		'secret' => 'xxxxxxxxxxxxxxxx',
	),
        
);

?>