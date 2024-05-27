<?php

declare(strict_types=1);

namespace SimpleSAML\Metadata;

use Exception;
use SimpleSAML\{Configuration, Database, Error};

use function array_key_exists;
use function count;
use function in_array;
use function json_decode;
use function json_last_error;
use function str_replace;
use function var_export;

/**
 * Class for handling metadata files stored in a database.
 *
 * This class has been based off a previous version written by
 * mooknarf@gmail.com and patched to work with the latest version
 * of SimpleSAMLphp
 *
 * @package SimpleSAMLphp
 */

class MetaDataStorageHandlerPdo extends MetaDataStorageSource
{
    /**
     * The PDO object
     * @var \SimpleSAML\Database
     */
    private Database $db;

    /**
     * Prefix to apply to the metadata table
     */
    private string $tablePrefix = '';

    /**
     * This is an associative array which stores the different metadata sets we have loaded.
     */
    private array $cachedMetadata = [];

    /**
     * All the metadata sets supported by this MetaDataStorageHandler
     * @var string[]
     */
    public array $supportedSets = [
        'adfs-idp-hosted',
        'adfs-sp-remote',
        'saml20-idp-hosted',
        'saml20-idp-remote',
        'saml20-sp-remote',
    ];


    /**
     * This constructor initializes the PDO metadata storage handler with the specified
     * configuration. The configuration is an associative array with the following
     * possible elements (set in config.php):
     * - 'usePersistentConnection': TRUE/FALSE if database connection should be persistent.
     * - 'dsn':                     The database connection string.
     * - 'username':                Database user name
     * - 'password':                Password for the database user.
     *
     * @param array $config An associative array with the configuration for this handler.
     */
    public function __construct(
        /** @scrutinizer ignore-unused */ Configuration $globalConfig,
        /** @scrutinizer ignore-unused */ array $config,
    ) {
        parent::__construct();

        $this->db = Database::getInstance();
    }


    /**
     * This function loads the given set of metadata from a file to a configured database.
     * This function returns NULL if it is unable to locate the given set in the metadata directory.
     *
     * @param string $set The set of metadata we are loading.
     *
     * @return array|null $metadata Associative array with the metadata, or NULL if we are unable to load
     *     metadata from the given file.
     *
     * @throws \Exception If a database error occurs.
     * @throws \SimpleSAML\Error\Exception If the metadata can be retrieved from the database, but cannot be decoded.
     */
    private function load(string $set): ?array
    {
        $tableName = $this->getTableName($set);

        if (!in_array($set, $this->supportedSets, true)) {
            return null;
        }

        $stmt = $this->db->read("SELECT entity_id, entity_data FROM $tableName");
        if ($stmt->execute()) {
            $metadata = [];

            while ($d = $stmt->fetch()) {
                $data = json_decode($d['entity_data'], true);
                if ($data === null) {
                    throw new Error\Exception("Cannot decode metadata for entity '${d['entity_id']}'");
                }
                if (!array_key_exists('entityid', $data)) {
                    $data['entityid'] = $d['entity_id'];
                }
                $metadata[$d['entity_id']] = $data;
            }

            return $metadata;
        } else {
            throw new Exception(
                'PDO metadata handler: Database error: ' . var_export($this->db->getLastError(), true),
            );
        }
    }


    /**
     * Retrieve a list of all available metadata for a given set.
     *
     * @param string $set The set we are looking for metadata in.
     *
     * @return array $metadata An associative array with all the metadata for the given set.
     */
    public function getMetadataSet(string $set): array
    {
        if (array_key_exists($set, $this->cachedMetadata)) {
            return $this->cachedMetadata[$set];
        }

        $metadataSet = $this->load($set);
        if ($metadataSet === null) {
            $metadataSet = [];
        }

        foreach ($metadataSet as $entityId => &$entry) {
            $entry['entityid'] = $entityId;
        }

        $this->cachedMetadata[$set] = $metadataSet;
        return $metadataSet;
    }


