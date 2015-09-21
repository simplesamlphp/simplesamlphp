<?php

/**
 * This is the configuration file for the DiscoJuice.
 */

$config = array(

	// A human readable name describing the Service Provider
	'name' => 'Service',
	
	/* A set of prepared metadata feeds from discojuice.org
	 * You may visit
	 * 		https://static.discojuice.org/feeds/
	 *
	 * to review the available feed identifiers.
	 * You may choose to not use any of the provider feed, by setting this to an 
	 * empty array: array()
	 */
	'feeds' => array('edugain'),
	
	/*
	 * You may provide additional feeds
	 */
	'additionalFeeds' => array(
	),
	
	/*
	 * If you set this value to true, the module will contact discojuice.org to read and write cookies.
	 * If you enable this, you will also need to get your host accepted in the access control list of 
	 * discojuice.org
	 *
	 * The response URL of your service, similar to:
	 *
	 *		https://sp.example.org/simplesaml/module.php/discojuice/response.html	
	 *
	 * will need to be registered at discojuice.org. If your response URL is already registered in the metadata
	 * of one of the federation feeds at discojuice.org, you should already have access.
	 */
	'enableCentralStorage' => false,
	
);