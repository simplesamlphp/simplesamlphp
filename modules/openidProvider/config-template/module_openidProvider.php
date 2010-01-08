<?php
/**
 * Configuration file for the OpenID provider.
 */

$config = array(

	/* The authentication source that should be used. */
	'auth' => 'default-sp',

	/* The name of the attribute which contains the username of the user. */
	'username_attribute' => 'uid',

	/* The directory where OpenID data is stored. */
	'filestore' => '/var/lib/simplesamlphp-openid-provider',

);
