<?php

$lang = array(


	'intro' => array(
		'en' => '<strong>Congratulations</strong>, you have successfully installed simpleSAMLphp. This is the start page of your installation, where you will find links to test examples, diagnostics, metadata and even links to relevant documentation.',
		'no' => '<strong>Gratulerer</strong>, du har nå installert simpleSAMLphp. Dette er startsiden til din simpleSAMLphp installasjon, hvor du vil finne eksempler, diagnostikk, metadata og til og med lenker til relevant dokumentasjon.',
	),
	
	'useful_links_header' => array(
		'en' => 'Useful links for your installation',
		'no' => 'Nyttige lenker for denne installasjonen',
	),
	'metadata_header' => array(
		'en' => 'Metadata',
		'no' => 'Metadata',
	),
	'doc_header' => array(
		'en' => 'Documentation',
		'no' => 'Dokumentasjon',
	),
	'checkphp' => array(
		'en' => 'Checking your PHP installation',
		'no' => 'Sjekker din PHP installasjon',
	),
	'about_header' => array(
		'en' => 'About simpleSAMLphp',
		'no' => 'Om simpleSAMLphp',
	),
	'about_text' => array(
		'en' => 'This simpleSAMLphp thing is pretty cool, where can I read more about it? You can find more information about <a href="http://rnd.feide.no/simplesamlphp">simpleSAMLphp at the Feide RnD blog</a> over at <a href="http://uninett.no">UNINETT</a>.',
		'no' => 'Yey! simpleSAMLphp virker jammen kult, hvor kan jeg finne ut mer om det? Du kan lese mer om simpleSAMLphp på <a href="http://rnd.feide.no/simplesamlphp">simpleSAMLphp sin hjemmeside</a>.',
	),
	
	'required' => array(
		'en' => 'Required',
		'no' => 'Påkrevd',
	),
	'required_ldap' => array(
		'en' => 'Required for LDAP',
		'no' => 'Påkrevd for LDAP',
	),
	'required_radius' => array(
		'en' => 'Required for Radius',
		'no' => 'Påkrevd for Radius',
	),
	'optional' => array(
		'en' => 'Optional',
		'no' => 'Valgfritt',
	),
	'reccomended' => array(
		'en' => 'Reccomended',
		'no' => 'Anbefalt',
	),	
	
	'warnings' => array(
		'en' => 'Warnings',
		'no' => 'Advarsler',
	),
	
	'warnings_https' => array(
		'en' => '<strong>You are not using HTTPS</strong> - encrypted communication with the user. Using simpleSAMLphp will works perfectly fine on HTTP for test purposes, but if you will be using simpleSAMLphp in a production environment, you should be running it on HTTPS. [ <a href="http://rnd.feide.no/content/simplesamlphp-maintenance-and-configuration">read more about simpleSAMLphp maintenance</a> ]',
		'no' => '<strong>Du benytter ikke HTTPS</strong> - kryptert kommunikasjon med brukeren. Det vil fungere utmerket å benytte simpleSAMLphp uten HTTPS til testformål, men dersom du skal bruke simpleSAMLphp i et produksjonsmiljø, vil vi sterkt anbefale å skru på sikker kommunikasjon med HTTPS. [ <a href="http://rnd.feide.no/content/simplesamlphp-maintenance-and-configuration">les mer i dokumentet: simpleSAMLphp maintenance</a> ]',
	),
	
	
	'link_saml2example' => array(
		'en' => 'SAML 2.0 SP example - test logging in through your IdP',
		'no' => 'SAML 2.0 SP eksempel - test innlogging med SAML 2.0 via din IdP',
	),
	'link_shib13example' => array(
		'en' => 'Shibboleth 1.3 SP example - test logging in through your Shib IdP',
		'no' => 'Shibboleth 1.3 SP eksempel - test innlogging med Shibboleth 1.3 via din IdP',
	),
	'link_openidprovider' => array(
		'en' => 'OpenID Provider site - Alpha version (test code)',
		'no' => 'OpenID Provider side - Alpha versjon (testkode)',
	),
	'link_diagnostics' => array(
		'en' => 'Diagnostics on hostname, port and protocol',
		'no' => 'Diagnostiser hostnavn, port og protokoll',
	),
	'link_phpinfo' => array(
		'en' => 'PHPinfo',
		'no' => 'PHPinfo',
	),
	
	'link_meta_overview' => array(
		'en' => 'Meta data overview for your installation. Diagnose your meta data files',
		'no' => 'Oversikt over metadata for din instalasjon. Diagnostiser metadatafilene her.'
	),
	
	'link_meta_saml2sphosted' => array(
		'en' => 'Hosted SAML 2.0 Service Provider Metadata (automatically generated)',
		'no' => 'Hosted SAML 2.0 Service Provider Metadata (automatisk generert)',
	),
	'link_meta_saml2idphosted' => array(
		'en' => 'Hosted SAML 2.0 Identity Provider Metadata (automatically generated)',
		'no' => 'Hosted SAML 2.0 Identity Provider Metadata (automatisk generert)',
	),
	'link_meta_shib13sphosted' => array(
		'en' => 'Hosted Shibboleth 1.3 Service Provider Metadata (automatically generated)',
		'no' => 'Hosted Shibboleth 1.3 Service Provider Metadata (automatisk generert)',
	),
	'link_meta_shib13idphosted' => array(
		'en' => 'Hosted Shibboleth 1.3 Identity Provider Metadata (automatically generated)',
		'no' => 'Hosted Shibboleth 1.3 Identity Provider Metadata (automatisk generert)',
	),
	'link_xmlconvert' => array(
		'en' => 'XML to simpleSAMLphp metadata converter',
		'no' => 'XML til simpleSAMLphp metadata oversetter',
	),
	
	
	'link_doc_install' => array(
		'en' => 'Installing simpleSAMLphp',
	),
	'link_doc_sp' => array(
		'en' => 'Using simpleSAMLphp as a Service Provider',
	),
	'link_doc_idp' => array(
		'en' => 'Using simpleSAMLphp as an Identity Provider',
	),
	'link_doc_shibsp' => array(
		'en' => 'Configure Shibboleth 1.3 SP to work with simpleSAMLphp IdP',
	),
	'link_doc_googleapps' => array(
		'en' => 'simpleSAMLphp as an IdP for Google Apps for Education',
	),
	'link_doc_advanced' => array(
		'en' => 'simpleSAMLphp Advanced Features',
	),
	'link_doc_maintenance' => array(
		'en' => 'simpleSAMLphp Maintenance and Configuration'
	),
	
	
	
);