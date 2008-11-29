<?php

$config = array(

	'kalmar' => array(
		'cron'		=> array('hourly'),
		'sources'	=> array(
			array(
				'src' => 'https://aitta.funet.fi/haka/haka_test_metadata_signed.xml',
				'certFingerprint' => '22:1D:EA:E3:2C:EB:A3:2D:78:72:B6:F4:E9:52:F6:23:31:5A:A5:3D',
				'template' => array(
					'tags'	=> array('kalmar'),
				),
			),
		),
		'maxCache' 		=> 60*60*24*4, // Maximum 4 days cache time.
		'maxDuration' 	=> 60*60*24*10, // Maximum 10 days duration on ValidUntil.
		'outputDir' 	=> 'metadata/metadata-kalmar-consuming/',
	),

);

?>