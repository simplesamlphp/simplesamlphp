<?php

$lang = array(
	'front_header' => array (
		'en' => 'MetaShare',
	),
	'front_desc' => array (
		'en' => 'This is a metadata sharing service. It allows you to add dynamically generated metadata to a shared store.',
	),
	'add_title' => array (
		'en' => 'Add entity',
	),
	'add_desc' => array (
		'en' => 'Add new or updated metadata by specifying the URL of the metadata. This URL must match the entity identifier of the entity described in the metadata.',
	),
	'add_entityid' => array (
		'en' => 'Entity identifier of the entity:',
	),
	'add_do' => array (
		'en' => 'Add',
	),
	'downloadall_desc' => array (
		'en' => 'It is possible to download all the metadata as a single XML file. This file will contain a single EntitiesDescriptor which contains all the entities which are atted to this MetaShare. The EntitiesDescriptor may be signed by this MetaShare if that is enabled in the configuration.',
	),
	'downloadall_link' => array (
		'en' => 'Download all metadata',
	),
	'entities_title' => array (
		'en' => 'Entities',
	),
	'entities_desc' => array (
		'en' => 'This is a list of all the entities which are currently stored in this MetaShare. Click on a link to download the metadata of the given entity.',
	),
	'entities_empty' => array (
		'en' => 'No entities are currently stored in this MetaShare.',
	),
	'text' => array (
		'en' => 'text',
	),
	'addpage_header' => array (
		'en' => 'Add metadata',
	),
	'addpage_ok' => array (
		'en' => 'The metadata from "%URL%" was successfylly added.',
	),
	'addpage_nourl' => array (
		'en' => 'No URL parameter given.',
	),
	'addpage_invalidurl' => array (
		'en' => 'Invalid URL/entity id to metadata. The entity id should be a valid http: or https: URL. The URL you gave was "%URL%".',
	),
	'addpage_nodownload' => array (
		'en' => 'Unable to download metadata from "%URL%".',
	),
	'addpage_invalidxml' => array (
		'en' => 'Malformed XML in metadata. The URL you gave was "%URL%".',
	),
	'addpage_notentitydescriptor' => array (
		'en' => 'The root node of the metadata was not an EntityDescriptor element. The URL you gave was "%URL%".',
	),
	'addpage_entityid' => array (
		'en' => 'The entity identifier in the metadata did not match the URL of the metadata ("%URL%").',
	),
	'addpage_validation' => array (
		'en' => 'XML validation of the metadata from "%URL%" failed:',
	),
	'addpage_gofront' => array (
		'en' => 'Go to metadata list',
	),

);


?>