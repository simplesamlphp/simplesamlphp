<?php

/*
* AUTHOR: Samuel Muñoz Hidalgo
* EMAIL: samuel.mh@gmail.com
* LAST REVISION: 13-FEB-09
* DESCRIPTION: 'InfoCard' module configuration for simpleSAMLphp.


Some definitions were taken from:
A Guide to Using the Identity Selector
Interoperability Profile V1.5 within Web
Applications and Browsers.
Copyright Microsoft

*/


$config = array (
	
//-------------  TEMPLATE OPTIONS ---------------
	'IClogo' => 'resources/infocard_114x80.png',        //Infocard logo button
	'help_desk_email_URL' => 'mailto:asd@asd.com',      //Help desk e-mail
	'contact_info_URL' => 'http://google.es',           //Contact information
	
	
	
	
//-------------  CERTIFICATE OPTIONS ---------------
	
	/*
	* USED IN: Relying Party
	* DESCRIPTION: Key of the certificate used in the https connection with the idp, it'll be used
	*  for decrypting the received XML token,
	*/
	'idp_key' => '/etc/apache2/ssl/idp.key',
	
	
	/*
	* USED IN: Relying Party
	* DESCRIPTION: Only accept tokens signed with this certificate,
	*  if no certificate is set, it'll be assumed to accept
	*  a self isued token and accept any token. 
	*/
	'sts_crt' => '/etc/apache2/ssl/sts.crt',
	
	
	/*
	* USED IN: Infocard Generator, STS
	*	DESCRIPTION: STS certificate for signing Infocards and tokens.
	*/
	'sts_key' => '/etc/apache2/ssl/sts.key',
	
	
	/*
	* USED IN:
	*	DESCRIPTION: Array of certificates forming a trust chain.  The local signing
	* certificate is [0], the one that signed that is [1], etc, chaining to a
	* trust anchor.
	* HINT: The first one, [0], should be the same as the sts_crt. 
	*/	
	'certificates' => array(
		0 => '/etc/apache2/ssl/sts.crt',
		1 => '/etc/apache2/ssl/CA.crt'
	),
	
	
	
//-------------  DATA (InfoCard) OPTIONS ---------------
	
	/*
	* USED IN: InfoCard Generator, Relying Party and STS
	*	DESCRIPTION: Infocard information
	*/
	'InfoCard' => array(
		/*
		*   -issuer (optional, taken from the sts_crt common name value, if no set, self issuer is assumed )
		* This parameter specifies the URL of the STS from which to obtain a token. If omitted, no
		* specific STS is requested. The special value
		* “http://schemas.xmlsoap.org/ws/2005/05/identity/issuer/self” specifies that the
		* token should come from a Self-issued Identity Provider
		*/
		/*
		* Root of the current InfoCard schema
		*/
		'schema' => 'http://schemas.xmlsoap.org/ws/2005/05/identity',
		/*
		*   -issuerPolicy (optional)
		* This parameter specifies the URL of an endpoint from which the STS’s WS-SecurityPolicy
		* can be retrieved using WS-MetadataExchange. This endpoint must use HTTPS.
		*/
		'issuerPolicy' => '',
		/*
		*   -privacyUrl (optional)
		* This parameter specifies the URL of the human-readable Privacy Policy of the site, if
		* provided.
		*/
		'privacyURL' => '',
		/*
		*   -tokenType (optional)
		* This parameter specifies the type of the token to be requested from the STS as a URI. Th
		* parameter can be omitted if the STS and the Web site front-end have a mutual
		* understanding about what token type will be provided or if the Web site is willing to accep
		* any token type.
		*/
		'tokenType' => 'urn:oasis:names:tc:SAML:1.0:assertion',
		
		/*-Claims supported by the current schema
			givenname
			surname
			emailaddress
			streetaddress
			locality
			stateorprovince
			postalcode
			country
			primaryphone
			dateofbirth
			privatepersonalid
			gender
			webpage
		*/
		
		/*
		*   -requiredClaims (optional)
		* This parameter specifies the types of claims that must be supplied by the identity. If
		* omitted, there are no required claims. The value of requiredClaims is a space-separate
		* list of URIs, each specifying a required claim type.
		*/
		'requiredClaims' => array(
			'privatepersonalidentifier' => array('displayTag'=>"Id",         'description'=>"id"),
			'givenname' =>                 array('displayTag'=>"Given Name", 'description'=>"etc"),
			'surname' =>                   array('displayTag'=>"Surname",    'description'=>"apellidos"),
			'emailaddress' =>              array('displayTag'=>"e-mail",     'description'=>"E-mail address")
		),
		/*
		*   -optionalClaims (optional)
		* This parameter specifies the types of optional claims that may be supplied by the identity
		* If omitted, there are no optional claims. The value of optionalClaims is a space-separat
		* list of URIs, each specifying a claim type that can be optionally submitted
		*/
		'optionalClaims' => array(
			'country' => array('displayTag'=>"country", 'description'=>"País"),
			'webpage' => array('displayTag'=>"webpage", 'description'=>"Página web")
		),
	),




//-------------  WEB PAGES ---------------
	
	/*
	* USED IN: InfoCard Generator, Relying Party (optional form)
	*	DESCRIPTION: Infocard generator URL, if set it'll  appear a form with username-password authentication in the template
	*/
	'CardGenerator' => 'https://sts.aut.uah.es/simplesaml/module.php/InfoCard/getcardform.php',


	/*
	* USED IN: InfoCard Generator, Relying Party (issuer), STS (Metadata-Exchange)
	*	DESCRIPTION: Token generator URL
	*/
	'tokenserviceurl' => 'https://sts.aut.uah.es/simplesaml/module.php/InfoCard/tokenservice.php',
	
	
	/*
	* USED IN: InfoCard Generator
	*	DESCRIPTION: Metadata Exchange URL
	*/
	'mexurl' => 'https://sts.aut.uah.es/simplesaml/module.php/InfoCard/mex.php',




//-------------  CREDENTIALS ---------------

	/*
	* USED IN: InfoCard Generator, Relying Party (optional form)
	* TYPES: UsernamePasswordCredential, KerberosV5Credential, X509V3Credential, SelfIssuedCredential
	* DESCRIPTION: How the user will be authenticated
	* IMPLEMENTED & TESTED: UsernamePasswordCredential, SelfIssuedCredential
	*/
	'UserCredential' => 'SelfIssuedCredential',




//-------------  DEBUG ---------------

	/*
	* USED IN: tokenservice
	* DESCRIPTION: directory where RSTs and RSTRs will be logged EJ: /tmp.
	*  If null, logging will be dissabled.
	*  The directory MUST exists and be accessible to the program, otherwise NO log will be written
	*  Log files have the form urn:uuid:XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX.log where X is an hexadecimal digit [0-9|a-f]
	*/
	'debugDir' => '/tmp',

);
 

?>