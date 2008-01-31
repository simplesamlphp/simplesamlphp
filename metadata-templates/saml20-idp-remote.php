<?php
/* 
 * SAML 2.0 Meta data for simpleSAMLphp
 *
 * The SAML 2.0 IdP Remote config is used by the SAML 2.0 SP to identify trusted SAML 2.0 IdPs.
 *
 */



$metadata = array( 

	/*
	 * Example simpleSAMLphp SAML 2.0 IdP
	 */
	'idp.example.org' =>  array(
		'name'					=>	'Test',
		'description'			=> 'Description of this example entry',
		'SingleSignOnService'	=>	'https://idp.example.org/simplesaml/saml2/idp/SSOService.php',
		'SingleLogoutService'	=>	'https://idp.example.org/simplesaml/saml2/idp/SingleLogoutService.php',
		'certFingerprint'		=>	'3fa158e8abfd4b5203315b08c0b791b6ee4715f6',
		'base64attributes'		=>	true,

		 /*
		 * When request.signing is true the certificate of the IdP will be used
		 * to verify all messages received with the HTTPRedirect binding.
		 * 
		 * The certificate from the IdP must be installed in the cert directory 
		 * before verification can be done.  
		 */
		'request.signing' => false,
		'certificate' => "idp.example.org.crt",

		/*
		 * It is possible to relax some parts of the validation of SAML2 messages.
		 * To relax a part, add the id to the 'saml2.relaxvalidation' array.
		 *
		 * Valid ids:
		 * - 'unknowncondition'         Disables errors when encountering unknown <Condition> nodes.
		 * - 'nosubject'                Ignore missing <Subject> in <Assertion>.
		 * - 'noconditions'             Ignore missing <Conditions> in <Assertion>.
		 * - 'noauthnstatement'         Ignore missing <AuthnStatement> in <Assertion>.
		 * - 'noattributestatement'     Ignore missing <AttributeStatement> in <Assertion>.
		 *
		 * Example:
		 * 'saml2.relaxvalidation' => array('unknowncondition', 'noattributestatement'),
		 *
		 * Default:
		 * 'saml2.relaxvalidation' => array(),
		 */
		'saml2.relaxvalidation' => array(),

	),


	/*
	 * Metadata for Feide's test environment.
	 */
	'max.feide.no' =>  array(
		'name'					=>	'Test environment of Feide',
		'description'			=> 'max.feide.no: the test environment of Feide.',
		'SingleSignOnService'	=>	'https://max.feide.no/amserver/SSORedirect/metaAlias/idp',
		'SingleLogoutService'	=>	'https://max.feide.no/amserver/IDPSloRedirect/metaAlias/idp',
		'certFingerprint'		=>	'3fa158e8abfd4b5203315b08c0b791b6ee4715f6',
		'base64attributes'		=>	true
	),
		 	
	/*
	 * Metadata for Feide's production environment.
	 */
	'sam.feide.no' =>  array( 
		'name'					=>	'Feide',
		'description'			=> 'Authenticate with your identity from a school or university in Norway.',
		'SingleSignOnService'	=>	'https://sam.feide.no/amserver/SSORedirect/metaAlias/idp',
		'SingleLogoutService'	=>	'https://sam.feide.no/amserver/IDPSloRedirect/metaAlias/idp',
		'certFingerprint'		=>	'3a:e7:d3:d3:06:ba:57:fd:7f:62:6a:4b:a8:64:b3:4a:53:d9:5d:d0',
		'base64attributes'		=>	true
	) 

    );
?>
