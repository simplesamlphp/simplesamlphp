<?php

$lang = array(
	'front_header' => array (
		'en' => 'MetaShare',
		'sv' => 'MetaShare',
		'sl' => 'MetaShare',
		'hu' => 'Metaadat megosztás',
	),
	'front_desc' => array (
		'en' => 'This is a metadata sharing service. It allows you to add dynamically generated metadata to a shared store.',
		'sv' => 'Detta delningsjänsten för metadata. Den tillåter att du lägger till dynamiskt skapad metadata till ett delat datalager.',
		'sl' => 'MetaShare je storitev souporabe metapodatkov. Omogoča dodajanje dinamično generiranih metapodatkov v skladišče za skupno rabo.',
		'hu' => 'Ez a metaadat megosztó szolgáltatás. Dinamikusan generált metaadatok megosztott tárolását teszi lehetővé.',
	),
	'add_title' => array (
		'en' => 'Add entity',
		'sv' => 'Lägg till entitet',
		'sl' => 'Dodaj entiteto',
		'hu' => 'Bejegyzés hozzáadása',
	),
	'add_desc' => array (
		'en' => 'Add new or updated metadata by specifying the URL of the metadata. This URL must match the entity identifier of the entity described in the metadata.',
		'sv' => 'Läg till ny eller uppdatera metadata genom att ange URL för metadatat. URLen måste matcha entitetsidentifieraren för entiteten som är beskriven i metatdatat.',
		'sl' => 'Dodajte nove ali popravljene metapodatke tako, da podate njen URL. URL se mora ujemati z identifikatorjem entitete.',
	),
	'add_entityid' => array (
		'en' => 'Entity identifier of the entity:',
		'sv' => 'Entitetsidentifierare för entiteten:',
		'sl' => 'Identifikator entete:',
		'hu' => 'Bejegyzés azonosítója:',
	),
	'add_do' => array (
		'en' => 'Add',
		'sv' => 'Lägg till',
		'sl' => 'Dodaj',
		'hu' => 'Hozzáadás',
	),
	'downloadall_desc' => array (
		'en' => 'It is possible to download all the metadata as a single XML file. This file will contain a single EntitiesDescriptor which contains all the entities which are atted to this MetaShare. The EntitiesDescriptor may be signed by this MetaShare if that is enabled in the configuration.',
		'sv' => 'Det är möjligt att hämta all metadata som en enda XML-fil. Denna fil kommer innehålla en enda EntitiesDescriptor som innehåller alla enteiteter som finns lagrade i denna MetaShare. EntitiesDescriptor kan vara signerade av MetaShare om detta är aktiverat i konfgiurationen för MetaShare.',
		'sl' => 'Celotne metapodatke si lahko prenesete kot eno XML datoteko. Ta bo vsebovala deskriptor entitet in vse entitete, ki so shranjene v MetaShareu. Deskriptor entitet je lahko digitalno podpisan, če je ta možnost v nastavitvah omogočena.',
	),
	'downloadall_link' => array (
		'en' => 'Download all metadata',
		'sv' => 'Hämta alla metadata',
		'sl' => 'Prenesi vse metapodatke k sebi.',
		'hu' => 'Összes metaadat letöltése',
	),
	'entities_title' => array (
		'en' => 'Entities',
		'sv' => 'Entiteter',
		'sl' => 'Entitete',
		'hu' => 'Bejegyzések',
	),
	'entities_desc' => array (
		'en' => 'This is a list of all the entities which are currently stored in this MetaShare. Click on a link to download the metadata of the given entity.',
		'sv' => 'Detta är en lista över alla entiteter som förnärvarande finns lagrades i denna MetaShare. Klicka på aktuell länk för att hämta för att hämta metadata för en viss entitet.',
		'sl' => 'To je seznam vseh entitet, ki so trenutnotno shranjene v tem MetaShareu. S klikom na povezavo (entiteto), lahko prenesete njeno metadato.',
	),
	'entities_empty' => array (
		'en' => 'No entities are currently stored in this MetaShare.',
		'sv' => 'Det finns förnärvarande inga eniteter lagrade i denna MetaShare.',
		'sl' => 'V MetaShare trenutno ni shranjene nobene entitete.',
		'hu' => 'Egyetlen bejegyzés sincs a Mataadat megosztóban',
	),
	'text' => array (
		'en' => 'text',
		'sv' => 'txt',
		'sl' => 'Besedilo',
		'hu' => 'szöveg',
	),
	'addpage_header' => array (
		'en' => 'Add metadata',
		'sv' => 'Lägg till metadata',
		'sl' => 'Dodaj metapodatke',
		'hu' => 'Metaadat hozzáadás',
	),
	'addpage_ok' => array (
		'en' => 'The metadata from "%URL%" was successfylly added.',
		'sv' => 'Metadata från "%URL%" har lagts till.',
		'sl' => 'Metapodatki z naslova "%URL%" so bili uspešno dodani.',
		'hu' => 'A "%URL%" metaadat sikeresen hozzálett adva.',
	),
	'addpage_nourl' => array (
		'en' => 'No URL parameter given.',
		'sv' => 'Ingen URL angavs.',
		'sl' => 'Noben URL parameter ni bil podan.',
		'hu' => 'Nem lett URL megadva!',
	),
	'addpage_invalidurl' => array (
		'en' => 'Invalid URL/entity id to metadata. The entity id should be a valid http: or https: URL. The URL you gave was "%URL%".',
		'sv' => 'Felaktig URL/Entitetsidentifierare för metadata. Entitetsidentifieraren ska vara en giltig http- eller https-adress (URL). Adressen du angav var "%URL%".',
		'sl' => 'Napačen URL ali ID entitete, le-ta mora biti veljaven http ali https naslov. Posredovali ste naslednji URL: "%URL%".',
	),
	'addpage_nodownload' => array (
		'en' => 'Unable to download metadata from "%URL%".',
		'sv' => 'Kunde inte hämta metadata från "%URL%".',
		'sl' => 'Prenos metapodatkov z naslova "%URL%" ni mogoč.',
		'hu' => 'Nem lehet metaadatot letölteni a "%URL%" helyről.',
	),
	'addpage_invalidxml' => array (
		'en' => 'Malformed XML in metadata. The URL you gave was "%URL%".',
		'sv' => 'Felaktigt formaterad XML i metadata. Adressen du angav var "%URL%".',
		'sl' => 'Napaka v strukturi XML metapodatkov. URL, ki ste ga posredovali "%URL%".',
		'hu' => 'Hibás XML formátumú metaadat. Ez a "%URL%" lett megadva.',
	),
	'addpage_notentitydescriptor' => array (
		'en' => 'The root node of the metadata was not an EntityDescriptor element. The URL you gave was "%URL%".',
		'sv' => 'Toppnoden av metadatat var inte en EntityDescriptor. Adressen du angav var "%URL%".',
		'sl' => 'Korensko vozlišče (root node) metapodakov ni element tipa entitetni deskriptor. URL, ki ste ga posredovali "%URL%".',
	),
	'addpage_entityid' => array (
		'en' => 'The entity identifier in the metadata did not match the URL of the metadata ("%URL%").',
		'sv' => 'Entitetsidentifieraren i metadatat stämmer inte överens med adressen för metadatat ("%URL%").',
		'sl' => 'Identifikator entitene se ne ujema z URLjem metapodatkov ("%URL%").',
	),
	'addpage_validation' => array (
		'en' => 'XML validation of the metadata from "%URL%" failed:',
		'sv' => 'XML-valideringen av metatdatat från "%URL%" misslyckades:',
		'sl' => 'XML validacija metapodatkov z naslova "%URL%" je spodletela:',
		'hu' => 'A "%URL%" címről származó metaadat XML formátuma hibás:',
	),
	'addpage_gofront' => array (
		'en' => 'Go to metadata list',
		'sv' => 'Gå till metadatalistan',
		'sl' => 'Pojdi na seznam metapodatkov',
		'hu' => 'Tovább a metaadat listához',
	),

);


?>