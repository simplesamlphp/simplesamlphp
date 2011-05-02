<?php

/**
 * This is a DiscoJuice configuration file.
 * 
 * This configuration is used for the embedded DiscoJuice instance.
 *
 * For details about the configuration option visit the DiscoJuice documentation:
 *  http://discojuice.simplesamlphp.org/docs
 */

$config = array(
	'target' => 'a.signin',
	'discojuice.options' => array(
		"title"=> 'Sign in to <strong>Foodle</strong>',
		"subtitle"=> "Select your Provider",
		
		//	Want to override the inline help texts in DiscoJuice, uncomment the following section:
	
		/*
			'textSearch' => 'søk etter noe...',
			'textHelp' => 'Finner du ikke en innnloggsingstjener du kjenner?',
			'textHelpMore' => 'Let mer...',
 		*/

		// Where to fetch metadata from. DiscoJuiceJSON format..
		// You may provide a 'callback=?' querystring parameter in ordert to support JSONP.
		// By default this feed endpoint is automatically configured to be correct for the 
		// DiscoJuice simpleSAMLphp module (which has a built-in feed generator). 
		
		//	"metadata" => 'http://example.org/discojuicejson/index.php?callback=?',
		
		"always"=> false,
		"overlay"=> true,
		"cookie"=> true,
		"type"=> false,
		"country"=> true,
		"location"=> true,
		"debug.weight" => false,
	),
	
	"callback" => "
		
function(e) {

	// The auth parameter is indicating which authentication method is being used.
	var auth = e.auth || null;
	var returnto = window.location.href || 'https://example.org';

	window.location = 'https://foodl.org/simplesaml/module.php/core/as_login.php?AuthId=saml&ReturnTo=' + escape(returnto) + '&saml:idp=' + escape(e.entityID);

}

	"
	

);