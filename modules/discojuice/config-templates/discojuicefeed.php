<?php

/**
 * This is a DiscoJuice configuration file.
 * 
 * This configuration is used for the DiscoJuiceJSON metadata feed, that will become available at:
 * 	/simplesaml/module.php/discojuice/feed.php
 * 
 * For details about the configuration option visit the DiscoJuice documentation:
 *  http://discojuice.simplesamlphp.org/docs
 */

$config = array(

	// Provides a list of IdPs that has already successfully logged in at least one user.
	// Will give these IdPs extra weight in the UI.	
	//	'idplistapi' => 'https://foodl.org/api/idplist',
	
	// Merge DiscoJuiceJSON with a additional sources.
	// 	'mergeEndpoints' => array(
	// 	),

	// Include a set of extra entities, that is not present in metadata (DiscoJuiceJSON)
	'insert' => array(
	),

	// Exclude a set of entity IDs to not show in the Discovery Service
	'exclude' => array(
	),
	
	// Allows you to override DiscoJuiceJSON metadata.
	// May be useful if an external party offers a DiscoJuiceJSON feed, and you would like to do some customization.
	'overrides' => array(
	),

);