    /**
     * Retrieve a metadata entry.
     *
     * @param string $entityId The entityId we are looking up.
     * @param string $set The set we are looking for metadata in.
     *
     * @return array|null An associative array with metadata for the given entity, or NULL if we are unable to
     *         locate the entity.
     */
    public function getMetaData(string $entityId, string $set): ?array
    {
        // validate the metadata set is valid
        if (!in_array($set, $this->supportedSets, true)) {
            return null;
        }

        // support caching
        if (isset($this->cachedMetadata[$entityId][$set])) {
            return $this->cachedMetadata[$entityId][$set];
        }

        $tableName = $this->getTableName($set);
        $stmt = $this->db->read(
            "SELECT entity_id, entity_data FROM {$tableName} WHERE entity_id = :entityId",
            ['entityId' => $entityId],
        );

        // throw pdo exception upon execution failure
        if (!$stmt->execute()) {
            throw new Exception(
                'PDO metadata handler: Database error: ' . var_export($this->db->getLastError(), true),
            );
        }

        // load the metadata into an array
        $metadataSet = [];
        while ($d = $stmt->fetch()) {
            $data = json_decode($d['entity_data'], true);
            if (json_last_error() != JSON_ERROR_NONE) {
                throw new Error\Exception(
                    "Cannot decode metadata for entity '${d['entity_id']}'",
                );
            }

            // update the entity id to either the key (if not dynamic or generate the dynamic hosted url)
            $data['entityid'] = $entityId;
            $metadataSet[$d['entity_id']] = $data;
        }

        $indexLookup = $this->lookupIndexFromEntityId($entityId, $metadataSet);
        if (isset($indexLookup) && array_key_exists($indexLookup, $metadataSet)) {
            $this->cachedMetadata[$indexLookup][$set] = $metadataSet[$indexLookup];
            return $this->cachedMetadata[$indexLookup][$set];
        }

        return null;
    }


    /**
     * Add metadata to the configured database
     *
     * @param string $index Entity ID
     * @param string $set The set to add the metadata to
     * @param array  $entityData Metadata
     *
     * @return bool True/False if entry was successfully added
     */
    public function addEntry(string $index, string $set, array $entityData): bool
    {
        if (!in_array($set, $this->supportedSets, true)) {
            return false;
        }

        $tableName = $this->getTableName($set);

        $metadata = $this->db->read(
            "SELECT entity_id, entity_data FROM $tableName WHERE entity_id = :entity_id",
            [
                'entity_id' => $index,
            ],
        );

        $retrivedEntityIDs = $metadata->fetch();

        $params = [
            'entity_id'   => $index,
            'entity_data' => json_encode($entityData),
        ];

        if ($retrivedEntityIDs !== false && count($retrivedEntityIDs) > 0) {
            $rows = $this->db->write(
                "UPDATE $tableName SET entity_data = :entity_data WHERE entity_id = :entity_id",
                $params,
            );
        } else {
            $rows = $this->db->write(
                "INSERT INTO $tableName (entity_id, entity_data) VALUES (:entity_id, :entity_data)",
                $params,
            );
        }

        return $rows === 1;
    }


    /**
     * Remove metadata from the configured database
     *
     * @param string $entityId The entityId we are removing.
     * @param string $set The set to remove the metadata from.
     *
     * @return bool True/False if entry was successfully deleted
     */
    public function removeEntry(string $entityId, string $set): bool
    {
        if (!in_array($set, $this->supportedSets, true)) {
            return false;
        }

        $tableName = $this->getTableName($set);

        $rows = $this->db->write(
            "DELETE FROM $tableName WHERE entity_id = :entity_id",
            ['entity_id' => $entityId],
        );

        return $rows === 1;
    }


    /**
     * Replace the -'s to an _ in table names for Metadata sets
     * since SQL does not allow a - in a table name.
     *
     * @param string $table Table
     *
     * @return string Replaced table name
     */
    private function getTableName(string $table): string
    {
        return $this->db->applyPrefix(str_replace("-", "_", $this->tablePrefix . $table));
    }


    /**
     * Initialize the configured database
     *
     * @return int|false The number of SQL statements successfully executed, false if some error occurred.
     */
    public function initDatabase(): int|false
    {
        $stmt = 0;
        $fine = true;
        $driver = $this->db->getDriver();

        $text = 'TEXT';
        if ($driver === 'mysql') {
            $text = 'MEDIUMTEXT';
        }

        foreach ($this->supportedSets as $set) {
            $tableName = $this->getTableName($set);
            $rows = $this->db->write(sprintf(
                "CREATE TABLE IF NOT EXISTS $tableName (entity_id VARCHAR(255) PRIMARY KEY NOT NULL, "
                    . "entity_data %s NOT NULL)",
                $text,
            ));

            if ($rows === false) {
                $fine = false;
            } else {
                $stmt += $rows;
            }
        }

        if (!$fine) {
            return false;
        }
        return $stmt;
    }
}
