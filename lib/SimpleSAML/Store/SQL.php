<?php

declare(strict_types=1);

namespace SimpleSAML\Store;

use PDO;
use PDOException;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Store;

/**
 * A data store using a RDBMS to keep the data.
 *
 * @package SimpleSAMLphp
 */
class SQL extends Store
{
    /**
     * The PDO object for our database.
     *
     * @var \PDO
     */
    public PDO $pdo;

    /**
     * Our database driver.
     *
     * @var string
     */
    public string $driver;

    /**
     * The prefix we should use for our tables.
     *
     * @var string
     */
    public string $prefix;

    /**
     * Associative array of table versions.
     *
     * @var array
     */
    private array $tableVersions;


    /**
     * Initialize the SQL data store.
     */
    public function __construct()
    {
        $config = Configuration::getInstance();

        $dsn = $config->getString('store.sql.dsn');
        $username = $config->getString('store.sql.username', null);
        $password = $config->getString('store.sql.password', null);
        $options = $config->getArray('store.sql.options', null);
        $this->prefix = $config->getString('store.sql.prefix', 'simpleSAMLphp');
        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new \Exception("Database error: " . $e->getMessage());
        }
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($this->driver === 'mysql') {
            $this->pdo->exec('SET time_zone = "+00:00"');
        }

