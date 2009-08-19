<?php

$config = array(

	'sets' => array(

		'kalmar' => array(
			'cron'		=> array('hourly'),
			'sources'	=> array(
				array(
					'src' => 'https://kalmar.feide.no/simplesaml/module.php/aggregator/?id=kalmarcentral&mimetype=text/plain&exclude=norway',
					'validateFingerprint' => '591d4b4670463eeda91fcc816dc0af2a092aa801',
					'template' => array(
						'tags'	=> array('kalmar'),
						'authproc' => array(
							51 => array('class' => 'core:AttributeMap', 'oid2name'),
						),
					),
				),
			),
			'expireAfter' 		=> 60*60*24*4, // Maximum 4 days cache time.
			'outputDir' 	=> 'metadata/metadata-kalmar-consuming/',

			/*
			 * Which output format the metadata should be saved as.
			 * Can be 'flatfile' or 'serialize'. 'flatfile' is the default.
			 */
			'outputFormat' => 'flatfile',
		),
	),
);



