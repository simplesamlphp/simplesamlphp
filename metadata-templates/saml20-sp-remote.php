<?php
/* 
 * SAML 2.0 Meta data for simpleSAMLphp
 *
 * The SAML 2.0 SP Remote config is used by the SAML 2.0 IdP to identify trusted SAML 2.0 SPs.
 *
 *	Required parameters:
 * 
 *		assertionConsumerServiceURL
 *		spNameQualifier
 *		NameIDFormat
 *		simplesaml.attributes (Will you send an attributestatement [true/false])
 *
 *	Optional parameters:
 *
 *		ForceAuthn (default: "false")
 *		simplesaml.nameidattribute (only needed when you are using NameID format email.
 *
 */

$metadata = array( 

	'dev.andreas.feide.no' => array(
 		'assertionConsumerServiceURL'	=>	'http://dev.andreas.feide.no/saml2/sp/AssertionConsumerService.php', 
		'spNameQualifier' 				=>	'dev.andreas.feide.no',
		'ForceAuthn'					=>	'false',
		'NameIDFormat'					=>	'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
		'simplesaml.attributes'			=>	true
	),
	
	/*
	 * This example shows an example config that works with Google Apps for education.
	 * What is important is that you have an attribute in your IdP that maps to the local part of the email address
	 * at Google Apps. In example, if your google account is foo.com, and you have a user that has an email john@foo.com, then you
	 * must set the simplesaml.nameidattribute to be the name of an attribute that for this user has the value of 'john'.
	 */
	'google.com' => array(
 		'assertionConsumerServiceURL'	=>	'https://www.google.com/a/foo.com/acs', 
		'spNameQualifier' 				=>	'google.com',
		'ForceAuthn'					=>	'false',
		'NameIDFormat'					=>	'urn:oasis:names:tc:SAML:2.0:nameid-format:email',
		'simplesaml.nameidattribute'	=>	'uid',
		'simplesaml.attributes'			=>	false
	),
	
	"feide2.erlang.no" => array(
 		"assertionConsumerServiceURL"	=>	"https://feide2.erlang.no/saml2/sp/AssertionConsumerService.php", 
		"spNameQualifier" 				=>	"feide2.erlang.no",
		"ForceAuthn"					=>	"false",
		"NameIDFormat"					=>	"urn:oasis:names:tc:SAML:2.0:nameid-format:transient",
		'simplesaml.nameidattribute'	=>	'uid',
		'simplesaml.attributes'			=>	true
	),
		
	"feide3.erlang.no" => array(
 		"assertionConsumerServiceURL"	=>	"https://feide3.erlang.no/saml2/sp/AssertionConsumerService.php", //
		"spNameQualifier" 				=>	"feide3.erlang.no",
		"ForceAuthn"					=>	"false",
		"NameIDFormat"					=>	"urn:oasis:names:tc:SAML:2.0:nameid-format:transient",
		'simplesaml.attributes'			=>	true
	),
	
	"skjak.uninett.no" => array(
 		"assertionConsumerServiceURL"	=>	"https://skjak.uninett.no/Shibboleth.sso/SAML2/POST", //
		"spNameQualifier" 				=>	"skjak.uninett.no",
		"ForceAuthn"					=>	"false",
		"NameIDFormat"					=>	"urn:oasis:names:tc:SAML:2.0:nameid-format:transient",
		'simplesaml.attributes'			=>	true
		),
	"skjak.uninett.no" => array(
// 		"assertionConsumerServiceURL"	=>	"https://skjak2.uninett.no:443/fam/Consumer/metaAlias/sp_meta_alias", //
 		"assertionConsumerServiceURL"	=>	"https://skjak.uninett.no/Shibboleth.sso/SAML2/POST", //
		"spNameQualifier" 				=>	"skjak.uninett.no",
		"ForceAuthn"					=>	"false",
		"NameIDFormat"					=>	"urn:oasis:names:tc:SAML:2.0:nameid-format:transient",
		'simplesaml.attributes'			=>	true
		)
		
		

);


?>
