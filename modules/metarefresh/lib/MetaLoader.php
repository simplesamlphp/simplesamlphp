<?php
/*
 * @author Andreas Åkre Solberg <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_metarefresh_MetaLoader {


	private $metadata;
	private $expire;

	/**
	 * Constructor
	 *
	 * @param array $sources 	Sources...
	 * @param 
	 */
	public function __construct($expire = NULL) {
		$this->expire = $expire;	
		$this->metadata = array();
	}

	/**
	 * This function processes a SAML metadata file.
	 *
	 * @param $src  Filename of the metadata file.
	 */
	public function loadSource($source) {
		
		$entities = array();
		try {
			$entities = SimpleSAML_Metadata_SAMLParser::parseDescriptorsFile($source['src']);
		} catch(Exception $e) {
			SimpleSAML_Logger::warning('metarefresh: Failed to retrieve metadata. ' . $e->getMessage());
		}
		foreach($entities as $entity) {
			if(array_key_exists('validateFingerprint', $source) && $source['validateFingerprint'] !== NULL) {
				if(!$entity->validateFingerprint($source['validateFingerprint'])) {
					SimpleSAML_Logger::info('Skipping "' . $entity->getEntityId() . '" - could not verify signature.' . "\n");
					continue;
				}
			}
			
			$template = NULL;
			if (array_key_exists('template', $source)) $template = $source['template'];
			
			$this->addMetadata($source['src'], $entity->getMetadata1xSP(), 'shib13-sp-remote', $template);
			$this->addMetadata($source['src'], $entity->getMetadata1xIdP(), 'shib13-idp-remote', $template);
			$this->addMetadata($source['src'], $entity->getMetadata20SP(), 'saml20-sp-remote', $template);
			$this->addMetadata($source['src'], $entity->getMetadata20IdP(), 'saml20-idp-remote', $template);
			$attributeAuthorities = $entity->getAttributeAuthorities();
			if (!empty($attributeAuthorities)) {
				$this->addMetadata($source['src'], $attributeAuthorities[0], 'attributeauthority-remote', $template);				
			}

		}
	}


	
	/**
	 * This function writes the metadata to stdout.
	 */
	public function dumpMetadataStdOut() {
	
		foreach($this->metadata as $category => $elements) {
	
			echo('/* The following data should be added to metadata/' . $category . '.php. */' . "\n");
	
	
			foreach($elements as $m) {
				$filename = $m['filename'];
				$entityID = $m['metadata']['entityid'];
	
				echo("\n");
				echo('/* The following metadata was generated from ' . $filename . ' on ' . $this->getTime() . '. */' . "\n");
				echo('$metadata[\'' . addslashes($entityID) . '\'] = ' . var_export($m['metadata'], TRUE) . ';' . "\n");
			}
	
	
			echo("\n");
			echo('/* End of data which should be added to metadata/' . $category . '.php. */' . "\n");
			echo("\n");
		}
	}
	

	
	
	/**
	 * This function adds metadata from the specified file to the list of metadata.
	 * This function will return without making any changes if $metadata is NULL.
	 *
	 * @param $filename The filename the metadata comes from.
	 * @param $metadata The metadata.
	 * @param $type The metadata type.
	 */
	private function addMetadata($filename, $metadata, $type, $template = NULL) {
	
		if($metadata === NULL) {
			return;
		}
	
		if (isset($template)) {
// 			foreach($metadata AS $mkey => $mentry) {
// 				echo '<pre>'; print_r($metadata); exit;
// 				$metadata[$mkey] = array_merge($mentry, $template);
// 			}
			$metadata = array_merge($metadata, $template);
		}
	
		if(!array_key_exists($type, $this->metadata)) {
			$this->metadata[$type] = array();
		}
		
		// If expire is defined in constructor...
		if (!empty($this->expire)) {
			
			// If expire is already in metadata
			if (array_key_exists('expire', $metadata)) {
			
				// Override metadata expire with more restrictive global config-
				if ($this->expire < $metadata['expire'])
					$metadata['expire'] = $this->expire;		
					
			// If expire is not already in metadata use global config
			} else {
				$metadata['expire'] = $this->expire;			
			}
		}
		

	
		$this->metadata[$type][] = array('filename' => $filename, 'metadata' => $metadata);
	}


	/**
	 * This function writes the metadata to an ARP file
	 */
	function writeARPfile($config) {
		
		assert('is_a($config, \'SimpleSAML_Configuration\')');
		
		$arpfile = $config->getValue('arpfile');
		$types = array('saml20-sp-remote');
		
		$md = array();
		foreach($this->metadata as $category => $elements) {
			if (!in_array($category, $types)) continue;
			$md = array_merge($md, $elements);
		}
		
		#$metadata, $attributemap, $prefix, $suffix
		$arp = new sspmod_metarefresh_ARP($md, 
			$config->getValue('attributemap', ''),  
			$config->getValue('prefix', ''),  
			$config->getValue('suffix', '')
		);
		
		
		$arpxml = $arp->getXML();

		SimpleSAML_Logger::info('Writing ARP file: ' . $arpfile . "\n");
		file_put_contents($arpfile, $arpxml);

	}
	
	
	/**
	 * This function writes the metadata to to separate files in the output directory.
	 */
	function writeMetadataFiles($outputDir) {
	
		while(strlen($outputDir) > 0 && $outputDir[strlen($outputDir) - 1] === '/') {
			$outputDir = substr($outputDir, 0, strlen($outputDir) - 1);
		}
	
		if(!file_exists($outputDir)) {
			SimpleSAML_Logger::info('Creating directory: ' . $outputDir . "\n");
			$res = @mkdir($outputDir, 0777, TRUE);
			if ($res === FALSE) {
				throw new Exception('Error creating directory: ' . $outputDir);
			}
		}
	
		foreach($this->metadata as $category => $elements) {
	
			$filename = $outputDir . '/' . $category . '.php';
	
			SimpleSAML_Logger::debug('Writing: ' . $filename . "\n");
	
			$fh = @fopen($filename, 'w');
			if($fh === FALSE) {
				throw new Exception('Failed to open file for writing: ' . $filename . "\n");
				exit(1);
			}
	
			fwrite($fh, '<?php' . "\n");
	
			foreach($elements as $m) {
				$filename = $m['filename'];
				$entityID = $m['metadata']['entityid'];
	
				fwrite($fh, "\n");
				fwrite($fh, '/* The following metadata was generated from ' . $filename . ' on ' . $this->getTime() . '. */' . "\n");
				fwrite($fh, '$metadata[\'' . addslashes($entityID) . '\'] = ' . var_export($m['metadata'], TRUE) . ';' . "\n");
			}
	
	
			fwrite($fh, "\n");
			fwrite($fh, '?>');
	
			fclose($fh);
		}
	}


	/**
	 * Save metadata for loading with the 'serialize' metadata loader.
	 *
	 * @param string $outputDir  The directory we should save the metadata to.
	 */
	public function writeMetadataSerialize($outputDir) {
		assert('is_string($outputDir)');

		$metaHandler = new SimpleSAML_Metadata_MetaDataStorageHandlerSerialize(array('directory' => $outputDir));

		/* First we add all the metadata entries to the metadata handler. */
		foreach ($this->metadata as $set => $elements) {
			foreach ($elements as $m) {
				$entityId = $m['metadata']['entityid'];

				SimpleSAML_Logger::debug('metarefresh: Add metadata entry ' .
					var_export($entityId, TRUE) . ' in set ' . var_export($set, TRUE) . '.');
				$metaHandler->saveMetadata($entityId, $set, $m['metadata']);
			}
		}

		/* Then we delete old entries which should no longer exist. */
		$ct = time();
		foreach ($metaHandler->getMetadataSets() as $set) {
			foreach ($metaHandler->getMetadataSet($set) as $entityId => $metadata) {
				if (!array_key_exists('expire', $metadata)) {
					SimpleSAML_Logger::warning('metarefresh: Metadata entry without expire timestamp: ' . var_export($entityId, TRUE) . 
						' in set ' . var_export($set, TRUE) . '.');
					continue;
				}
				if ($metadata['expire'] > $ct) {
					continue;
				}
				SimpleSAML_Logger::debug('metarefresh: ' . $entityId . ' expired ' . date('l jS \of F Y h:i:s A', $metadata['expire']) );
				SimpleSAML_Logger::debug('metarefresh: Delete expired metadata entry ' .
					var_export($entityId, TRUE) . ' in set ' . var_export($set, TRUE) . '. (' . ($ct - $metadata['expire']) . ' sec)');
				$metaHandler->deleteMetadata($entityId, $set);
			}
		}
	}


	private function getTime() {
		/* The current date, as a string. */
		date_default_timezone_set('UTC');
		$when = date('Y-m-d\\TH:i:s\\Z');
		return $when;
	}

}

?>