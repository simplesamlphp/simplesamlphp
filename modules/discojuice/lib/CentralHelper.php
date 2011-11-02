<?php

/**
 * ...
 */
class sspmod_discojuice_CentralHelper {
	
	public static function show($path = '/simplesaml/module.php/discojuice/discojuice/') {
			
		$djconfig = SimpleSAML_Configuration::getOptionalConfig('discojuicecentral.php');
		$config = SimpleSAML_Configuration::getInstance();
		
		
		$feed = new sspmod_discojuice_Feed();
		$metadata = json_decode($feed->read(), TRUE);	
		
		$t = new SimpleSAML_XHTML_Template($config, 'discojuice:central.tpl.php');
		$t->data['metadata'] = $metadata;
		$t->data['discojuice.options'] = $djconfig->getValue('discojuice.options');
		$t->data['discojuice.options']['discoPath'] = $path;
		$t->data['acl'] = $djconfig->getValue('acl');
		$t->show();
		
	}

}

