<?php

/**
 * Error for missing metadata.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Error_MetadataNotFound extends SimpleSAML_Error_Error {

	/**
	 * The entityID we were unable to locate.
	 *
	 * @var string
	 */
	private $entityId;


	/**
	 * Create the error
	 *
	 * @param string $entityId  The entityID we were unable to locate.
	 */
	public function __construct($entityId) {
		assert('is_string($entityId)');

		parent::__construct(array(
				'METADATANOTFOUND',
				'ENTITYID' => htmlspecialchars(var_export($entityId, TRUE))
		));

		$this->entityId = $entityId;
	}


	/**
	 * Show the error to the user.
	 *
	 * This function does not return.
	 */
	public function show() {

		header('HTTP/1.0 500 Internal Server Error');

		$this->logError();

		$globalConfig = SimpleSAML_Configuration::getInstance();
		$t = new SimpleSAML_XHTML_Template($globalConfig, 'core:no_metadata.tpl.php');
		$t->data['entityId'] = $this->entityId;
		$t->show();
		exit();
	}

}
