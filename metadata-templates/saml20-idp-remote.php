<?php
/* 
 * SAML 2.0 Meta data for simpleSAMLphp
 *
 * The SAML 2.0 IdP Remote config is used by the SAML 2.0 SP to identify trusted SAML 2.0 IdPs.
 *
 */



$metadata = array( 
	"feide2.erlang.no-saml2" => 
		array(
			"SingleSignOnUrl"	=>	"https://feide2.erlang.no/saml2/idp/SSOService.php",
		 	"SingleLogOutUrl"	=>	"https://feide2.erlang.no/saml2/idp/LogoutService.php",
		 	"certFingerprint"	=>	"afe71c28ef740bc87425be13a2263d37971da1f9",
		 	"base64attributes"	=>	true),

	'dev2.andreas.feide.no' => 
		array(
			"SingleSignOnUrl"	=>	"http://dev2.andreas.feide.no/saml2/idp/SSOService.php",
		 	"SingleLogOutUrl"	=>	"http://dev2.andreas.feide.no/saml2/idp/LogoutService.php",
		 	"certFingerprint"	=>	"afe71c28ef740bc87425be13a2263d37971da1f9",
		 	"base64attributes"	=>	false),
		 	
	"sam.feide.no" => 
		array( 
			"SingleSignOnUrl"	=>	"https://sam.feide.no/amserver/SSORedirect/metaAlias/idp",
		 	"SingleLogOutUrl"	=>	"https://sam.feide.no/amserver/IDPSloRedirect/metaAlias/idp",
		 	"certFingerprint"	=>	"3a:e7:d3:d3:06:ba:57:fd:7f:62:6a:4b:a8:64:b3:4a:53:d9:5d:d0",
		 	"base64attributes"	=>	true),
		 	
	"max.feide.no" => 
		array(
			"SingleSignOnUrl"	=>	"https://max.feide.no/amserver/SSORedirect/metaAlias/idp",
		 	"SingleLogOutUrl"	=>	"https://max.feide.no/amserver/IDPSloRedirect/metaAlias/idp",
		 	"certFingerprint"	=>	"d79b0e23c0833d2f5b8d94abd54ae693708b1eef",
		 	"base64attributes"	=>	false )

    );
?>
