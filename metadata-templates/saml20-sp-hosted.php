<?php
/* 
 * SAML 2.0 Meta data for simpleSAMLphp
 *
 * The SAML 2.0 IdP Remote config is used by the SAML 2.0 SP to identify itself.
 *
 * Required fields:
 *  - host
 *
 * Optional fields:
 *  - NameIDFormat
 *  - ForceAuthn  
 *
 * Authentication request signing
 *    When request.signing is true the privatekey and certificate of the SP 
 *    will be used to sign/verify all messages received/sent with the HTTPRedirect binding.
 *    Certificate and privatekey must be placed in the cert directory.
 *    All these attributes are optional:
 *
 *  - 'request.signing' => true,
 *  - 'privatekey' => 'server.pem',
 *  - 'certificate' => 'server.crt',
 */
 
$metadata = array( 

	/*
	 * Example of a hosted SP 
	 */
	'__DYNAMIC:1__' => array(
		'host'  => '__DEFAULT__'
	)

);


?>