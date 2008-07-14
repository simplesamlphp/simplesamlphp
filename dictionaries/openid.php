<?php

$lang = array(
	'list_trusted_sites' => array (
		'en' => 'List of trusted sites',
		'sv' => 'Lista med godkända sajter',
		'sl' => 'Seznam zaupanja vrednih strani.',
		'hu' => 'Megbízható oldlak listája',
	),
	'about_link' => array (
		'en' => 'About simpleSAMLphp OpenID',
		'sv' => 'Om simpleSAMLphp OpenID',
		'sl' => 'O simpleSAMLphp OpenID',
		'hu' => 'A simpleSAMLphp alapú OpenID-ról',
	),
	'welcome' => array (
		'en' => 'Welcome to the simpleSAMLphp OpenID provider.',
		'sv' => 'Välkommen till simpleSAMLphp OpenID-leverantör',
		'sl' => 'Dobrodošli v simpleSAMLphp OpenID ponudnik.',
		'hu' => 'Köszöntjük a simpleSAMLphp-val üzemletett OpenID kiszolgálókon.',
	),
	'howtouse' => array (
		'en' => 'To use this server, you will have to set up a URL to use as an identifier. Insert the following markup into the <code>&lt;head&gt;</code> of the HTML document at that URL:',
		'sv' => 'För att använda denna server måste du ha satt upp en URL som identifierare. Lägg till följande märktagg i <code>&lt;head&gt;</code> på webbsidan som finns på URLen:',
		'sl' => 'Pred uporabo tega strežnika morate vzpostaviti URL, ki se bo uporabil kot identifikator.  Vstavite naslednjo oznako <code>&lt;head&gt;</code> v HTML dokument na URLju:',
		'hu' => 'A szerverhasználatához, be kell állítani egy azonosító URL-t. A következőt kell beállítani a HTML dokumentum <code>&lt;head&gt;</code> tegjébe (fejlécébe):',
	),
	'loggedinas' => array (
		'en' => 'You are now logged in as %USERID%',
		'sv' => 'Du är nu inloggad som %USERID%',
		'sl' => 'Prijavljeni ste kot %USERID%',
		'hu' => 'A %USERID% névvel van bejelentkezve',
	),
	'login' => array (
		'en' => 'Login',
		'sv' => 'Logga in',
		'sl' => 'Prijava',
		'hu' => 'Belépés',
	),
	'howtouse_cont' => array (
		'en' => 'Then configure this server so that you can log in with that URL. Once you have configured the server, and marked up your identity URL, you can verify that it is working by using the %SITE% %TOOL%:',
		'sv' => 'Konfigurera sedan denna server så du kan logga in med den URLen. När du har konfigurerat servern och märkt upp din identiets-URL kan du verifiera att det fungerar genom att använda %SITE% %TOOL%:',
		'sl' => 'Nato nastavite ta strežnik tako, da se boste lahko prijavili s tem URL naslovom. Pravilno delovanje lahko preverite s %SITE% %TOOL%:',
		'hu' => 'Miután beállította a szervet ezen az URL-n bejelentkezhet. Először beállítja a szervert és az azonosító URL-t, azonosíthatja magát a következő használatával %SITE% %TOOL%:',
	),
	'checkup_tool' => array (
		'en' => 'OpenID Checkup tool',
		'sv' => 'OpenID kontrollverktyg',
		'sl' => 'OpenID orodje za preverjanje',
		'hu' => 'OpenID ellenőrző eszköz',
	),
	'openid_url' => array (
		'en' => 'OpenID URL:',
		'sv' => 'OpenID URL:',
		'sl' => 'OpenID URL:',
		'hu' => 'OpenID URL:',
	),
	'check' => array (
		'en' => 'Check',
		'sv' => 'Kontrollera',
		'sl' => 'Preveri',
		'hu' => 'Ellenőriz',
	),
	'confirm_question' => array (
		'en' => 'Do you wish to confirm your identity URL (%OPENIDURL%) with %SITEURL%?',
		'sv' => 'Vill du bekräfta din URL (%OPENIDURL%) med %SITEURL%?',
		'sl' => 'Ali želite potrditi svoj entitetni URL (%OPENIDURL%) z %SITEURL%?',
		'hu' => 'Kívánja megerősíteni a személyazonosságát igazólő URL-t (%OPENIDURL%) a %SITEURL% segítségével?',
	),
	'remember' => array (
		'en' => 'Remember this decision',
		'sv' => 'Spara detta beslut',
		'sl' => 'Zapomni si to odločitev',
		'hu' => 'Emlékezzen erre a választásra',
	),
	'confirm' => array (
		'en' => 'Confirm',
		'sv' => 'Bekräfta',
		'sl' => 'Potrdi',
		'hu' => 'Megerősít',
	),
	'notconfirm' => array (
		'en' => 'Do not confirm',
		'sv' => 'Bekräfta inte',
		'sl' => 'Ne potrdi',
		'hu' => 'Nem erősíti meg',
	),
	'trustlist_desc' => array (
		'en' => 'These decisions have been remembered for this session. All decisions will be forgotten when the session ends.',
		'sv' => 'Dessa beslut har sparats för denna session. Alla beslut kommer att glömmas när sessionen avslutas.',
		'sl' => 'Te odločitve veljajo samo v trenutni seji. Ko se bo seja zakjučila, bodo odločitve izbrisane.',
		'hu' => 'Ezekre a válaszokra emlékezzen a munkamenet folyamán. Az összes változtatás elvésza munkamenet befelyeztével.',
	),
	'trustlist_trustedsites' => array (
		'en' => 'Trusted Sites',
		'sv' => 'Godkända sajter',
		'sl' => 'Zaupanja vredne strani',
		'hu' => 'Megbízható oldalak',
	),
	'trustlist_untrustedsites' => array (
		'en' => 'Untrusted Sites',
		'sv' => 'Ej godkända sajter',
		'sl' => 'Nepreverjene strani',
		'hu' => 'Megbízhatatlanoldlak',
	),
	'trustlist_remove' => array (
		'en' => 'Remove Selected',
		'sv' => 'Ta bort vald',
		'sl' => 'Odstrani izbiro',
		'hu' => 'Kijelölt eltávolítása',
	),
	'trustlist_refresh' => array (
		'en' => 'Refresh List',
		'sv' => 'Uppdatera listan',
		'sl' => 'Osveži seznam',
		'hu' => 'Lista frissítése',
	),
	'trustlist_forget' => array (
		'en' => 'Forget All',
		'sv' => 'Glöm alla',
		'sl' => 'Izbriši vse',
		'hu' => 'Mind el felejt',
	),
	'trustlist_nosites' => array (
		'en' => 'No sites are remembered for this session. When you authenticate with a site, you can choose to add it to this list by choosing <q>Remember this decision</q>.',
		'sv' => 'Inga sajter är sparade i denna session. När du loggar in på en sajt kan du välja om du ska lägga den i listan genom att välja <q>Spara detta beslut</q>.',
		'sl' => 'Nobena stran ni bila shranjena za to sejo. Shranite jo lahko med prijavo na strani.',
	),

);


?>