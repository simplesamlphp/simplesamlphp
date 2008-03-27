<?php

$lang = array(


	'error_header' => array(
		'en' => 'simpleSAMLphp error',
		'no' => 'simpleSAMLphp feil',
		'dk' => 'simpleSAMLphp fejl',
		'fr' => 'erreur de simpleSAMLphp',
	),
	
	'report_trackid' => array(
		'en' => 'If you report this error, please also report this tracking ID which makes it possible to locate your session in the logs which are available to the system administrator:',
		'no' => 'Hvis du ønsker å rapportere denne feilen, send også med denne sporings-IDen. Den gjør det enklere for systemadministratorene og finne ut hva som gikk galt:',
		'dk' => 'Hvis du vil rapportere denne fejl, så medsend venligst dette sporings-ID. Den gør det muligt for teknikerne at finde fejlen.',
		'es' => 'Por favor, si informa de este error, mantenga el <emph>tracking ID</emph> que permite enonctrar su sesi&oacute;n en los registros de que dispone el administrador del sistema:',
		'fr' => 'Si vous signalez cette erreur, veuillez aussi signaler l\'identifiant de suivi qui permet de trouver votre session dans les logs accessibles à l\'administrateur système :',
	),
	
	'debuginfo_header' => array(
		'en' => 'Debug information',
		'no' => 'Detaljer for feilsøking',
		'dk' => 'Detaljer til fejlsøgning',
		'es' => 'Informaci&oacute;n de depuraci&oacute;n',
		'fr' => 'Information de débugage',
	),

	'debuginfo_text' => array(
		'en' => 'The debug information below may be interesting for the administrator / help desk:',
		'no' => 'Detaljene nedenunder kan være av interesse for administratoren / hjelpetjenesten',
		'dk' => 'Detaljerne herunder kan være af interesse for teknikerne / help-desken',
		'es' => 'La siguiente informaci&oacute; de depuraci;oacute;n puede ser de utilidad para el administrador del sistema o el centro de atenci&aucte;n a usuarios:',
		'fr' => 'L\'information de débugage ci-dessous peut être intéressante pour l\'administrateur ou le help desk',
	),
	
	'report_header' => array(
		'en' => 'Report errors',
		'no' => 'Rapporter feil',
		'dk' => 'Rapportér fejl',
		'es' => 'Informar del error',
		'fr' => 'Signaler les erreurs',
	),

	'report_text' => array(
		'en' => 'Optionally enter your email address, for the administrators to be able contact you for further questions about your issue:',
		'no' => 'Dersom du ønsker at hjelpetjenesten skal kunde kontakte deg igjen i forbindelse med denne feilen, må du oppgi e-post adressen din nedenunder:',
		'dk' => 'Hvis du vil kunne kontaktes i forbindelse med fejlmeldingen, bedes du indtaste din emailadresse herunder',
		'es' => 'Si lo desea, indique su direcci&oacute;n electr;oacute;nica, para que los administradores puedan ponerse en contacto con usted y obtener datos adicionales de su problema',
		'fr' => 'Facultativement vous pouvez entrer votre courriel, pour que les administrateurs puissent vous contacter par la suite à propose de votre problème :',
	),
	
	'report_email' => array(
		'en' => 'E-mail address:',
		'no' => 'E-post adresse:',
		'dk' => 'E-mailadresse:',
		'es' => 'Correo-e:',
		'fr' => 'Courriel :',
	),
	
	'report_explain' => array(
		'en' => 'Explain what you did to get this error...',
		'no' => 'Forklar hva du gjorde og hvordan feilen oppsto...',
		'dk' => 'Forklar hvad du gjorde og hvordan fejlen opstod',
		'es' => 'Explique lo que ha hecho para llegar a este error...',
		'fr' => 'Expliquez ce que vous faisiez pour obtenir cette erreur ...',
	),
	
	'report_submit' => array(
		'en' => 'Send error report',
		'no' => 'Send feilrapport',
		'dk' => 'Send fejlrapport',
		'es' => 'Send error report',
		'fr' => 'Envoyer le rapport d\'erreur',
	),
	
	'howto_header' => array(
		'en' => 'How to get help',
		'no' => 'Hvordan få hjelp',
		'dk' => 'Hvordan få hjælp',
		'fr' => 'Envoyer le rapport d\'erreur',
	),
	
	'howto_text' => array(
		'en' => 'This error probably is due to some unexpected behaviour or to misconfiguration of simpleSAMLphp. Contact the administrator of this login service, and send them the error message above.',
		'no' => 'Denne feilen skyldes sannsynligvis en feilkonfigurasjon av simpleSAMLphp eller som en følge av en uforutsett hendelse. Kontakt administratoren av denne tjenesten og rapporter så mye som mulig angående feilen.',
		'dk' => 'Denne fejl skyldes formentlig en fejlkonfiguration af simpleSAMLphp - alternativt en ukendt fejl. Kontakt administratoren af denne tjeneste og rapportér så mange detaljer som muligt om fejlen',
		'es' => 'Este erro se debe probablemente a un comportamiento inesperado o a una configuraci&oacute; incorrecta de simpleSAMLphp. P&oacute;ngase en contacto con el administrador de este servicio de conexi&oacute;n y env&iacute;ele el mensaje de error anterior.',
		'fr' => 'Cette erreur est problablement causée par un comportement imprévu ou une mauvaise configuration de simpleSAMLphp.  Contactez l\'administrateur de ce service d\'identification et envoyez lui le message d\'erreur.',
	),
	
	'title_CACHEAUTHNREQUEST' => array(
		'en' => 'Error making single sign-on to service',
		'es' => 'Error en el inicio de sesi—n œnico',
	),
	'descr_CACHEAUTHNREQUEST' => array(
		'en' => 'You can authenticated and are ready to be sent back to the service that requested authentication, but we could not find your cached authentication request. The request is only cached for a limited amount of time. If you leaved your browser open for hours before entering your username and password, this could be one possible explaination. If this could be the case in your situation, try to go back to the service you want to access, and start a new login process. If this issue continues, please report the problem.',
		'es' => 'Has podido ser autenticado y est‡s listo para retornar al servicio que solicit— la autenticaci—n, pero no es posible encontrar tu solicitud de autenticaci—n en cachŽ. Esta solicitud s—lo se conserva en cachŽ por un periodo limitado de tiempo. Si dej— su navegador abierto durante horas antes de introducir el nombre de usuario y la contrase–a, esto pudo provocar este error. Si es esa la situaci—n, intente retornar al servicio que quer’a acceder e intente acceder de nuevo. Si el problema continœa, por favor informe del problema',
	),
	
	'title_CREATEREQUEST' => array(
		'en' => 'Error creating request',
		'es' => 'Error en la creaci—n de la solictud',
	),
	'descr_CREATEREQUEST' => array(
		'en' => 'An error occured when trying to create the SAML request.',
		'es' => 'Se ha producido un error al tratar de crear la petici—n SAML.',
	),
	
	'title_DISCOPARAMS' => array(
		'en' => 'Bad request to discovery service',
		'es' => 'Solicitud err—nea al servicio de descubrimiento',
	),
	'descr_DISCOPARAMS' => array(
		'en' => 'The parameters sent to the discovery service were not following the specification.',
		'es' => 'Los parametros enviados al servicio de descubrimiento no se ajustan a la especificaci—n
.',
	),
	
	'title_GENERATEAUTHNRESPONSE' => array(
		'en' => 'Could not create authentication response',
		'es' => 'No se pudo crear la respuesta de autenticaci—n',
	),
	'descr_GENERATEAUTHNRESPONSE' => array(
		'en' => 'When this identity provider tried to create an authentication response, an error occured.',
		'es' => 'El proveedor de identidad ha detectado un error al crear respuesta de autenticaci—n.',
	),

	'title_GENERATELOGOUTRESPONSE' => array(
		'en' => 'Could not create logout response',
		'es' => 'No se pudo crear la respuesta de cierre de sesi—n',
	),
	'descr_GENERATELOGOUTRESPONSE' => array(
		'en' => 'When this SAML entity tried to create an logout response, an error occured.',
		'es' => 'La entidad SAML ha detectado un error al crear la respuesta de cierre de sesi—n.',
	),

	'title_LDAPERROR' => array(
		'en' => 'LDAP Error',
		'es' => 'Error de LDAP',
	),
	
	'descr_LDAPERROR' => array(
		'en' => 'LDAP is the user database, and when you try to login, we need to contact an LDAP database. When we tried it this time an error occured.',
		'es' => 'LDAP es la base de datos de usuarios, es necesario contactar con ella cuando usted decide entrar. Se ha producido un error en dicho acceso',
	),
	
	'title_LOGOUTREQUEST' => array(
		'en' => 'Error processing Logout Request',
		'es' => 'Error al procesar la solicitud de cierre de sesi—n',
	),
	'descr_LOGOUTREQUEST' => array(
		'en' => 'An error occured when trying to process the Logout Request.',
		'es' => 'Se ha producido un error al tratar de procesar la solicitud de cierre de sesi—n.',
	),
	
	'title_GENERATELOGOUTREQUEST' => array(
		'en' => 'Could not create logout request',
		'es' => 'No se ha podido crear la solicitud de cierre de sesi—n',
	),
	'descr_GENERATELOGOUTREQUEST' => array(
		'en' => 'When this SAML entity tried to create an logout request, an error occured.',
		'es' => 'La entidad SAML ha detectado un error al crear la solicitud de cierre de sesi—n.',
	),
	
	'title_LOGOUTRESPONSE' => array(
		'en' => 'Error processing Logout Response',
		'es' => 'Error al procesar la respuesta de cierre de sesi—n',
	),
	'descr_LOGOUTRESPONSE' => array(
		'en' => 'An error occured when trying to process the Logout Response.',
		'es' => 'Se ha producido un error al tratar de procesar la respuesta de cierre de sesi—n.',
	),
	
	'title_METADATA' => array(
		'en' => 'Error loading metadata',
		'es' => 'Error al cargar los metadatos',
	),
	'descr_METADATA' => array(
		'en' => 'There is some misconfiguration of your simpleSAMLphp installation. If you are the administrator of this service, you should make sure your metadata configuration is correctly setup.',
		'es' => 'Hay errores de configuraci—n en su instalaci—n de simpleSAMLphp. Si es usted el administrador del servicio, cerci—rese de que la configuraci—n de los metadatos es correcta.',
	),
	
	'title_NOACCESS' => array(
		'en' => 'No access',
		'es' => 'Acceso no definido',
	),
	'descr_NOACCESS' => array(
		'en' => 'This endpoint is not enabled. Check the enable options in your configuration of simpleSAMLphp.',
		'es' => 'Este punto de acceso no est‡ habilitado. Verifique las opciones de habilitaci—n en la configuraci—n de simpleSAMLphp.',
	),
	
	'title_NORELAYSTATE' => array(
		'en' => 'No RelayState',
		'es' => 'RelayState no definido',
	),
	'descr_NORELAYSTATE' => array(
		'en' => 'The initiator of this request did not provide an RelayState parameter, that tells where to go next.',
		'es' => 'El iniciador de esta solicitud no proporcion— el par‡metro RelayState que indica donde ir a continuaci—n',
	),
	
	'title_NOSESSION' => array(
		'en' => 'No session found',
		'es' => 'Sesi—n no encontrada',
	),
	'descr_NOSESSION' => array(
		'en' => 'Unfortuneately we could not get your session. This could be because your browser do not support cookies, or cookies is disabled. Or may be your session timed out because you let the browser open for a long time.',
		'es' => 'Desgraciadamente no hemos podido recuperar su sesi—n. Esto podr’a deberse a que su navegador no soporte cookies o a que las cookies estŽn deshabilitadas.. O quiz‡s su sesi—n caduc— si dej— su navegador abierto durante un periodo importante de tiempo.',
	),
	
	'title_PROCESSASSERTION' =>	array(
		'en' => 'Error processing response from IdP',
		'es' => 'Error al procesar la respuesta procedente del IdP',
	),
	'descr_PROCESSASSERTION' =>	array(
		'en' => 'We did not accept the response sent from the Identity Provider.',
		'es' => 'No ha sido posible aceptar la respuesta enviada por el proveedor de identidad.',
	),
	
	'title_PROCESSAUTHNRESPONSE' =>	array(
		'en' => 'Error processing response from Identity Provider',
		'es' => 'Error al procesar la solicitud del proveedor de servicio',
	),
	'descr_PROCESSAUTHNRESPONSE' =>	array(
		'en' => 'This SP received an authentication response from a identity provider, but an error occured when trying to process the response.'
	),
	
	'title_PROCESSAUTHNREQUEST' => array(
		'en' => 'Error processing request from Service Provider',
		'no' => 'Feil under prosessering av forespørsel fra SP',
		'es' => 'Error al procesar la solicitud del proveedor de servicio',
	),
	'descr_PROCESSAUTHNREQUEST' => array(
		'en' => 'This IdP received an authentication request from a service provider, but an error occured when trying to process the request.',
		'no' => 'Denne IdP-en mottok en autentiseringsforespørsel fra en SP, men en feil oppsto under prosessering av requesten.',
		'es' => 'Este IdP ha recibido una petici—n de autenticaci—n de un proveedor de servicio pero se ha producido un error al tratar de procesar la misma.',
	),
	
	
	'title_SSOSERVICEPARAMS' =>	array(
		'en' => 'Wrong parameters provided',
		'es' => 'Error en los par‡metros recibidos',
	),
	'descr_SSOSERVICEPARAMS' =>	array(
		'en' => 'You must either provide a SAML Request message or a RequestID on this interface.',
		'es' => 'Debe propocionar o una solicitud SAML o un RequestIP para esta interfaz.',
	),
	
	'title_SLOSERVICEPARAMS' => array(
		'en' => 'No SAML message provided',
		'es' => 'Falta el mensaje SAML',
	),
	'descr_SLOSERVICEPARAMS' => array(
		'en' => 'You accessed the SingleLogoutService interface, but did not provide a SAML LogoutRequest or LogoutResponse.',
		'es' => 'Usted accedi— a la interfaz SingleLogoutService pero no incluy— un mensaje SAML LogoutRequest o LogoutResponse',
	),
	
	'title_ACSPARAMS' => array(
		'en' => 'No SAML response provided',
		'es' => 'Falta la respuesta SAML',
	),
	'descr_ACSPARAMS' => array(
		'en' => 'You accessed the Assertion Consumer Service interface, but did not provide a SAML Authentication Response.',
		'es' => 'Usted accedi— a la interfaz consumidora de aserciones pero no incluy— una respuesta de autenticaci—n SAML.',
	),
	
	'title_CASERROR' => array(
		'en' => 'CAS Error',
	),
	'descr_CASERROR' => array(
		'en' => 'Error when communicating with the CAS server.',
	),

);