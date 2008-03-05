<?php

/*
 * This is the configuration file for the test script which can be found at
 * bin/test.php.
 *
 */

/* Add a test towards the default IdP using the simpleSAMLphp login handler. */
$tests[] = array(

	/* The full url to the admin/test.php page on the SP. */
	'url' => 'https://example.org/simplesaml/admin/test.php',

	/* The username and password which should be used for logging in. ('simplesaml' login type) */
	'username' => 'username',
	'password' => 'secretpassword',

	/* The type of login page we expect. */
	'logintype' => 'simplesaml',

	/* Expected attributes in the result. */
	'attributes' => array(
		'uid' => 'test',
		),
	);


/* Add a test towards the default IdP using the shib13 protocol. */
$tests[] = array(

	/* The full url to the admin/test.php page on the SP. */
	'url' => 'https://example.org/simplesaml/admin/test.php',

	/* The protocol we are going to test. */
	'protocol' => 'shib13',

	/* The username and password which should be used for logging in. ('simplesaml' login type) */
	'username' => 'username',
	'password' => 'secretpassword',

	/* The type of login page we expect. */
	'logintype' => 'simplesaml',

	/* Expected attributes in the result. */
	'attributes' => array(
		'uid' => 'test',
		),
	);


/* Add a test towards the specified IdP using the FEIDE login handler. */
$tests[] = array(

	/* The full url to the admin/test.php page on the SP. */
	'url' => 'https://example.org/simplesaml/admin/test.php',

	/* The idp we should test. */
	'idp' => 'max.feide.no',

	/* The username, password and organization which should be used for logging in. ('feide' login type) */
	'username' => 'username',
	'password' => 'secretpassword',
	'organization' => 'feide.no',

	/* The type of login page we expect. */
	'logintype' => 'feide',

	/* Expected attributes in the result. */
	'attributes' => array(
		'eduPersonAffiliation' => array(
			'employee',
			'staff',
			'student',
			),
		),
	);

