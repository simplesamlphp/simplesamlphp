<?php
/* 
 * The configuration of simpleSAMLphp statistics package
 */

$config = array (

	'statdir' => '/tmp/stats/',
	'inputfile' => '/var/log/simplesamlphp.stat',
	'offset' => 60*60*2, // Two hours offset to match epoch and norwegian winter time.
	
	'datestart' => 1,
	'datelength' => 15,
	'offsetspan' => 21,
	
	/*
	 * Do you want to generate statistics using the cron module? If so, specify which cron tag to use.
	 * Examples: daily, weekly
	 * To not run statistics in cron, set value to 
	 *     'cron_tag' => NULL,
	 */
	'cron_tag' => 'daily',
	
	'statrules' => array(
		'sso_hoursday' => array(
			'name' 		=> 'Numer of SP logins (per 15 minutes for one day)',
			'descr'		=> 'The number of Service Provider logins put into slots of one hour.',
		
			'action' 	=> 'saml20-sp-SSO',
			'col'		=> 7,				// Service Provider EntityID
			'slot'		=> 60*15,			// Slots of one hour
			'fileslot'	=> 60*60*24,		// 7 days of data in each file
			'axislabelint' => 6*4,			// Number of slots per label. 24 is one each day
			
			'dateformat-period'	=> 'j. M', 			//  4. Mars
			'dateformat-intra'	=> 'j. M H:i', 		//  4. Mars 12:30
#			'dateformat-intra'	=> 'j. H:i', 		//  4. Mars 12:30
		),
		'sso_day80' => array(
			'name' 		=> 'Number of SP logins (per day spanning 80 days)',
			'descr'		=> 'The number of Service Provider logins put into slots of one hour.',
		
			'action' 	=> 'saml20-sp-SSO',
			'col'		=> 5,				// Service Provider EntityID
			'slot'		=> 60*60*24,			// Slots of six hour
			'fileslot'	=> 60*60*24*80,		// 7 days of data in each file
			'axislabelint' => 7,			// Number of slots per label. 24 is one each day
			
			'dateformat-period'	=> 'j. M', 			//  4. Mars
			'dateformat-intra'	=> 'j. M', # H:i', 		//  4. Mars 12:30
#			'dateformat-intra'	=> 'j. H:i', 		//  4. Mars 12:30
		),
		'sso_hoursweek' => array(
			'name' 		=> 'Numer of SP logins (per hour spanning 7 days)',
			'descr'		=> 'The number of Service Provider logins put into slots of one hour.',
		
			'action' 	=> 'saml20-sp-SSO',
			'col'		=> 5,				// Service Provider EntityID
			'slot'		=> 60*60,			// Slots of one hour
			'fileslot'	=> 60*60*24*7,		// 7 days of data in each file
			'axislabelint' => 24,			// Number of slots per label. 24 is one each day
			
			'dateformat-period'	=> 'j. M', 			//  4. Mars
			'dateformat-intra'	=> 'j. M H:i', 		//  4. Mars 12:30
#			'dateformat-intra'	=> 'j. H:i', 		//  4. Mars 12:30
		),
		'sso_days' => array(
			'name' 		=> 'Numer of SP logins (per day)',
			'descr'		=> 'The number of Service Provider logins put into slots of one day.',
		
			'action' 	=> 'saml20-sp-SSO',
			'col'		=> 5,				// Service Provider EntityID
			'slot'		=> 60*60*24,			// Slots of one day
			'fileslot'	=> 60*60*24*30,		// 30 days of data in each file
			'axislabelint' => 7,			// Number of slots per label. 24 is one each day
			
			'dateformat-period'	=> 'j. M Y H:i', 			//  4. Mars
			'dateformat-intra'	=> 'j. M', 		//  4. Mars 12:30
#			'dateformat-intra'	=> 'j. H:i', 		//  4. Mars 12:30
		),
		
		
	),

);

?>