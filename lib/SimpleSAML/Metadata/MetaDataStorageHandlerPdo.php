<?php

/**
 * Class for handling metadata files stored in a database.
 *
 * This class has been based off a previous version written by
 * mooknarf@gmail.com and patched to work with the latest version
 * of simpleSAMLphp
 *
 * @author Tyler Antonio, University of Alberta <tantonio@ualberta.ca>
 * @package simpleSAMLphp
 */
class SimpleSAML_Metadata_MetaDataStorageHandlerPdo extends SimpleSAML_Metadata_MetaDataStorageSource{

	/**
	 * PDO Database connection string
	 */
	private $dsn;

	/**
	 * The PDO object
	 */
	private $pdo;

	/**
	 * Prefix to apply to the metadata table
	 */
	private $tablePrefix;

	/**
	 * This is an associative array which stores the different metadata sets we have loaded.
	 */
	private $cachedMetadata = array();

	/**
	 * All the metadata sets supported by this MetaDataStorageHandler
	 */
	public $supportedSets = array(
		'adfs-idp-hosted',
		'adfs-sp-remote',
		'saml20-idp-hosted',
		'saml20-idp-remote',
		'saml20-sp-remote',
		'shib13-idp-hosted',
		'shib13-idp-remote',
		'shib13-sp-hosted',
		'shib13-sp-remote',
		'wsfed-idp-remote',
		'wsfed-sp-hosted'
	);


	/**
	 * This constructor initializes the PDO metadata storage handler with the specified
	 * configuration. The configuration is an associative array with the following
	 * possible elements (set in config.php):
	 * - 'usePersistentConnection': TRUE/FALSE if database connection should be
	 *                				persistent.
	 *
	 * - 'dsn': 					The database connection string.
	 *
	 * - 'username': 				Database user name
	 *
	 * - 'password': 				Password for the database user.
	 *
	 * @param $config  An associtive array with the configuration for this handler.
	 */
	public function __construct($config) {
		assert('is_array($config)');

		$globalConfig = SimpleSAML_Configuration::getInstance();

		$cfgHelp = SimpleSAML_Configuration::loadFromArray($config, 'pdo metadata source');

		// determine the table prefix if one was set
		$this->tablePrefix = $cfgHelp->getString('tablePrefix', '');
		$this->dsn = $cfgHelp->getString('dsn');

		$driverOptions = array();
		if ($cfgHelp->getBoolean('usePersistentConnection', TRUE)) {
			$driverOptions = array(PDO::ATTR_PERSISTENT => TRUE);
		}

		$this->pdo = new PDO($this->dsn, $cfgHelp->getValue('username', NULL), $cfgHelp->getValue('password', NULL), $driverOptions);
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}


	/**
	 * This function loads the given set of metadata from a file our configured database.
	 * This function returns NULL if it is unable to locate the given set in the metadata directory.
	 *
	 * @param $set  The set of metadata we are loading.
	 * @return Associative array with the metadata, or NULL if we are unable to load metadata from the given file.
	 */
	private function load($set) {
		assert('is_string($set)');

		$tableName = $this->getTableName($set);

		if (!in_array($set, $this->supportedSets)) {
			return NULL;
		}

		$stmt = $this->pdo->prepare("SELECT entity_id, entity_data FROM $tableName");
		if($stmt->execute()) {
			$metadata = array();

			while($d = $stmt->fetch()) {
				$metadata[$d['entity_id']] = json_decode($d['entity_data'], TRUE);
			}

			return $metadata;
		} else {
			throw new Exception('PDO metadata handler: Database error: ' . var_export($this->pdo->errorInfo(), TRUE));
		}
	}


