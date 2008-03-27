<?php

$lang = array(


	'error_header' => array(
		'en' => 'simpleSAMLphp error',
		'no' => 'simpleSAMLphp feil',
		'dk' => '',
	),
	
	'report_trackid' => array(
		'en' => 'If you report this error, please also report this tracking ID which makes it possible to locate your session in the logs which are available to the system administrator:',
		'no' => 'Hvis du ønsker å rapportere denne feilen, send også med denne sporings-IDen. Den gjør det enklere for systemadministratorene og finne ut hva som gikk galt:',
		'dk' => 'Hvis du vil rapportere denne fejl, så medsend venligst dette sporings-ID. Den gør det muligt for teknikerne at finde fejlen.',
	),
	
	'debuginfo_header' => array(
		'en' => 'Debug information',
		'no' => 'Detaljer for feilsøking',
		'dk' => 'Detaljer til fejlsøgning',
	),

	'debuginfo_text' => array(
		'en' => 'The debug information below may be interesting for the administrator / help desk:',
		'no' => 'Detaljene nedenunder kan være av interesse for administratoren / hjelpetjenesten',
		'dk' => 'Detaljerne herunder kan være af interesse for teknikerne / help-desken',
	),
	
	'report_header' => array(
		'en' => 'Report errors',
		'no' => 'Rapporter feil',
		'dk' => 'Rapportér fejl',
	),

	'report_text' => array(
		'en' => 'Optionally enter your email address, for the administrators to be able contact you for further questions about your issue:',
		'no' => 'Dersom du ønsker at hjelpetjenesten skal kunde kontakte deg igjen i forbindelse med denne feilen, må du oppgi e-post adressen din nedenunder:',
		'dk' => 'Hvis du vil kunne kontaktes i forbindelse med fejlmeldingen, bedes du indtaste din emailadresse herunder',
	),
	
	'report_email' => array(
		'en' => 'E-mail address: ',
		'no' => 'E-post adresse:',
		'dk' => 'E-mailadresse',
	),
	
	'report_explain' => array(
		'en' => 'Explain what you did to get this error...',
		'no' => 'Forklar hva du gjorde og hvordan feilen oppsto...',
		'dk' => 'Forklar hvad du gjorde og hvordan fejlen opstod',
	),
	
	'report_submit' => array(
		'en' => 'Send error report',
		'no' => 'Send feilrapport',
		'dk' => 'Send fejlrapport',
	),
	
	'howto_header' => array(
		'en' => 'Send error report',
		'no' => 'Send feilrapport',
		'dk' => 'Send fejlrapport',
	),
	
	'howto_text' => array(
		'en' => 'This error probably is due to some unexpected behaviour or to misconfiguration of simpleSAMLphp. Contact the administrator of this login service, and send them the error message above.',
		'no' => 'Denne feilen skyldes sannsynligvis en feilkonfigurasjon av simpleSAMLphp eller som en følge av en uforutsett hendelse. Kontakt administratoren av denne tjenesten og rapporter så mye som mulig angående feilen.',
		'dk' => 'Denne fejl skyldes formentlig en fejlkonfiguration af simpleSAMLphp - alternativt en ukendt fejl. Kontakt administratoren af denne tjeneste og rapportér så mange detaljer som muligt om fejlen',
	),
	
	'title_CACHEAUTHNREQUEST' => array('en' => 'Error making single sign-on to service'),
	'descr_CACHEAUTHNREQUEST' => array('en' => 'You can authenticated and are ready to be sent back to the service that requested authentication, but we could not find your cached authentication request. The request is only cached for a limited amount of time. If you leaved your browser open for hours before entering your username and password, this could be one possible explaination. If this could be the case in your situation, try to go back to the service you want to access, and start a new login process. If this issue continues, please report the problem.'),
	
	'title_CREATEREQUEST' => array('en' => 'Error creating request'),
	'descr_CREATEREQUEST' => array('en' => 'An error occured when trying to create the SAML request.'),
	
	'title_DISCOPARAMS' => array('en' => 'Bad request to discovery service'),
	'descr_DISCOPARAMS' => array('en' => 'The parameters sent to the discovery service were not following the specification.'),
	
	'title_GENERATEAUTHNRESPONSE' => array('en' => 'Could not create authentication response'),
	'descr_GENERATEAUTHNRESPONSE' => array('en' => 'When this identity provider tried to create an authentication response, an error occured.'),

	'title_GENERATELOGOUTRESPONSE' => array('en' => 'Could not create logout response'),
	'descr_GENERATELOGOUTRESPONSE' => array('en' => 'When this SAML entity tried to create an logout response, an error occured.'),

	
	'title_LDAPERROR' => array('en' => 'LDAP Error'),
	'descr_LDAPERROR' => array('en' => 'LDAP is the user database, and when you try to login, we need to contact an LDAP database. When we tried it this time an error occured.'),
	
	'title_LOGOUTREQUEST' => array('en' => 'Error processing Logout Request'),
	'descr_LOGOUTREQUEST' => array('en' => 'An error occured when trying to process the Logout Request.'),
	
	'title_GENERATELOGOUTREQUEST' => array('en' => 'Could not create logout request'),
	'descr_GENERATELOGOUTREQUEST' => array('en' => 'When this SAML entity tried to create an logout request, an error occured.'),
	
	'title_LOGOUTRESPONSE' => array('en' => 'Error processing Logout Response'),
	'descr_LOGOUTRESPONSE' => array('en' => 'An error occured when trying to process the Logout Response.'),
	
	'title_METADATA' => array('en' => 'Error loading metadata'),
	'descr_METADATA' => array('en' => 'There is some misconfiguration of your simpleSAMLphp installation. If you are the administrator of this service, you should make sure your metadata configuration is correctly setup.'),
	
	'title_NOACCESS' => array('en' => 'No access'),
	'descr_NOACCESS' => array('en' => 'This endpoint is not enabled. Check the enable options in your configuration of simpleSAMLphp.'),
	
	'title_NORELAYSTATE' => array('en' => 'No RelayState'),
	'descr_NORELAYSTATE' => array('en' => 'The initiator of this request did not provide an RelayState parameter, that tells where to go next.'),
	
	'title_NOSESSION' => array('en' => 'No session found'),
	'descr_NOSESSION' => array('en' => 'Unfortuneately we could not get your session. This could be because your browser do not support cookies, or cookies is disabled. Or may be your session timed out because you let the browser open for a long time.'),
	
	'title_PROCESSASSERTION' =>	array('en' => 'Error processing response from IdP'),
	'descr_PROCESSASSERTION' =>	array('en' => 'We did not accept the response sent from the Identity Provider.'),
	
	'title_PROCESSAUTHNRESPONSE' =>	array('en' => 'Error processing response from Identity Provider'),
	'descr_PROCESSAUTHNRESPONSE' =>	array('en' => 'This IdP received an authentication response from a service provider, but an error occured when trying to process the response.'),
	
	'title_PROCESSAUTHNREQUEST' => array(
		'en' => 'Error processing request from Service Provider',
		'no' => 'Feil under prosessering av forespørsel fra SP'),
	'descr_PROCESSAUTHNREQUEST' => array(
		'en' => 'This IdP received an authentication request from a service provider, but an error occured when trying to process the request.',
		'no' => 'Denne IdP-en mottok en autentiseringsforespørsel fra en SP, men en feil oppsto under prosessering av requesten.'),
	
	
	'title_SSOSERVICEPARAMS' =>	array('en' => 'Wrong parameters provided'),
	'descr_SSOSERVICEPARAMS' =>	array('en' => 'You must either provide a SAML Request message or a RequestID on this interface.'),
	
	'title_SLOSERVICEPARAMS' => array('en' => 'No SAML message provided'),
	'descr_SLOSERVICEPARAMS' => array('en' => 'You accessed the SingleLogoutService interface, but did not provide a SAML LogoutRequest or LogoutResponse.'),
	
	'title_ACSPARAMS' => array('en' => 'No SAML response provided'),
	'descr_ACSPARAMS' => array('en' => 'You accessed the Assertion Consumer Service interface, but did not provide a SAML Authentication Response.'),
	
	'title_CASERROR' => array('en' => 'CAS Error'),
	'descr_CASERROR' => array('en' => 'Error when communicating with the CAS server.'),

);