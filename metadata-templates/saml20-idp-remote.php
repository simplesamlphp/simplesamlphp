<?php
/* 
 * SAML 2.0 Meta data for simpleSAMLphp
 *
 * The SAML 2.0 IdP Remote config is used by the SAML 2.0 SP to identify trusted SAML 2.0 IdPs.
 *
 */



$metadata = array( 

	'https://openidp.feide.no' =>  array(
		'name'                 => 'Feide RnD OpenIdP',
		'description'          => 'Here you can login with your account on Feide RnD OpenID. If you do not already have an account on this identity provider, you can create a new one by following the create new account link and follow the instructions.',
		'SingleSignOnService'  => 'https://openidp.feide.no/simplesaml/saml2/idp/SSOService.php',
		'SingleLogoutService'  => 'https://openidp.feide.no/simplesaml/saml2/idp/SingleLogoutService.php',
		'certFingerprint'      => 'c9ed4dfb07caf13fc21e0fec1572047eb8a7a4cb'
	),
	
	
	/*
	 * Example simpleSAMLphp SAML 2.0 IdP
	 */
	'idp-entity-id-simple' =>  array(
		'name'                 => 'Test',
		'description'          => 'Description of this example entry',
		
		'SingleSignOnService'  => 'https://idp.example.org/simplesaml/saml2/idp/SSOService.php',
		'SingleLogoutService'  => 'https://idp.example.org/simplesaml/saml2/idp/SingleLogoutService.php',
		'certFingerprint'      => '3fa158e8abfd4b5203315b08c0b791b6ee4715f6'
	),

	/*
	 * Example simpleSAMLphp SAML 2.0 IdP
	 */
	'idp-entity-id' =>  array(
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
		'certFingerprint'		=>	'5dd3196bdb2fb7e75380fe234a3f4f2d1e8d6d84',
		'base64attributes'		=>	true,
		'hint.cidr'				=> '158.38.0.0/16'
	),
		 	
	/*
	 * Metadata for Feide's production environment.
	 */
	'sam.feide.no' =>  array( 
		'name'					=>	'Feide',
		'description'			=> 'Authenticate with your identity from a school or university in Norway.',
		'SingleSignOnService'	=>	'https://sam.feide.no/amserver/SSORedirect/metaAlias/idp',
		'SingleLogoutService'	=>	'https://sam.feide.no/amserver/IDPSloRedirect/metaAlias/idp',
		'certFingerprint'		=>	'f6:72:c5:e7:04:fb:86:5e:93:6b:3b:cd:45:b0:49:2e:94:f5:f0:95',
		'base64attributes'		=>	true,
		'hint.cidr'				=> '158.38.0.0/16'
	) 

    );
?>
