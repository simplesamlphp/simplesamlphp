<?php

require_once('../_include.php');

/* Make sure that the user has admin access rights. */
SimpleSAML\Utils\Auth::requireAdmin();

$config = SimpleSAML_Configuration::getInstance();

if ( !empty($_FILES['xmlfile']['tmp_name']) ) {
	$xmldata = file_get_contents($_FILES['xmlfile']['tmp_name']);
} elseif ( array_key_exists('xmldata', $_POST) ) {
	$xmldata = $_POST['xmldata'];
}

if ( !empty($xmldata) ) {
	\SimpleSAML\Utils\XML::checkSAMLMessage($xmldata, 'saml-meta');
	$entities = SimpleSAML_Metadata_SAMLParser::parseDescriptorsString($xmldata);

	/* Get all metadata for the entities. */
	foreach($entities as &$entity) {
		$entity = array(
			'shib13-sp-remote' => $entity->getMetadata1xSP(),
			'shib13-idp-remote' => $entity->getMetadata1xIdP(),
			'saml20-sp-remote' => $entity->getMetadata20SP(),
			'saml20-idp-remote' => $entity->getMetadata20IdP(),
			);

	}

	/* Transpose from $entities[entityid][type] to $output[type][entityid]. */
	$output = SimpleSAML\Utils\Arrays::transpose($entities);

	/* Merge all metadata of each type to a single string which should be
	 * added to the corresponding file.
	 */
	foreach($output as $type => &$entities) {

		$text = '';

		foreach($entities as $entityId => $entityMetadata) {

			if($entityMetadata === NULL) {
				continue;
			}

			/* Remove the entityDescriptor element because it is unused, and only
			 * makes the output harder to read.
			 */
			unset($entityMetadata['entityDescriptor']);

			$text .= '$metadata[' . var_export($entityId, TRUE) . '] = ' .
				var_export($entityMetadata, TRUE) . ";\n";
		}

		$entities = $text;
	}

} else {
	$xmldata = '';
	$output = array();
}


$template = new SimpleSAML_XHTML_Template($config, 'metadata-converter.php', 'admin');

$template->data['xmldata'] = $xmldata;
$template->data['output'] = $output;

$template->show();
