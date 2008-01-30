<?php
/* 
 * Shibboleth 1.3 Meta data for simpleSAMLphp
 *
 *
 *
 *
 */


$metadata = array(

	'https://sp.shiblab.feide.no'	=> array(
		'AssertionConsumerService'				=>	'http://sp.shiblab.feide.no/Shibboleth.sso/SAML/POST',
		'audience'			=>	'urn:mace:feide:shiblab',
		'base64attributes'			=> false
	),
	'urn:geant:edugain:component:be:switchaai-test:central' => array(
		'AssertionConsumerService'				=>	'https://edugain-login.switch.ch/ShiBE-R/WebSSOResponseListener',
		'audience'			=>	'urn:geant:edugain:component:be:switchaai-test:central',
		'base64attributes'			=> false
	),
	'urn:geant:edugain:component:be:rediris:rediris.es' => array(
		'AssertionConsumerService'				=>	'http://serrano.rediris.es:8080/PAPIWebSSOResponseListener/request',
		'audience'			=>	'urn:geant:edugain:component:be:rediris:rediris.es',
		'base64attributes'			=> false
	),
	'https://skjak.uninett.no/shibboleth/target' => array(
		'AssertionConsumerService'				=>	'https://skjak.uninett.no/Shibboleth.shire',
		'audience'			=>	'https://skjak.uninett.no/shibboleth/target',
		'base64attributes'			=> false
	)

);

?>