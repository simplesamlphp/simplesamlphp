<?php
/* 
 * SAML 2.0 Meta data for simpleSAMLphp
 *
 * The SAML 2.0 IdP Remote config is used by the SAML 2.0 SP to identify itself.
 *
 */
 
$metadata = array( 
	"dev.andreas.feide.no" => array(
		'host'							=>	'dev.andreas.feide.no',
 		"assertionConsumerServiceURL"	=>	"http://dev.andreas.feide.no/saml2/sp/AssertionConsumerService.php", 
		"issuer"						=>	"dev.andreas.feide.no",
		"spNameQualifier" 				=>	"dev.andreas.feide.no",
		"ForceAuthn"					=>	"false",
		"NameIDFormat"					=>	"urn:oasis:names:tc:SAML:2.0:nameid-format:transient"
	),
	"feide2.erlang.no" => array(
		'host'							=>	'feide2.erlang.no',
 		"assertionConsumerServiceURL"	=>	"https://feide2.erlang.no/saml2/sp/AssertionConsumerService.php", 
		"issuer"						=>	"feide2.erlang.no",
		"spNameQualifier" 				=>	"feide2.erlang.no",
		"ForceAuthn"					=>	"false",
		"NameIDFormat"					=>	"urn:oasis:names:tc:SAML:2.0:nameid-format:transient"
	),
	"feide3.erlang.no" => array(
		'host'							=>	'feide3.erlang.no',
 		"assertionConsumerServiceURL"	=>	"https://feide3.erlang.no/saml2/sp/AssertionConsumerService.php", //
		"issuer"						=>	"feide3.erlang.no",
		"spNameQualifier" 				=>	"feide3.erlang.no",
		"ForceAuthn"					=>	"false",
		"NameIDFormat"					=>	"urn:oasis:names:tc:SAML:2.0:nameid-format:transient"
	)
);


?>
