<?php
/* 
 * SAML 2.0 Meta data for simpleSAMLphp
 *
 */


$metadata = array(

	'urn:mace:switch.ch:aaitest:dukono.switch.ch'	=> array(
		'SingleSignOnUrl'		=>	'https://dukono.switch.ch/shibboleth-idp/SSO',
		'certFingerprint'		=>	'c7279a9f28f11380509e075441e3dc55fb9ab864' 
//		'certFingerprint'		=>	'4e730f327ce8d9fe6269298d8f777a4bd0937ba5'
//		c7279a9f28f11380509e075441e3dc55fb9ab864
		// "SingleLogOutUrl" => "https://mars.feide.no/amserver/IDPSloRedirect/metaAlias/idp",
	),
	
	'feide.erlang.no-shib13'	=> array(
		'issuer'						=>	'feide.erlang.no',
		'assertionDurationMinutes'		=>	10,
		'audience'						=> 'urn:mace:feide:shiblab'
	),
	
	'urn:mace:dfnwayf'	=> array(
		'SingleSignOnUrl'		=>	'https://dfn.wayf.com/WAYF'
	)
);

?>