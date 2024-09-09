<?php

declare(strict_types=1);

namespace SimpleSAML\Store;

use Exception;
use PDO;
use PDOException;
use SimpleSAML\Assert\Assert;
use SimpleSAML\{Configuration, Logger, Utils};

use function array_keys;
use function count;
use function gmdate;
use function implode;
use function in_array;
use function intval;
use function rand;
use function serialize;
use function sha1;
use function strlen;
use function unserialize;
use function urldecode;
use function rawurlencode;

/**
 * A data store using a RDBMS to keep the data.
 *
 * @package simplesamlphp/simplesamlphp
 */
class SQLStore implements StoreInterface
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
        $username = $config->getOptionalString('store.sql.username', null);
        $password = $config->getOptionalString('store.sql.password', null);
        $options = $config->getOptionalArray('store.sql.options', null);
        $this->prefix = $config->getOptionalString('store.sql.prefix', 'simpleSAMLphp');
        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
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
                '_tableVersion (_name VARCHAR(30) PRIMARY KEY NOT NULL, _version INTEGER NOT NULL)',
            );
            $this->setTableVersion('tableVersion', 1);
            return;
        }

        while (($row = $fetchTableVersion->fetch(PDO::FETCH_ASSOC)) !== false) {
            $this->tableVersions[$row['_name']] = intval($row['_version']);
        }

        $tableVer = $this->getTableVersion('tableVersion');
        if ($tableVer === 1) {
            return;
        } else {
            // The _name index is being changed from UNIQUE to PRIMARY KEY for table version 1.
            switch ($this->driver) {
                case 'pgsql':
                    // Drop old index and add primary key
                    $update = [
                        'ALTER TABLE ' . $this->prefix . '_tableVersion DROP CONSTRAINT IF EXISTS ' .
                          $this->prefix . '_tableVersion__name_key',
                        'ALTER TABLE ' . $this->prefix . '_tableVersion ADD PRIMARY KEY (_name)',
                    ];
                    break;
                case 'sqlsrv':
                    /**
                     * Drop old index and add primary key.
                     * NOTE: Because the index has a default name, we need to look it up in the information
                     *       schema.
                     */
                    $update = [
                        // Use dynamic SQL to drop the existing unique constraint
                        'DECLARE @constraintName NVARCHAR(128); ' .
                        'SELECT @constraintName = CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS ' .
                        'WHERE TABLE_NAME = \'' . $this->prefix . '_tableVersion\' AND CONSTRAINT_TYPE = \'UNIQUE\'; ' .
                        'IF @constraintName IS NOT NULL ' .
                        'BEGIN ' .
                            'EXEC(\'ALTER TABLE ' . $this->prefix . '_tableVersion ' .
                                  ' DROP CONSTRAINT \' + @constraintName); ' .
                        'END;',

                        // Add the new primary key constraint
                        'ALTER TABLE ' . $this->prefix . '_tableVersion ' .
                        '  ADD CONSTRAINT PK_' . $this->prefix . '_tableVersion ' .
                        '      PRIMARY KEY CLUSTERED (_name);',
                    ];
                    break;
                case 'sqlite':
                    /**
                     * Because SQLite does not support field alterations, the approach is to:
                     *     Create a new table with the primary key
                     *     Copy the current data to the new table
                     *     Drop the old table
                     *     Rename the new table correctly
                     */
                    $update = [
                        'CREATE TABLE ' . $this->prefix .
                          '_tableVersion (_name VARCHAR(30) PRIMARY KEY NOT NULL, _version INTEGER NOT NULL)',
                        'INSERT INTO ' . $this->prefix . '_tableVersion_new SELECT * FROM ' .
                          $this->prefix . '_tableVersion',
                        'DROP TABLE ' . $this->prefix . '_tableVersion',
                        'ALTER TABLE ' . $this->prefix . '_tableVersion_new RENAME TO ' .
                          $this->prefix . '_tableVersion',
                    ];
                    break;
                case 'mysql':
                    // Drop old index and add primary key
                    $update = [
                        'ALTER TABLE ' . $this->prefix . '_tableVersion DROP INDEX _name',
                        'ALTER TABLE ' . $this->prefix . '_tableVersion ADD PRIMARY KEY (_name)',
                    ];
                    break;
                default:
                    // Drop old index and add primary key
                    $update = [
                        'ALTER TABLE ' . $this->prefix . '_tableVersion DROP INDEX _name',
                        'ALTER TABLE ' . $this->prefix . '_tableVersion ADD PRIMARY KEY (_name)',
                    ];
                    break;
            }

            try {
                foreach ($update as $query) {
                    $this->pdo->exec($query);
                }
            } catch (Exception $e) {
                Logger::warning('Database error: ' . var_export($this->pdo->errorInfo(), true));
                return;
            }
            $this->setTableVersion('tableVersion', 1);
            return;
        }
    }

    /**
     * Initialize key-value table.
     */
    private function initKVTable(): void
    {
        $tableVer = $this->getTableVersion('kvstore');
        if ($tableVer === 2) {
            return;
        } elseif ($tableVer < 2 && $tableVer > 0) {
            throw new Exception(
                'No upgrade path available. Please migrate to the latest 1.16+ '
                . 'version of SimpleSAMLphp first before upgrading to 2.x.',
            );
        }

        $text_t = 'TEXT';
        if ($this->driver === 'mysql') {
            // TEXT data type has size constraints that can be hit at some point, so we use LONGTEXT instead
            $text_t = 'LONGTEXT';
        }

        $time_field = 'TIMESTAMP';
        if ($this->driver === 'sqlsrv') {
            // TIMESTAMP will not work for MSSQL. TIMESTAMP is automatically generated and cannot be inserted
            //    so we use DATETIME instead
            $time_field = 'DATETIME';
        }

        $query = 'CREATE TABLE ' . $this->prefix .
            '_kvstore (_type VARCHAR(30) NOT NULL, _key VARCHAR(50) NOT NULL, _value ' . $text_t .
            ' NOT NULL, _expire ' . $time_field . ' NULL, PRIMARY KEY (_key, _type))';
        $this->pdo->exec($query);

        $query = $this->driver === 'sqlite' || $this->driver === 'sqlsrv' || $this->driver === 'pgsql' ?
            'CREATE INDEX ' . $this->prefix . '_kvstore_expire ON ' . $this->prefix . '_kvstore (_expire)' :
            'ALTER TABLE ' . $this->prefix . '_kvstore ADD INDEX ' . $this->prefix . '_kvstore_expire (_expire)';
        $this->pdo->exec($query);

        $this->setTableVersion('kvstore', 2);
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
            ['_name' => $name, '_version' => $version],
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
    public function get(string $type, string $key): mixed
    {
        if ($type == 'session') {
            $key = $this->hashData($key);
        }

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
    public function set(string $type, string $key, mixed $value, ?int $expire = null): void
    {
        Assert::nullOrGreaterThan($expire, 2592000);

        if (rand(0, 1000) < 10) {
            $this->cleanKVStore();
        }

        if ($type == 'session') {
            $key = $this->hashData($key);
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
        if ($type == 'session') {
            $key = $this->hashData($key);
        }

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


    /**
     * Calculates an URL-safe sha-256 hash.
     *
     * @param string $data
     * @return string The hashed data.
     */
    private function hashData(string $data): string
    {
        $secretSalt = (new Utils\Config())->getSecretSalt();
        return hash_hmac('sha256', $data, $secretSalt);
    }
}
