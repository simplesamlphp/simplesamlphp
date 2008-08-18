<?php

$config = array(

	'example-static' => array(
		'exampleauth:Static',
		'uid' => 'testuser',
		'eduPersonAffiliation' => array('member', 'employee'),
		'cn' => array('Test User'),
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