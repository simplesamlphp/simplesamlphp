<?php

// consentadmin dictionary

/*
	'' => array(
		'en' => '',
		'no' => '',
		'nn' => '',
        'da' => '',
		'es' => '',
		'fr' => '',
		'de' => '',
		'nl' => '',
		'lu' => '',
		'sl' => '',
	),


* 
* */


$lang = array(
			  'sp_empty_name' => array( 
									   'en' => '(name not specified)', 
									   'no' => '(namn ikke spesifisert)', 
									   'nn' => '(name not specified)', 
									   'da' => '(navn ikke angivet)', 
									   'en' => '(name not specified)', 
									   'fr' => '(name not specified)', 
									   'de' => '(name nicht definiert)', 
									   'nl' => '(name not specified)', 
									   'lu' => '(name not specified)', 
									   'sl' => '(name not specified)', 
									  ), 
			  'sp_empty_description' => array( 
											  'en' => '(no description)', 
											  'no' => '(ingen beskrivelse)', 
											  'nn' => '(no description)', 
											  'da' => '(ingen beskrivelse)', 
											  'es' => '(no description)', 
											  'fr' => '(no description)', 
											  'de' => '(no description)', 
											  'nl' => '(no description)', 
											  'lu' => '(no description)', 
											  'sl' => '(no description)', 
											 ),

			  // WAYF: Additional attributes START

			  'attribute_org' => array(
									   'en' => 'Organisation',
									   'da' => 'Organisation',
									  ),

			  'added' => array(
							   'en' => 'Consent Added',
							   'da' => 'Samtykke givet',
							  ),

			  'removed' => array(
								 'en' => 'Consent Removed',
								 'da' => 'Samtykke slettet',
	),

	'updated' => array(
		'en' => 'Consent Updated',
		'da' => 'Samtykke Opdateret!!!',
	),

	'unknown' => array(
		'en' => 'Unknown ...',
		'da' => 'Ukendt ...',
	),

	'attribute_id' => array(
		'en' => 'Identity',
		'da' => 'Identitet',
	),

	'attribute_injected' => array(
		'en' => 'Injected attribut',
		'da' => 'Injiceret attribut',
	),
	
// WAYF: Additional attributes END
	

// Text

	'show' => array(
		'en' => 'Show',
        'da' => 'Vis',
	),

	'hide' => array(
		'en' => 'Hide',
        'da' => 'Skjul',
	),
	
	'attributes_text' => array(
		'en' => 'attributes',
        'da' => 'attributter',
	),
	

	'consentadmin_header' => array(
		'en' => 'Consent Administration',
        'da' => 'Administrer dine samtykker',
	),

	'consentadmin_description1' => array(
		'en' => 'Here you can view and edit your consent for the Service Providers.',

        'da' => '
 WAYF videregiver kun oplysninger til eksterne tjenester, hvis du giver dit samtykke til det. Hvilke oplysninger det drejer sig om, varierer alt efter hvad tjenesteudbyderen har behov for. Det kan for eksempel være:
<ul>
<li>	Dit navn
<li>	Din e-mail-adresse
<li>	Din institution
<li>	Etc.
</ul>

Hvis du sætter et flueben ud for <b>Husk dette samtykke</b>, vil du ikke blive spurgt, næste gang du besøger tjenesteudbyderen. 
Så husker WAYF, at du allerede har givet samtykke til at videregive oplysninger til tjenesteudbyderen. 
<p>Nedenfor er opført de tjenester, som du for øjeblikket har givet løbende samtykke til:</a>
', //da
		),

		'consentadmin_description2' => array(
		'en' => '
<h3>How to delete your consent</h3>
Uncheck the box corresponding to the service provider

<h3>Links</h3>
<ul>
<li><a href="https://www.wayf.dk">Start</a> </li>

<li><a href="https://www.wayf.dk/FAQ">FAQ</a> </li>
</ul>
', // en
		        'da' => '
<h3>Sådan sletter du et samtykke</h3>
Fjern fluebenet ud for tjenesten, samtykket tilhører.
<h3>Hvilke data gemmer WAYF om dig?</h3>
<ul>
<li>	Når du giver dit samtykke, henter WAYF dine oplysninger fra din institution og sender de relevante videre til tjenesteudbyderen
<li>	Ingen af oplysningerne gemmes af WAYF
<li>	Hvis du har bedt WAYF huske dit samtykke, gemmes personhenførbare data heller ikke hos WAYF. Oplysningen om, at du har givet dit samtykke, gemmes på en ikke-personhenførbar måde
</ul>

<h3>Hvilke rettigheder har du?</h3>
Du har ret til at trække et samtykke tilbage.
<h3>Hvor længe gemmes dine samtykker?</h3>
Et samtykke slettes tre år efter, at du sidst har benyttet det.
<h3>Hvordan beskyttes mine oplysninger?</h3>
WAYF foretager behandlinger af personoplysninger i henhold til persondataloven (lov nr. 429 af 31. maj 2000 med senere ændringer). Du kan læse nærmere om registreredes rettigheder i persondatalovens afsnit III.
<a href="http://www.datatilsynet.dk/lovgivning/persondataloven/">Persondataloven</a>

<h3>Links</h3>
<ul>
<li><a href="https://www.wayf.dk">Start</a> </li>

<li><a href="https://www.wayf.dk/FAQ">FAQ</a> </li>
</ul>
', // da
	),	
		
		
	'login' => array(
		'en' => 'login',
        'da' => 'login',
	),
		   		
	'service_providers_for' => array(
		'en' => 'Service Providers for',
        'da' => 'Service Providers for',
		),
		
  
  
  'service_provider_header' => array(
		'en' => 'Service Provider',
        'da' => 'Service Provider',
		),
		
	'status_header' => array(
		'en' => 'Consent status',
        'da' => 'Samtykke status',		
		),
		
	'show_hide_attributes' => array(
		'en' => 'show/hide attributes',
        'da' => 'vis/skjul attributter',		
		),
    'consentadmin_purpose' => array(
        'en' => 'The purpose of the service is',
        'da' => 'Formålet med tjenesten er',
    ),        
);


