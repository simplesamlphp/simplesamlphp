<?php
/*
 * @author Andreas Åkre Solberg <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_metarefresh_MetaLoader {


	private $metadata;

	private $maxcache;
	private $maxduration;

	/**
	 * Constructor
	 *
	 * @param array $sources 	Sources...
	 * @param 
	 */
	public function __construct($maxcache = NULL, $maxduration = NULL) {
		$this->maxcache = $maxcache;
		$this->maxduration = $maxduration;
		
		$this->metadata = array();
	}

	/**
	 * This function processes a SAML metadata file.
	 *
	 * @param $src  Filename of the metadata file.
	 */
	public function loadSource($src, $validateFingerprint = NULL, $template = NULL) {

		$entities = SimpleSAML_Metadata_SAMLParser::parseDescriptorsFile($src);
	
		foreach($entities as $entity) {
			if($validateFingerprint !== NULL) {
				if(!$entity->validateFingerprint($validateFingerprint)) {
					echo('Skipping "' . $entity->getEntityId() . '" - could not verify signature.' . "\n");
					continue;
				}
			}
	
			if($ca !== NULL) {
				if(!$entity->validateCA($ca)) {
					echo('Skipping "' . $entity->getEntityId() . '" - could not verify certificate.' . "\n");
					continue;
				}
			}
	
			$this->addMetadata($src, $entity->getMetadata1xSP(), 'shib13-sp-remote');
			$this->addMetadata($src, $entity->getMetadata1xIdP(), 'shib13-idp-remote');
			$this->addMetadata($src, $entity->getMetadata20SP(), 'saml20-sp-remote');
			$this->addMetadata($src, $entity->getMetadata20IdP(), 'saml20-idp-remote');
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
	private function addMetadata($filename, $metadata, $type) {
	
		if($metadata === NULL) {
			return;
		}
	
		if(!array_key_exists($type, $this->metadata)) {
			$this->metadata[$type] = array();
		}
	
		$this->metadata[$type][] = array('filename' => $filename, 'metadata' => $metadata);
	}



	
	
	/**
	 * This function writes the metadata to to separate files in the output directory.
	 */
	function writeMetadataFiles($outputDir) {
	
		while(strlen($outputDir) > 0 && $outputDir[strlen($outputDir) - 1] === '/') {
			$outputDir = substr($outputDir, 0, strlen($outputDir) - 1);
		}
	
		if(!file_exists($outputDir)) {
			echo('Creating directory: ' . $outputDir . "\n");
			mkdir($outputDir, 0777, TRUE);
		}
	
		foreach($this->metadata as $category => $elements) {
	
			$filename = $outputDir . '/' . $category . '.php';
	
			echo('Writing: ' . $filename . "\n");
	
			$fh = fopen($filename, 'w');
			if($fh === FALSE) {
				echo('Failed to open file for writing: ' . $filename . "\n");
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

	private function getTime() {
		/* The current date, as a string. */
		date_default_timezone_set('UTC');
		$when = date('Y-m-d\\TH:i:s\\Z');
		return $when;
	}

}

?>