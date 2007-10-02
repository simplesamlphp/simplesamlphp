<?php
/* 
 * SAML 2.0 Meta data for simpleSAMLphp
 *
 * The SAML 2.0 SP Remote config is used by the SAML 2.0 IdP to identify trusted SAML 2.0 SPs.
 *
 *	Required parameters:
 * 
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

	/*
	 * Example simpleSAMLphp SAML 2.0 SP
	 */
	'saml2sp.example.org' => array(
 		'AssertionConsumerService'		=>	'https://saml2sp.example.org/simplesaml/saml2/sp/AssertionConsumerService.php', 
 		'SingleLogoutService'			=>	'https://saml2sp.example.org/simplesaml/saml2/sp/SingleLogoutService.php',
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
 		'AssertionConsumerService'		=>	'https://www.google.com/a/g.feide.no/acs', 
 		'SingleLogoutService'			=> 	'',
		'spNameQualifier' 				=>	'google.com',
		'ForceAuthn'					=>	'false',
		'NameIDFormat'					=>	'urn:oasis:names:tc:SAML:2.0:nameid-format:email',
		'simplesaml.nameidattribute'	=>	'uid',
		'simplesaml.attributes'			=>	false
	)
	
		

);


?>
