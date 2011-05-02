<?php

/**
 * This is a DiscoJuice configuration file.
 * 
 * This configuration is used for the standalone DiscoJuice instance, that will become available at:
 *  /simplesaml/module.php/discojuice/central.php
 *
 * For details about the configuration option visit the DiscoJuice documentation:
 *  http://discojuice.simplesamlphp.org/docs
 */

$config = array(

	// Which hostnames should be allowed to read user selections on entities chosen using DiscoJuice.
	// You need to enable those hostnames that is configured to read data using the DiscoJuiceReadWrite protocol.
	// 		http://discojuice.simplesamlphp.org/docs/1.0/discoreadwrite
	'acl' => array(
		'simplesamlphp.org', 'example.org',
	),
	
	// DiscoJuice opitions for the central DiscoJuice page.
	'discojuice.options' => array(
		"title"=> 'Sign in to <strong>this service</strong>',
		"subtitle"=> "Select your Provider",
		
		"always"=> true,
		"overlay"=> true,
		"cookie"=> true,
		"type"=> false,
		"country"=> true,
		"location"=> true,
		"debug.weight" => false,
	),

);