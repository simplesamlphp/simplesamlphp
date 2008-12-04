<?php

/*
* AUTHOR: Samuel Muñoz Hidalgo
* EMAIL: samuel.mh@gmail.com
* LAST REVISION: 1-DEC-08
* DESCRIPTION: 'login-infocard' module configuration.


-server_key:
-server_crt:
-IClogo: InfoCard logo (template's button)


Definitions taken from:
A Guide to Using the Identity Selector
Interoperability Profile V1.5 within Web
Applications and Browsers.
Copyright Microsoft
"
-issuer (optional)
	This parameter specifies the URL of the STS from which to obtain a token. If omitted, no
	specific STS is requested. The special value
	“http://schemas.xmlsoap.org/ws/2005/05/identity/issuer/self” specifies that the
	token should come from a Self-issued Identity Provider.

-issuerPolicy (optional)
	This parameter specifies the URL of an endpoint from which the STS’s WS-SecurityPolicy
	can be retrieved using WS-MetadataExchange. This endpoint must use HTTPS.

-tokenType (optional)
	This parameter specifies the type of the token to be requested from the STS as a URI. Th
	parameter can be omitted if the STS and the Web site front-end have a mutual
	understanding about what token type will be provided or if the Web site is willing to accep
	any token type.

-requiredClaims (optional)
	This parameter specifies the types of claims that must be supplied by the identity. If
	omitted, there are no required claims. The value of requiredClaims is a space-separate
	list of URIs, each specifying a required claim type.

-optionalClaims (optional)
	This parameter specifies the types of optional claims that may be supplied by the identity
	If omitted, there are no optional claims. The value of optionalClaims is a space-separat
	list of URIs, each specifying a claim type that can be optionally submitted.

-privacyUrl (optional)
	This parameter specifies the URL of the human-readable Privacy Policy of the site, if
	provided.
"


-Claims supported by the current schema
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


$config = array (
	
	'server_key' => '/etc/apache2/ssl/idp.key',
	'server_crt' => '/etc/apache2/ssl/idp.crt',
	'sts_crt' => '/etc/apache2/ssl/sts.crt',
	
	'IClogo' => 'resources/infocard_114x80.png',
	

	'InfoCard' => array(
		'schema' => 'http://schemas.xmlsoap.org/ws/2005/05/identity',
		'issuer' => 'https://sts/tokenservice.php',
		'issuerPolicy' => '',
		'privacyURL' => '',
		'tokenType' => 'urn:oasis:names:tc:SAML:1.0:assertion',
		'requiredClaims' => array(
			'privatepersonalidentifier' => array('displayTag'=>"Id",         'description'=>"id"),
			'givenname' =>                 array('displayTag'=>"Given Name", 'description'=>"etc"),
			'surname' =>                   array('displayTag'=>"Surname",    'description'=>"apellidos"),
			'emailaddress' =>              array('displayTag'=>"e-mail",     'description'=>"E-mail address")
		),
		'optionalClaims' => array(
			'country' => array('displayTag'=>"country", 'description'=>"País"),
			'webpage' => array('displayTag'=>"webpage", 'description'=>"Página web")
		),
	),


//STS only
// array of certificates forming a trust chain.  The local signing
// certificate is [0], the one that signed that is [1], etc, chaining to a
// trust anchor.
	
	'CardGenerator' => 'https://idp.aut.uah.es/simplesaml/module.php/InfoCard/getinfocard.php',
	'certificates' => array(
		0 => '/etc/apache2/ssl/sts.crt',
		1 => '/etc/apache2/ssl/CA.crt'
	),
	
	'sts_key' => '/etc/apache2/ssl/sts.key',
	'tokenserviceurl' => 'https://sts/tokenservice.php',
	'mexurl' => 'https://sts/mex.php',
);

?>