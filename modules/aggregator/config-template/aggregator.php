<?php

/* Configuration for the aggregator module. */
$config = array(

	/* List of aggregators. */
	'aggragators' => array(
		'example' => array(
			array('type' => 'flatfile'),  /* Metadata from metadata-directory. */
			array('type' => 'xml', 'url' => 'https://idp.example.org/Metadata'),
			array('type' => 'xml', 'file' => 'static-metadata.xml'),
		),
	),

);

?>