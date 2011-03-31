<?php

/**
 * This is the configuration file for the Auth MemCookie example.
 */

$config = array(

	'insert' => array(
		'https://twitter.com' => array(
			'icon' => 'twitter.png',
		//	'descr' => 'Login with your Twitter account',
			'title' => 'Twitter',
			'weight' => -1,
			'auth' => 'twitter',
			'country' => 'XX',
			'distance' => null,
		// 	'keywords' => array(
		// 		'Organizations' => array('Twitter'),
		// 	),
		),
	),


	'exclude' => array(
		'https://testidp.wayf.dk',
		'https://betawayf.wayf.dk',
	),
	
	'overrides' => array(
		'https://twitter.com' => array(
			'icon' => 'twitter.png',
			'weight' => -1,
			'auth' => 'twitter',
			'country' => '_all_',
		),
		'https://idp.feide.no' => array(
			'icon' => 'feide2.png',
			'descr' => 'Brukere i norske utdanningsinstitusjoner [beta]',
			'weight' => -10,
			'keywords' => array(
				'Organizations' => array('UNINETT', 'Feide', 'UiO', 'NTNU', 'UiB', 'UiT'),
				'Places' => array('Norway', 'Norge', 'Trondheim', 'Oslo', 'Bergen', 'Tromsø', 'Stavanger'),
			),
		),
		'https://idp-test.feide.no' => array(
			'icon' => 'feide2.png',
			'weight' => 10,
			'country' => 'XX',
		),
		'https://betawayf.wayf.dk' => array(
			'icon' => 'wayf2.png',
			'weight' => 10,
			'country' => 'XX',
		),
		
		'https://www.rediris.es/sir/redirisidp' => array(
			'icon' => 'rediris.png',
			'weight' => -1,
		),
		'https://idp.nordu.net/idp/shibboleth' => array(
			'icon' => 'nordunet.png',
			'weight' => -3,	
		),
		'https://openidp.feide.no' => array(
			'icon' => 'openidp.png',
			'title' => 'Feide OpenIdP',
			'descr' => 'If you do not have an institutional account, register here.',
			'country' => '_all_',
			'weight' => -5,
			'keywords' => array(
				'Keyword' => array('Guest', 'OpenIdP', 'Orphanage', 'Homeless', 'Create Account', 'Register'),
			),
		),
		'https://idp.protectnetwork.org/protectnetwork-idp' => array(
			'icon' => 'protectnetwork.png',
			'title' => 'Protect Network',
			'descr' => 'If you do not have an institutional account, register here.',
			'country' => '_all_',
			'weight' => -6,
			'geo' => null,
			'keywords' => array(
				'Keyword' => array('Guest', 'OpenIdP', 'Orphanage', 'Homeless', 'Create Account', 'Register'),
			),
		),
		
		'https://login.terena.org/idp/saml2/idp/metadata.php' => array(
			'icon' => 'terena2.png',
			'descr' => 'Terena offices Netherlands',
			'country' => 'NL',
			'weight' => -3,
		),
		'https://idp.csc.fi/idp/shibboleth' => array(
			'icon' => 'csc.png',
			'weight' => -2,
		),
		'https://wayf.wayf.dk' => array(
			'weight' => -3,
			'icon' => 'wayf2.png',
		),
	
		'https://www.rediris.es/sir/umaidp' => array(
			'weight' => -2,
			'icon' => 'uma.png',
		),
		'https://idp.uma.es/simplesaml/saml2/idp/metadata.php' => array(
			'weight' => -2,
			'icon' => 'uma.png',
		),
		'https://idpguest.fccn.pt/idp/shibboleth' => array(
			'weight' => -2,
			'icon' => 'fccn.png',
		),
		'https://whoami.cesnet.cz/idp/shibboleth' => array(
			'weight' => -2,
			'icon' => 'cesnet.png',
			'country' => 'CZ',
		),
		'https://login.ntua.gr/idp/shibboleth' => array(
			'weight' => -2,
			'icon' => 'ntua.png',
			'country' => 'GR',	
		),
		'https://idp.umu.se/saml2/idp/metadata.php' => array(
			'title' => 'Umeå Universitet',
			'weight' => -3,
			'icon' => 'umeaa.png',
	#		'country' => 'sweden',	
		),
		'SURFnet%20BV' => array(
			'icon' => 'surfnet.png',
			'weight' => -2
		),
		
		'gidp.geant.net' => array(
			'country' => '_all_',
			'icon' => 'geant.png',
		),
		'urn:mace:cru.fr:federation:sac' => array(
			'icon' => 'cru.png',
		),
		'https://stc-test16.cis.brown.edu/idp/shibboleth' => array(
			'icon' => 'brown.png',
			'title' => 'Brown University',
		),
				
		'https://www.rediris.es/sir/uvigoidp' => array(
			'geo' => array('lat' => 42.169185 , 'lon' => -8.683609)
		),
		
		'https://www.rediris.es/sir/uahidp' => array(
			'geo' => array('lat' => 40.510597 , 'lon' => -3.343858)
		),
		
		'https://www.rediris.es/sir/bcblidp' => array(
			'geo' => array('lat' => 43.294269, 'lon' => -1.9861)
		),
		
		'https://www.rediris.es/sir/ullidp' => array(
			'geo' => array('lat' => 28.4816, 'lon' => -16.3168)
		),
		
		'https://www.rediris.es/sir/ubuidp' => array(
			'geo' => array('lat' => 42.340197, 'lon' => -3.7277)
		),
		
		'https://www.rediris.es/sir/upctidp' => array(
			'geo' => array('lat' => 37.6018, 'lon' => -0.9794)
		),
		
		'https://www.rediris.es/sir/umidp' => array(
			'geo' => array('lat' => 38.022, 'lon' => -1.174)
		),
		
		'https://www.rediris.es/sir/uclmidp' => array(
			'geo' => array('lat' => 38.986096 , 'lon' => -3.927262)
		),
		
		'https://www.rediris.es/sir/upmidp' => array(
			'geo' => array('lat' => 40.449, 'lon' => 3.7191)
		),
		
		'https://www.rediris.es/sir/boeidp' => array(
			'geo' => array('lat' => 40.4867, 'lon' => -3.6604)
		),
		
		'https://www.rediris.es/sir/usidp' => array(
			'geo' => array('lat' => 37.382, 'lon' => -5.99158)
		),
		
		'https://www.rediris.es/sir/usalidp'=> array(
			'geo' => array('lat' => 40.790887, 'lon' => -5.539856)
		 ),
		
		'https://www.rediris.es/sir/deustoidp' => array(
			'geo' => array('lat' => 43.270891, 'lon' => -2.938769)
		),
		
		'https://www.rediris.es/sir/uaidp' => array(
			'geo' => array('lat' => 38.385975, 'lon' => -0.514267)
		),
		
		'https://www.rediris.es/sir/ucoidp' => array(
			'geo' => array('lat' => 37.9157009, 'lon' => -4.721796)
		),
		
		'https://www.rediris.es/sir/dipcidp' => array(
			'geo' => array('lat' => 43.1819, 'lon' => -2.0039)
		),
		
		'https://www.rediris.es/sir/uaxidp' => array(
			'geo' => array('lat' => 40.452, 'lon' => -3.984)
		),
		
		'https://www.rediris.es/sir/iacidp' => array(
			'geo' => array('lat' => 28.47468, 'lon' => -16.308303)
		),
		
		'https://www.rediris.es/sir/redirisidp' => array(
			'geo' => array('lat' =>  40.447636, 'lon' =>  -3.694236)
		),
		
		
	),
	
	
	

);