<?php

$config = array( 

	'urn:federation:localhost:adfs' => array(

		'prp' => 'https://localhost/adfs/ls/',
		'simplesaml.nameidattribute' => 'uid',
		'authproc' => array(
			50 => array(
				'class' => 'core:AttributeLimit',
				'cn', 'mail', 'uid'
			),
		),
	),

);

?>
