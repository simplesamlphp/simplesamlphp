<?php
/* 
 * SAML 2.0 Meta data for simpleSAMLphp
 *
 * The SAML 2.0 SP Remote config is used by the SAML 2.0 IdP to identify trusted SAML 2.0 SPs.
 *
 * Required parameters:
 *   - AssertionConsumerService
 *   - SingleLogoutService
 *
 * Optional parameters:
 *
 *   - simplesaml.attributes (Will you send an attributestatement [true/false])
 *   - NameIDFormat
 *   - ForceAuthn (default: "false")
 *   - simplesaml.nameidattribute (only needed when you are using NameID format email or persistent).
 *
 *   - 'base64attributes'	=>	false,
 *   - 'simplesaml.attributes'	=>	true,
 *   - 'attributemap'		=>	'test',
 *   - 'attributes'			=>	array('mail'),
 *   - 'userid.attribute'
 *
 * Request signing
 *    When redirect.sign is true the certificate of the IDP
 *    will be used to sign all messages sent with the HTTPRedirect binding.
 *    The certificate from the IDP must be installed in the cert directory 
 *    before signing can be done.  
 *
 *   'redirect.sign' => false,
 *
 */

$metadata = array( 

	/*
	 * Example simpleSAMLphp SAML 2.0 SP
	 */
	'https://saml2sp.example.org' => array(
 		'AssertionConsumerService' => 'https://saml2sp.example.org/simplesaml/saml2/sp/AssertionConsumerService.php', 
 		'SingleLogoutService'      => 'https://saml2sp.example.org/simplesaml/saml2/sp/SingleLogoutService.php'
	),
	
	/*
	 * This example shows an example config that works with Google Apps for education.
	 * What is important is that you have an attribute in your IdP that maps to the local part of the email address
	 * at Google Apps. In example, if your google account is foo.com, and you have a user that has an email john@foo.com, then you
	 * must set the simplesaml.nameidattribute to be the name of an attribute that for this user has the value of 'john'.
	 */
	'google.com' => array(
 		'AssertionConsumerService'		=>	'https://www.google.com/a/g.feide.no/acs',
		'NameIDFormat'					=>	'urn:oasis:names:tc:SAML:2.0:nameid-format:email',
		'simplesaml.nameidattribute'	=>	'uid',
		'simplesaml.attributes'			=>	false
	)
	
		

);


?>
