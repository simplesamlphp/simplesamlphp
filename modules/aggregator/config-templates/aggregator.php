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

	
	'maxCache' 		=> 60*60*24, // 24 hour cache time
	'maxDuration' 	=> 60*60*24*5, // Maximum 5 days duration on ValidUntil.

	// If base64 encoded for entity is already cached in the entity, should we
	// reconstruct the XML or re-use.
	'reconstruct' => TRUE,

	/* Whether metadata should be signed. */
	'sign.enable' => FALSE,

	/* Private key which should be used when signing the metadata. */
	'sign.privatekey' => 'server.key',

	/* Password to decrypt private key, or NULL if the private key is unencrypted. */
	'sign.privatekey_pass' => NULL,

	/* Certificate which should be included in the signature. Should correspond to the private key. */
	'sign.certificate' => 'server.crt',

);

?>