        $this->initTableVersionTable();
        $this->initKVTable();
    }


    /**
     * Initialize the table-version table.
     */
    private function initTableVersionTable(): void
    {
        $this->tableVersions = [];

        try {
            $fetchTableVersion = $this->pdo->query('SELECT _name, _version FROM ' . $this->prefix . '_tableVersion');
        } catch (PDOException $e) {
            $this->pdo->exec(
                'CREATE TABLE ' . $this->prefix .
                '_tableVersion (_name VARCHAR(30) NOT NULL UNIQUE, _version INTEGER NOT NULL)'
            );
            return;
        }

        while (($row = $fetchTableVersion->fetch(PDO::FETCH_ASSOC)) !== false) {
            $this->tableVersions[$row['_name']] = (int) $row['_version'];
        }
    }


    /**
     * Initialize key-value table.
     */
    private function initKVTable(): void
    {
        $current_version = $this->getTableVersion('kvstore');

        $text_t = 'TEXT';
        $time_field = 'TIMESTAMP';
        if ($this->driver === 'mysql') {
            // TEXT data type has size constraints that can be hit at some point, so we use LONGTEXT instead
            $text_t = 'LONGTEXT';
        }
        if ($this->driver === 'sqlsrv') {
            // TIMESTAMP will not work for MSSQL. TIMESTAMP is automatically generated and cannot be inserted
            //    so we use DATETIME instead
            $time_field = 'DATETIME';
        }

        /**
         * Queries for updates, grouped by version.
         * New updates can be added as a new array in this array
         */
        $table_updates = [
            [
                'CREATE TABLE ' . $this->prefix .
                '_kvstore (_type VARCHAR(30) NOT NULL, _key VARCHAR(50) NOT NULL, _value ' . $text_t .
                ' NOT NULL, _expire ' . $time_field . ', PRIMARY KEY (_key, _type))',
                $this->driver === 'sqlite' || $this->driver === 'sqlsrv' || $this->driver === 'pgsql' ?
                'CREATE INDEX ' . $this->prefix . '_kvstore_expire ON ' . $this->prefix . '_kvstore (_expire)' :
                'ALTER TABLE ' . $this->prefix . '_kvstore ADD INDEX ' . $this->prefix . '_kvstore_expire (_expire)'
            ],
            /**
             * This upgrade removes the default NOT NULL constraint on the _expire field in MySQL.
             * Because SQLite does not support field alterations, the approach is to:
             *     Create a new table without the NOT NULL constraint
             *     Copy the current data to the new table
             *     Drop the old table
             *     Rename the new table correctly
             *     Read the index
             */
            [
                'CREATE TABLE ' . $this->prefix .
                '_kvstore_new (_type VARCHAR(30) NOT NULL, _key VARCHAR(50) NOT NULL, _value ' . $text_t .
                ' NOT NULL, _expire ' . $time_field . ' NULL, PRIMARY KEY (_key, _type))',
                'INSERT INTO ' . $this->prefix . '_kvstore_new SELECT * FROM ' . $this->prefix . '_kvstore',
                'DROP TABLE ' . $this->prefix . '_kvstore',
                // FOR MSSQL use EXEC sp_rename to rename a table (RENAME won't work)
                $this->driver === 'sqlsrv' ?
                'EXEC sp_rename ' . $this->prefix . '_kvstore_new, ' . $this->prefix . '_kvstore' :
                'ALTER TABLE ' . $this->prefix . '_kvstore_new RENAME TO ' . $this->prefix . '_kvstore',
                $this->driver === 'sqlite' || $this->driver === 'sqlsrv' || $this->driver === 'pgsql' ?
                'CREATE INDEX ' . $this->prefix . '_kvstore_expire ON ' . $this->prefix . '_kvstore (_expire)' :
                'ALTER TABLE ' . $this->prefix . '_kvstore ADD INDEX ' . $this->prefix . '_kvstore_expire (_expire)'
            ]
        ];

        $latest_version = count($table_updates);

        if ($current_version == $latest_version) {
            return;
        }

        // Only run queries for after the current version
        $updates_to_run = array_slice($table_updates, $current_version);

        foreach ($updates_to_run as $version_updates) {
            foreach ($version_updates as $query) {
                $this->pdo->exec($query);
            }
        }

        $this->setTableVersion('kvstore', $latest_version);
    }


    /**
     * Get table version.
     *
     * @param string $name Table name.
     *
     * @return int The table version, or 0 if the table doesn't exist.
     */
    public function getTableVersion(string $name): int
    {
        if (!isset($this->tableVersions[$name])) {
            return 0;
        }

        return $this->tableVersions[$name];
    }


    /**
     * Set table version.
     *
     * @param string $name Table name.
     * @param int $version Table version.
     */
    public function setTableVersion(string $name, int $version): void
    {
        $this->insertOrUpdate(
            $this->prefix . '_tableVersion',
            ['_name'],
            ['_name' => $name, '_version' => $version]
        );
        $this->tableVersions[$name] = $version;
    }


    /**
     * Insert or update a key-value in the store.
     *
     * Since various databases implement different methods for doing this, we abstract it away here.
     *
     * @param string $table The table we should update.
     * @param string[] $keys The key columns.
     * @param array $data Associative array with columns.
     */
    public function insertOrUpdate(string $table, array $keys, array $data): void
    {
        $colNames = '(' . implode(', ', array_keys($data)) . ')';
        $values = 'VALUES(:' . implode(', :', array_keys($data)) . ')';

        switch ($this->driver) {
            case 'mysql':
                $query = 'REPLACE INTO ' . $table . ' ' . $colNames . ' ' . $values;
                $query = $this->pdo->prepare($query);
                $query->execute($data);
                break;
            case 'sqlite':
                $query = 'INSERT OR REPLACE INTO ' . $table . ' ' . $colNames . ' ' . $values;
                $query = $this->pdo->prepare($query);
                $query->execute($data);
                break;
            default:
                $updateCols = [];
                $condCols = [];
                $condData = [];

                foreach ($data as $col => $value) {
                    $tmp = $col . ' = :' . $col;

                    if (in_array($col, $keys, true)) {
                        $condCols[] = $tmp;
                        $condData[$col] = $value;
                    } else {
                        $updateCols[] = $tmp;
                    }
                }

                $selectQuery = 'SELECT * FROM ' . $table . ' WHERE ' . implode(' AND ', $condCols);
                $selectQuery = $this->pdo->prepare($selectQuery);
                $selectQuery->execute($condData);

                if (count($selectQuery->fetchAll()) > 0) {
                    // Update
                    $insertOrUpdateQuery = 'UPDATE ' . $table . ' SET ' . implode(',', $updateCols);
                    $insertOrUpdateQuery .= ' WHERE ' . implode(' AND ', $condCols);
                    $insertOrUpdateQuery = $this->pdo->prepare($insertOrUpdateQuery);
                } else {
                    // Insert
                    $insertOrUpdateQuery = 'INSERT INTO ' . $table . ' ' . $colNames . ' ' . $values;
                    $insertOrUpdateQuery = $this->pdo->prepare($insertOrUpdateQuery);
                }
                $insertOrUpdateQuery->execute($data);
                break;
        }
    }


    /**
     * Clean the key-value table of expired entries.
     */
    private function cleanKVStore(): void
    {
        Logger::debug('store.sql: Cleaning key-value store.');

        $query = 'DELETE FROM ' . $this->prefix . '_kvstore WHERE _expire < :now';
        $params = ['now' => gmdate('Y-m-d H:i:s')];

        $query = $this->pdo->prepare($query);
        $query->execute($params);
    }


    /**
     * Retrieve a value from the data store.
     *
     * @param string $type The type of the data.
     * @param string $key The key to retrieve.
     *
     * @return mixed|null The value associated with that key, or null if there's no such key.
     */
    public function get(string $type, string $key)
    {
        if (strlen($key) > 50) {
            $key = sha1($key);
        }

        $query = 'SELECT _value FROM ' . $this->prefix .
            '_kvstore WHERE _type = :type AND _key = :key AND (_expire IS NULL OR _expire > :now)';
        $params = ['type' => $type, 'key' => $key, 'now' => gmdate('Y-m-d H:i:s')];

        $query = $this->pdo->prepare($query);
        $query->execute($params);

        $row = $query->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        $value = $row['_value'];

        Assert::string($value);

        $value = urldecode($value);
        $value = unserialize($value);

        if ($value === false) {
            return null;
        }
        return $value;
    }


    /**
     * Save a value in the data store.
     *
     * @param string $type The type of the data.
     * @param string $key The key to insert.
     * @param mixed $value The value itself.
     * @param int|null $expire The expiration time (unix timestamp), or null if it never expires.
     */
    public function set(string $type, string $key, $value, ?int $expire = null): void
    {
        Assert::nullOrGreaterThan($expire, 2592000);

        if (rand(0, 1000) < 10) {
            $this->cleanKVStore();
        }

        if (strlen($key) > 50) {
            $key = sha1($key);
        }

        if ($expire !== null) {
            $expire = gmdate('Y-m-d H:i:s', $expire);
        }

        $value = serialize($value);
        $value = rawurlencode($value);

        $data = [
            '_type'   => $type,
            '_key'    => $key,
            '_value'  => $value,
            '_expire' => $expire,
        ];

        $this->insertOrUpdate($this->prefix . '_kvstore', ['_type', '_key'], $data);
    }


    /**
     * Delete an entry from the data store.
     *
     * @param string $type The type of the data
     * @param string $key The key to delete.
     */
    public function delete(string $type, string $key): void
    {
        if (strlen($key) > 50) {
            $key = sha1($key);
        }

        $data = [
            '_type' => $type,
            '_key'  => $key,
        ];

        $query = 'DELETE FROM ' . $this->prefix . '_kvstore WHERE _type=:_type AND _key=:_key';
        $query = $this->pdo->prepare($query);
        $query->execute($data);
    }
}
