<?php

/**
 * This is the configuration file for the Auth MemCookie example.
 */

$config = array(

	'acl' => array(
		'simplesamlphp.org', 'foodl.org',
	),
	
	'discojuice.options' => array(
		"title"=> 'Sign in to <strong>this service</strong>',
		"subtitle"=> "Select your Provider",
		
		"always"=> true,
		"overlay"=> true,
		"cookie"=> true,
		"type"=> false,
		"country"=> true,
		"location"=> true,
		"debug.weight" => true,
	),
	
	

);