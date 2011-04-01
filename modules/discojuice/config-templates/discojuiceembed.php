<?php

/**
 * This is the configuration file for the Auth MemCookie example.
 */

$config = array(
	'target' => 'a.signin',
	'discojuice.options' => array(
		"title"=> 'Sign in to <strong>Foodle</strong>',
		"subtitle"=> "Select your Provider",
		
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

	console.log(e);

	var auth = e.auth || null;
	var returnto = window.location.href || 'https://foodl.org';
	switch(auth) {
		
		case 'twitter':
			window.location = 'https://foodl.org/simplesaml/module.php/core/as_login.php?AuthId=twitter&ReturnTo=' + escape(returnto);
		break;
	
	
		case 'saml':
		default:
			window.location = 'https://foodl.org/simplesaml/module.php/core/as_login.php?AuthId=saml&ReturnTo=' + escape(returnto) + '&saml:idp=' + escape(e.entityID);
		break;							
			
	}
}

	"
	

);