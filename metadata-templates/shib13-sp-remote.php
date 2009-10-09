<?php
/**
 * SAML 1.1 remote SP metadata for simpleSAMLphp.
 *
 * See: https://rnd.feide.no/content/sp-remote-metadata-reference
 */

$metadata['https://sp.shiblab.feide.no'] = array(
	'AssertionConsumerService' => 'http://sp.shiblab.feide.no/Shibboleth.sso/SAML/POST',
	'audience'                 => 'urn:mace:feide:shiblab',
	'base64attributes'         => FALSE,
);

$metadata['urn:geant:edugain:component:be:switchaai-test:central'] = array(
	'AssertionConsumerService' => 'https://edugain-login.switch.ch/ShiBE-R/WebSSOResponseListener',
	'audience'                 => 'urn:geant:edugain:component:be:switchaai-test:central',
	'base64attributes'         => FALSE,
);

$metadata['urn:geant:edugain:component:be:rediris:rediris.es'] = array(
	'AssertionConsumerService' => 'http://serrano.rediris.es:8080/PAPIWebSSOResponseListener/request',
	'audience'                 => 'urn:geant:edugain:component:be:rediris:rediris.es',
	'base64attributes'         => FALSE,
);
