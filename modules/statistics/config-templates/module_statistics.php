<?php
/* 
 * The configuration of simpleSAMLphp statistics package
 */

$config = array (

	'statdir' => '/tmp/stats/',
	'inputfile' => '/var/log/simplesamlphp.stat',
	'offset' => 60*60*2 + 60*60*24*3, // Two hours offset to match epoch and norwegian winter time.
	
	'datestart' => 1,
	'datelength' => 15,
	'offsetspan' => 21,
	
	// Dimensions on graph from Google Charts in pixels...
	'dimension.x' => 800,
	'dimension.y' => 350,
	
	/*
	 * Do you want to generate statistics using the cron module? If so, specify which cron tag to use.
	 * Examples: daily, weekly
	 * To not run statistics in cron, set value to 
	 *     'cron_tag' => NULL,
	 */
	'cron_tag' => 'daily',
	
	'statrules' => array(
		'sso_hoursday' => array(
			'name' 		=> 'SP logins 15 min slots through one day',
			'descr'		=> 'The number of logins at a Service Provider divided into slots of one hour. Each file contains data for one day (24 hours)',
		
			'action' 	=> 'saml20-sp-SSO',
			'col'		=> 5,				// Service Provider EntityID
			'slot'		=> 60*15,			// Slots of 15 minutes
			'fileslot'	=> 60*60*24,		// One day (24 hours) file slots
			'axislabelint' => 6*4,			// Number of slots per label. 4 per hour *6 = 6 hours 
			
			'dateformat-period'	=> 'j. M', 			//  4. Mars
			'dateformat-intra'	=> 'j. M H:i', 		//  4. Mars 12:30
		),
		'sso_day80' => array(
			'name' 		=> 'SP logins per day slots through 80 days',
			'descr'		=> 'The number of logins at a Service Provider divided into slots of one day. Each file contains data for 80 days',
		
			'action' 	=> 'saml20-sp-SSO',
			'col'		=> 5,				// Service Provider EntityID
			'slot'		=> 60*60*24,		// Slots of 1 day (24 hours)
			'fileslot'	=> 60*60*24*80,		// 80 days of data in each file
			'axislabelint' => 7,			// Number of slots per label. 7 days => 1 week
			
			'dateformat-period'	=> 'j. M', 		//  4. Mars
			'dateformat-intra'	=> 'j. M', 		//  4. Mars
		),
		'sso_hoursweek' => array(
			'name' 		=> 'SP logins per hour through one week',
			'descr'		=> 'The number of logins at a Service Provider divided into slots of one hour. Each file contains data for one week.',
		
			'action' 	=> 'saml20-sp-SSO',
			'col'		=> 5,				// Service Provider EntityID
			'slot'		=> 60*60,			// Slots of one hour
			'fileslot'	=> 60*60*24*7,		// 7 days of data in each file
			'axislabelint' => 24,			// Number of slots per label. 24 is one each day
			
			'dateformat-period'	=> 'j. M', 			//  4. Mars
			'dateformat-intra'	=> 'j. M H:i', 		//  4. Mars 12:30
		),
		'sso_days' => array(
			'name' 		=> 'SP logins per day through 30 days',
			'descr'		=> 'The number of logins at a Service Provider divided into slots of one day. Each file contains data for 30 days.',
		
			'action' 	=> 'saml20-sp-SSO',
			'col'		=> 5,				// Service Provider EntityID
			'slot'		=> 60*60*24,		// Slots of one day
			'fileslot'	=> 60*60*24*30,		// 30 days of data in each file
			'axislabelint' => 7,			// Number of slots per label. 7 days => 1 week
			
			'dateformat-period'	=> 'j. M Y H:i', 	//  4. Mars 12:30
			'dateformat-intra'	=> 'j. M', 			//  4. Mars
		),
		
		
	),

);

?>