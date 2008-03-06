<?php

$lang = array(
	'en'	=>	array(
		'title_CACHEAUTHNREQUEST'	=>	'Error making single sign-on to service',
		'descr_CACHEAUTHNREQUEST'	=>	'You can authenticated and are ready to be sent back to the service that requested authentication, but we could not find your cached authentication request. The request is only cached for a limited amount of time. If you leaved your browser open for hours before entering your username and password, this could be one possible explaination. If this could be the case in your situation, try to go back to the service you want to access, and start a new login process. If this issue continues, please report the problem.',
		
		'title_CREATEREQUEST' => 'Error creating request',
		'descr_CREATEREQUEST' => 'An error occured when trying to create the SAML request.',
		
		'title_DISCOPARAMS' => 'Bad request to discovery service',
		'descr_DISCOPARAMS' => 'The parameters sent to the discovery service were not following the specification.',
		
		'title_GENERATEAUTHNRESPONSE' => 'Could not create authentication response',
		'descr_GENERATEAUTHNRESPONSE' => 'When this identity provider tried to create an authentication response, an error occured.',

		'title_GENERATELOGOUTRESPONSE' => 'Could not create logout response',
		'descr_GENERATELOGOUTRESPONSE' => 'When this SAML entity tried to create an logout response, an error occured.',

		
		'title_LDAPERROR' => 'LDAP Error',
		'descr_LDAPERROR' => 'LDAP is the user database, and when you try to login, we need to contact an LDAP database. When we tried it this time an error occured.',
		
		'title_LOGOUTREQUEST' => 'Error processing Logout Request',
		'descr_LOGOUTREQUEST' => 'An error occured when trying to process the Logout Request.',
		
		'title_GENERATELOGOUTREQUEST' => 'Could not create logout request',
		'descr_GENERATELOGOUTREQUEST' => 'When this SAML entity tried to create an logout request, an error occured.',
		
		'title_LOGOUTRESPONSE' => 'Error processing Logout Response',
		'descr_LOGOUTRESPONSE' => 'An error occured when trying to process the Logout Response.',
		
		'title_METADATA' => 'Error loading metadata',
		'descr_METADATA' => 'There is some misconfiguration of your simpleSAMLphp installation. If you are the administrator of this service, you should make sure your metadata configuration is correctly setup.',
		
		'title_NOACCESS' => 'No access',
		'descr_NOACCESS' => 'This endpoint is not enabled. Check the enable options in your configuration of simpleSAMLphp.',
		
		'title_NORELAYSTATE' => 'No RelayState',
		'descr_NORELAYSTATE' => 'The initiator of this request did not provide an RelayState parameter, that tells where to go next.',
		
		'title_NOSESSION' => 'No session found',
		'descr_NOSESSION' => 'Unfortuneately we could not get your session. This could be because your browser do not support cookies, or cookies is disabled. Or may be your session timed out because you let the browser open for a long time.',
		
		'title_PROCESSASSERTION' =>	'Error processing response from IdP',
		'descr_PROCESSASSERTION' =>	'We did not accept the response sent from the Identity Provider.',
		
		'title_PROCESSAUTHNRESPONSE' =>	'Error processing request from Service Provider',
		'descr_PROCESSAUTHNRESPONSE' =>	'This IdP received an authentication request from a service provider, but an error occured when trying to process the request.',
		
		'title_SSOSERVICEPARAMS' =>	'Wrong parameters provided',
		'descr_SSOSERVICEPARAMS' =>	'You must either provide a SAML Request message or a RequestID on this interface.',
		
		'title_SLOSERVICEPARAMS' => 'No SAML message provided',
		'descr_SLOSERVICEPARAMS' => 'You accessed the SingleLogoutService interface, but did not provide a SAML LogoutRequest or LogoutResponse.',
		
		'title_ACSPARAMS' => 'No SAML response provided',
		'descr_ACSPARAMS' => 'You accessed the Assertion Consumer Service interface, but did not provide a SAML Authentication Response.'
	)

);