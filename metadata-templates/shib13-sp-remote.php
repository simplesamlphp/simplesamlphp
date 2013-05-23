<?php
/**
 * SAML 1.1 remote SP metadata for simpleSAMLphp.
 *
 * See: https://rnd.feide.no/content/sp-remote-metadata-reference
 */

/*
 * This is just an example:
 */
$metadata['https://sp.shiblab.feide.no'] = array(
	'AssertionConsumerService' => 'http://sp.shiblab.feide.no/Shibboleth.sso/SAML/POST',
	'audience'                 => 'urn:mace:feide:shiblab',
	'base64attributes'         => FALSE,
);