	/**
	 * Retrieve a list of all available metadata for a given set.
	 *
	 * @param string $set  The set we are looking for metadata in.
	 * @return array  An associative array with all the metadata for the given set.
	 */
	public function getMetadataSet($set) {
		assert('is_string($set)');

		if(array_key_exists($set, $this->cachedMetadata)) {
			return $this->cachedMetadata[$set];
		}

		$metadataSet = $this->load($set);
		if($metadataSet === NULL) {
			$metadataSet = array();
		}

		foreach ($metadataSet AS $entityId => &$entry) {
			if (preg_match('/__DYNAMIC(:[0-9]+)?__/', $entityId)) {
				$entry['entityid'] = $this->generateDynamicHostedEntityID($set);
			} else {
				$entry['entityid'] = $entityId;
			}
		}

		$this->cachedMetadata[$set] = $metadataSet;
		return $metadataSet;
	}

	private function generateDynamicHostedEntityID($set) {
		assert('is_string($set)');

		/* Get the configuration. */
		$baseurl = SimpleSAML_Utilities::getBaseURL();

		if ($set === 'saml20-idp-hosted') {
			return $baseurl . 'saml2/idp/metadata.php';
		} elseif($set === 'saml20-sp-hosted') {
			return $baseurl . 'saml2/sp/metadata.php';			
		} elseif($set === 'shib13-idp-hosted') {
			return $baseurl . 'shib13/idp/metadata.php';
		} elseif($set === 'shib13-sp-hosted') {
			return $baseurl . 'shib13/sp/metadata.php';
		} elseif($set === 'wsfed-sp-hosted') {
			return 'urn:federation:' . SimpleSAML_Utilities::getSelfHost();
		} elseif($set === 'adfs-idp-hosted') {
			return 'urn:federation:' . SimpleSAML_Utilities::getSelfHost() . ':idp';
		} else {
			throw new Exception('Can not generate dynamic EntityID for metadata of this type: [' . $set . ']');
		}
	}

	/**
	 * Add metadata to the configured database
	 *
	 * @param string $index Entity ID
	 * @param string $set The set to add the metadata to
	 * @param array $entityData Metadata
	 * @return bool True/False if entry was sucessfully added
	 */
	public function addEntry($index, $set, $entityData) {
		assert('is_string($index)');
		assert('is_string($set)');
		assert('is_array($entityData)');

		if (!in_array($set, $this->supportedSets)) {
			return FALSE;
		}

		$tableName = $this->getTableName($set);

		$metadata = $this->pdo->prepare("SELECT entity_id, entity_data FROM $tableName WHERE entity_id = :entity_id");
		$metadata->bindValue(":entity_id", $index, PDO::PARAM_STR);
		$metadata->execute();
		$retrivedEntityIDs = $metadata->fetch();

		if(count($retrivedEntityIDs) > 0){
			$stmt = $this->pdo->prepare("UPDATE $tableName SET entity_data = :entity_data WHERE entity_id = :entity_id");
		}
		else{
			$stmt = $this->pdo->prepare("INSERT INTO $tableName (entity_id, entity_data) VALUES (:entity_id, :entity_data)");
		}

		$stmt->bindValue(":entity_id", $index, PDO::PARAM_STR);
		$stmt->bindValue(":entity_data", json_encode($entityData), PDO::PARAM_STR);
		
		if ($stmt->execute() === FALSE) {
			throw new Exception("PDO metadata handler: Database error: " . var_export($this->pdo->errorInfo(), TRUE));
		}
		return 1 === $stmt->rowCount();
	}

	/**
	 * Replace the -'s to an _ in table names for Metadata sets
	 * since SQL does not allow a - in a table name.
	 *
	 * @param string $table Table
	 * @return string Replaced table name
	 */
	private function getTableName($table) {
		assert('is_string($table)');

		return str_replace("-", "_", $this->tablePrefix . $table);
	}

	/**
	 * Initialize the configured database
	 */
	public function initDatabase() {
		foreach ($this->supportedSets as $set) {
			$tableName = $this->getTableName($set);
			$result = $this->pdo->exec("CREATE TABLE IF NOT EXISTS $tableName (entity_id VARCHAR(255) PRIMARY KEY NOT NULL, entity_data TEXT NOT NULL)");
			if ($result === FALSE) {
			    throw new Exception("PDO metadata handler: Database error: " . var_export($this->pdo->errorInfo(), TRUE));
			}
		}
	}

}
