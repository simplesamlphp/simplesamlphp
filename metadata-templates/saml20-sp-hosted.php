<?php
/* 
 * SAML 2.0 Meta data for simpleSAMLphp
 *
 * The SAML 2.0 IdP Remote config is used by the SAML 2.0 SP to identify itself.
 *
 */
 
$metadata = array( 

	/*
	 * Example of a hosted SP 
	 */
	'sp.example.org' => array(
		'host'							=>	'sp.example.org',
		'spNameQualifier' 				=>	'sp.example.org',
		'NameIDFormat'					=>	'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
		'ForceAuthn'					=>	'false',

		
		/*
		 * When request.signing is true the privatekey and certificate of the SP
		 * will be used to sign/verify all messages received/sent with the HTTPRedirect binding.
		 * 
		 * Certificate and privatekey must be placed in the cert directory.
		 */
		'request.signing' => true,
		'privatekey' => 'server.pem',
		'certificate' => 'server.pem',
		
	)

);


?>