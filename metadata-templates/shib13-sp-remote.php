<?php
/* 
 * SAML 2.0 Meta data for simpleSAMLphp
 *
 */


$metadata = array(
	'https://sp.shiblab.feide.no'	=> array(
		'shire'				=>	'http://sp.shiblab.feide.no/Shibboleth.sso/SAML/POST',
		'spnamequalifier'	=>	'urn:feide.no',
		'audience'			=>	'urn:mace:feide:shiblab'
	),
	'urn:geant:edugain:component:be:switchaai-test:central' => array(
		'shire'				=>	'https://edugain-login.switch.ch/ShiBE-R/WebSSOResponseListener',
		'spnamequalifier'	=>	'urn:geant:edugain:component:be:rediris:rediris.es',
		'audience'			=>	'urn:geant:edugain:component:be:switchaai-test:central'
	),
	'urn:geant:edugain:component:be:rediris:rediris.es' => array(
		'shire'				=>	'http://serrano.rediris.es:8080/PAPIWebSSOResponseListener/request',
		'spnamequalifier'	=>	'urn:geant:edugain:component:be:rediris:rediris.es',
		'audience'			=>	'urn:geant:edugain:component:be:rediris:rediris.es'
	),
	'https://skjak.uninett.no/shibboleth/target' => array(
		'shire'				=>	'https://skjak.uninett.no/Shibboleth.shire',
		'spnamequalifier'	=>	'https://skjak.uninett.no/shibboleth/target',
		'audience'			=>	'https://skjak.uninett.no/shibboleth/target'
	)

);

?>