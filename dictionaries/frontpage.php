<?php

$lang = array(

	'intro' => array(
		'en' => '<strong>Congratulations</strong>, you have successfully installed simpleSAMLphp. This is the start page of your installation, where you will find links to test examples, diagnostics, metadata and even links to relevant documentation.',
		'no' => '<strong>Gratulerer</strong>, du har nå installert simpleSAMLphp. Dette er startsiden til din simpleSAMLphp installasjon, hvor du vil finne eksempler, diagnostikk, metadata og til og med lenker til relevant dokumentasjon.',
		'dk' => '<strong>Tillykke</strong>, du har nu installeret simpleSAMLphp. Dette er startsiden til installationen, hvor du vil finde eksempler, diagnostik, metadata og links til relevant dokumentation',
		'es' => '<strong>&iexcl;Felicidades!</strong>, ha instalado simpleSAMLphp con &eacute;xito. This &eacute;sta es la p&aacute;gina inicial de su instalaci&oacute;n, aqu&iacute; encontrar&aacute; enlaces a ejemplos de prueba, diagn&oacute;sticos, metadatos e incluso enlaces a la documentaci&oacute;n pertienente.',
		'fr' => '<strong>Félicitations</strong>, vous avez installé simpleSAMLphp avec succès.  Ceci est la page de démarrage de votre installation, où vous trouverez des liens vers des exemples, des pages de diagnostic, les métadata et même vers de la documentation.',
	),
	
	'useful_links_header' => array(
		'en' => 'Useful links for your installation',
		'no' => 'Nyttige lenker for denne installasjonen',
		'dk' => 'Nyttige links',
		'es' => 'Enalces &uacute;tiles para su instalaci&oacute;n',
		'fr' => 'Liens utiles pour votre installation',
	),
	'metadata_header' => array(
		'en' => 'Metadata',
		'no' => 'Metadata',
		'dk' => 'Metadata',
		'es' => 'Metadatos',
		'fr' => 'Métadata',
	),
	'doc_header' => array(
		'en' => 'Documentation',
		'no' => 'Dokumentasjon',
		'dk' => 'Dokumentation',
		'es' => 'Documentaci&oacute;n',
		'fr' => 'Documetnation',
	),
	'checkphp' => array(
		'en' => 'Checking your PHP installation',
		'no' => 'Sjekker din PHP installasjon',
		'dk' => 'Checker din PHP-installation',
		'es' => 'Verificaci&oacute;n de su instalaci&oacute;n de PHP',
		'fr' => 'Vérification de votre installation de PHP',
	),
	'about_header' => array(
		'en' => 'About simpleSAMLphp',
		'no' => 'Om simpleSAMLphp',
		'dk' => 'Om simpleSAMLphp',
		'es' => 'Sobre simpleSAMLphp',
		'fr' => 'À propos de simpleSAMLphp',
	),
	'about_text' => array(
		'en' => 'This simpleSAMLphp thing is pretty cool, where can I read more about it? You can find more information about <a href="http://rnd.feide.no/simplesamlphp">simpleSAMLphp at the Feide RnD blog</a> over at <a href="http://uninett.no">UNINETT</a>.',
		'no' => 'Yey! simpleSAMLphp virker jammen kult, hvor kan jeg finne ut mer om det? Du kan lese mer om simpleSAMLphp på <a href="http://rnd.feide.no/simplesamlphp">simpleSAMLphp sin hjemmeside</a>.',
		'dk' => 'Yes, det er cool! Hvor kan jeg læse mere om det? Gå til <a href="http://rnd.feide.no/simplesamlphp">simpleSAMLphp sin hjemmeside</a>',
		'es' => '&iexcl;Eh! Esto del simpleSAMLphp est&aacute; interesante, &iquest;d&oacute;nde puedo averiguar m&aacute;a? Hay m&aacute;s informaci&oacute;n sobre <a href="http://rnd.feide.no/simplesamlphp">simpleSAMLphp en el blog de I+D de Feide</a> en <a href="http://uninett.no">UNINETT</a>.',
		'fr' => 'Yeah! simpleSAMLphp est assez cool, où puis-je en lire plus à son sujet ?  Vous trouverez plus d\'informations sur  <a href="http://rnd.feide.no/simplesamlphp">simpleSAMLphp sur le blog de la R&amp;D de Feide</a> sur <a href=\"http://uninett.no\">UNINETT</a>.',
	),
	
	'required' => array(
		'en' => 'Required',
		'no' => 'Påkrevd',
		'dk' => 'Påkrævet',
		'fr' => 'Obligatoire',
	),
	'required_ldap' => array(
		'en' => 'Required for LDAP',
		'no' => 'Påkrevd for LDAP',
		'dk' => 'Påkrævet for LDAP',
		'fr' => 'Obligatoire pour LDAP',
	),
	'required_radius' => array(
		'en' => 'Required for Radius',
		'no' => 'Påkrevd for Radius',
		'dk' => 'Påkrævet for RADIUS',
		'fr' => 'Obligatoire pour Radius',
	),
	'optional' => array(
		'en' => 'Optional',
		'no' => 'Valgfritt',
		'dk' => 'Valgfrit',
		'fr' => 'Facultatif',
	),
	'reccomended' => array(
		'en' => 'Reccomended',
		'no' => 'Anbefalt',
		'dk' => 'Anbefalet',
		'fr' => 'Recommendé',
	),	
	
	'warnings' => array(
		'en' => 'Warnings',
		'no' => 'Advarsler',
		'dk' => 'Advarsler',
		'es' => 'Avisos',
		'fr' => 'Avertissements',
	),
	
	'warnings_https' => array(
		'en' => '<strong>You are not using HTTPS</strong> - encrypted communication with the user. Using simpleSAMLphp will works perfectly fine on HTTP for test purposes, but if you will be using simpleSAMLphp in a production environment, you should be running it on HTTPS. [ <a href="http://rnd.feide.no/content/simplesamlphp-maintenance-and-configuration">read more about simpleSAMLphp maintenance</a> ]',
		'no' => '<strong>Du benytter ikke HTTPS</strong> - kryptert kommunikasjon med brukeren. Det vil fungere utmerket å benytte simpleSAMLphp uten HTTPS til testformål, men dersom du skal bruke simpleSAMLphp i et produksjonsmiljø, vil vi sterkt anbefale å skru på sikker kommunikasjon med HTTPS. [ <a href="http://rnd.feide.no/content/simplesamlphp-maintenance-and-configuration">les mer i dokumentet: simpleSAMLphp maintenance</a> ]',
		'dk' => '<strong>Du benytter ikke HTTPS</strong>-krypteret kommunikation med brugeren. SimpleSAMLphp vil fungere uden problemer med HTTP alene, men hvis du anvende systemet i produktionssystemer, anbefales det stærkt at benytte sikker kommunikation i form af HTTPS. [ <a href="http://rnd.feide.no/content/simplesamlphp-maintenance-and-configuration">læs mere i dokumentet: simpleSAMLphp maintenance</a> ] ',
		'fr' => '<strong>Vous n\'utilisez pas HTTPS</strong>, communications chiffrées avec l\'utilisateur.  Utiliser simpleSAMLphp marchera parfaitement avec HTTP pour des tests, mais si vous voulez l\'utiliser dans un environnement de production, vous devriez utiliser HTTPS. [  <a href="http://rnd.feide.no/content/simplesamlphp-maintenance-and-configuration">lire plus sur la maintenance de simpleSAMLphp</a> ]',
	),
	
	
	'link_saml2example' => array(
		'en' => 'SAML 2.0 SP example - test logging in through your IdP',
		'no' => 'SAML 2.0 SP eksempel - test innlogging med SAML 2.0 via din IdP',
		'dk' => 'SAML 2.0 SP eksempel - test indlogning med SAML 2.0 via din IdP',
		'fr' => 'SP SAML 2.0 d\'example - tester l\'identification via votre IdP',
	),
	'link_shib13example' => array(
		'en' => 'Shibboleth 1.3 SP example - test logging in through your Shib IdP',
		'no' => 'Shibboleth 1.3 SP eksempel - test innlogging med Shibboleth 1.3 via din IdP',
		'dk' => 'Shibboleth 1.3 SP eksempel - test indlogning med Shibboleth 1.3 via din IdP',
		'fr' => 'SP Shibboleth 1.3 d\'example - tester l\'identification via votre IdP',
	),
	'link_openidprovider' => array(
		'en' => 'OpenID Provider site - Alpha version (test code)',
		'no' => 'OpenID Provider side - Alpha versjon (testkode)',
		'dk' => 'OpenID Provider side - Alpha version (testkode)',
		'fr' => 'Site de fournisseur OpenID - version alpha (code de test)',
	),
	'link_diagnostics' => array(
		'en' => 'Diagnostics on hostname, port and protocol',
		'no' => 'Diagnostiser hostnavn, port og protokoll',
		'dk' => 'Diagnostisør hostnavn, port og protokol',
		'fr' => 'Diagnostics sur le nom d\'hôte, le port et le protocole',
	),
	'link_phpinfo' => array(
		'en' => 'PHPinfo',
		'no' => 'PHPinfo',
		'dk' => 'PHPinfo',
		'fr' => 'PHPinfo',
	),
	
	'link_meta_overview' => array(
		'en' => 'Meta data overview for your installation. Diagnose your meta data files',
		'no' => 'Oversikt over metadata for din instalasjon. Diagnostiser metadatafilene her.',
		'dk' => 'Oversigt over metadata for din installation. Check metadatafilerne her',
		'fr' => 'Aperçu des métadata de votre installation.  Diagnostic de vos fichiers de métadata.',
	),
	
	'link_meta_saml2sphosted' => array(
		'en' => 'Hosted SAML 2.0 Service Provider Metadata (automatically generated)',
		'no' => 'Hosted SAML 2.0 Service Provider Metadata (automatisk generert)',
		'fr' => 'Métadata du fournisseur de service SAML 2.0 (automatiquement générée)',
	),
	'link_meta_saml2idphosted' => array(
		'en' => 'Hosted SAML 2.0 Identity Provider Metadata (automatically generated)',
		'no' => 'Hosted SAML 2.0 Identity Provider Metadata (automatisk generert)',
		'dk' => 'Hosted SAML 2.0 Identity Provider Metadata (automatisk genereret)',
		'fr' => 'Métadata du fournisseur d\'identités SAML 2.0 (automatiquement générée)',
	),
	'link_meta_shib13sphosted' => array(
		'en' => 'Hosted Shibboleth 1.3 Service Provider Metadata (automatically generated)',
		'no' => 'Hosted Shibboleth 1.3 Service Provider Metadata (automatisk generert)',
		'dk' => 'Hosted Shibboleth 1.3 Service Provider Metadata (automatisk genereret)',
		'fr' => 'Métadata du fournisseur de service Shibboleth 1.3 (automatiquement générée)',
	),
	'link_meta_shib13idphosted' => array(
		'en' => 'Hosted Shibboleth 1.3 Identity Provider Metadata (automatically generated)',
		'no' => 'Hosted Shibboleth 1.3 Identity Provider Metadata (automatisk generert)',
		'dk' => 'Hosted Shibboleth 1.3 Identity Provider Metadata (automatisk genereret)',
		'fr' => 'Métadata du fournisseur d\'identités Shibboleth 1.3 (automatiquement générée)',
	),
	'link_xmlconvert' => array(
		'en' => 'XML to simpleSAMLphp metadata converter',
		'no' => 'XML til simpleSAMLphp metadata oversetter',
		'dk' => 'XML til simpleSAMLphp metadata oversætter',
		'fr' => 'Convertiseur de métadata XML vers simpleSAMLphp',
	),
	
	
	'link_doc_install' => array(
		'en' => 'Installing simpleSAMLphp',
		'fr' => 'Installation de simpleSAMLphp',
	),
	'link_doc_sp' => array(
		'en' => 'Using simpleSAMLphp as a Service Provider',
		'fr' => 'Utilisation de simpleSAMLphp comme fournisseur de service',
	),
	'link_doc_idp' => array(
		'en' => 'Using simpleSAMLphp as an Identity Provider',
		'fr' => 'Utilisation de simpleSAMLphp comme fournisseur d\'identités',
	),
	'link_doc_shibsp' => array(
		'en' => 'Configure Shibboleth 1.3 SP to work with simpleSAMLphp IdP',
		'fr' => 'Configurer un SP Shibboleth 1.3 pour fonctionner avec l\'IdP simpleSAMLphp',
	),
	'link_doc_googleapps' => array(
		'en' => 'simpleSAMLphp as an IdP for Google Apps for Education',
		'fr' => 'simpleSAMLphp comme IdP pour les Google Apps for Education',
	),
	'link_doc_advanced' => array(
		'en' => 'simpleSAMLphp Advanced Features',
		'fr' => 'Fonctionnalités avancées de simpleSAMLphp',
	),
	'link_doc_maintenance' => array(
		'en' => 'simpleSAMLphp Maintenance and Configuration',
		'fr' => 'Maintenance et configuration de simpleSAMLphp',
	),
	
	
	
);