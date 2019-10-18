<?php

namespace SimpleSAML\Module\saml\IdP;

use PDO;
use SimpleSAML\Error;
use SimpleSAML\Store;
use SimpleSAML\Database;
use SimpleSAML\Configuration;

/**
 * Helper class for working with persistent NameIDs stored in SQL datastore.
 *
 * @package SimpleSAMLphp
 */
class SQLNameID
{
    const TABLE_VERSION = 1;
    const DEFAULT_TABLE_PREFIX = '';
    const TABLE_SUFFIX = '_saml_PersistentNameID';

    private static function query($query, $params = [], $config = []) {
        if (!empty($config)) {
            $database = Database::getInstance(Configuration::loadFromArray($config));
            if (stripos($query, 'SELECT') === 0) {
                $stmt = $database->read($query, $params);
            } else {
                $stmt = $database->write($query, $params);
            }
        } else {
            $store = self::getStore();
            $query = $store->pdo->prepare($query);
            $stmt = $query->execute($params);
        }
        return $stmt;
    }

    private static function tableName($config = []) {
        $store = empty($config) ? self::getStore() : null;
        $prefix = $store === null ? self::DEFAULT_TABLE_PREFIX : $store->prefix;
        $table = $prefix . self::TABLE_SUFFIX;
        return $table;
    }

    private static function createAndQuery($query, $params = [], $config = []) {
        $store = empty($config) ? self::getStore() : null;
        $table = self::tableName($config);
        if ($store === null) {
            $stmt = self::query(
                'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME=:tablename',
                ['tablename'=>$table],
                $config
            );
            if ($stmt !== false && $stmt->fetchColumn() !== '1') {
                self::createTable($table, $config);
            }
        } elseif ($store->getTableVersion('saml_PersistentNameID') !== self::TABLE_VERSION) {
            self::createTable($table);
            $store->setTableVersion('saml_PersistentNameID', self::TABLE_VERSION);
        }

        return self::query($query, $params, $config);
    }


    /**
     * Create NameID table in SQL.
     *
     * @param string $table  The table name.
     * @return void
     */
    private static function createTable($table, $config = [])
    {
        $query = 'CREATE TABLE ' . $table . ' (
            _idp VARCHAR(256) NOT NULL,
            _sp VARCHAR(256) NOT NULL,
            _user VARCHAR(256) NOT NULL,
            _value VARCHAR(40) NOT NULL,
            UNIQUE (_idp, _sp, _user)
        )';
        self::query($query, [], $config);

        $query = 'CREATE INDEX ' . $table . '_idp_sp ON ';
        $query .= $table . ' (_idp, _sp)';
        self::query($query, [], $config);
    }


    /**
     * Retrieve the SQL datastore.
     *
     * @return \SimpleSAML\Store\SQL  SQL datastore.
     */
    private static function getStore()
    {
        $store = Store::getInstance();
        if (!($store instanceof Store\SQL)) {
            throw new Error\Exception(
                'SQL NameID store requires SimpleSAMLphp to be configured with a SQL datastore.'
            );
        }

        return $store;
    }


    /**
     * Add a NameID into the database.
     *
     * @param \SimpleSAML\Store\SQL $store  The data store.
     * @param string $idpEntityId  The IdP entityID.
     * @param string $spEntityId  The SP entityID.
     * @param string $user  The user's unique identificator (e.g. username).
     * @param string $value  The NameID value.
     * @return void
     */
    public static function add($idpEntityId, $spEntityId, $user, $value, $config = [])
    {
        assert(is_string($idpEntityId));
        assert(is_string($spEntityId));
        assert(is_string($user));
        assert(is_string($value));

        $params = [
            '_idp' => $idpEntityId,
            '_sp' => $spEntityId,
            '_user' => $user,
            '_value' => $value,
        ];

        $query = 'INSERT INTO ' . self::tableName($config);
        $query .= ' (_idp, _sp, _user, _value) VALUES(:_idp, :_sp, :_user, :_value)';
        self::createAndQuery($query, $params, $config);
    }


    /**
     * Retrieve a NameID into from database.
     *
     * @param string $idpEntityId  The IdP entityID.
     * @param string $spEntityId  The SP entityID.
     * @param string $user  The user's unique identificator (e.g. username).
     * @return string|null $value  The NameID value, or NULL of no NameID value was found.
     */
    public static function get($idpEntityId, $spEntityId, $user, $config = [])
    {
        assert(is_string($idpEntityId));
        assert(is_string($spEntityId));
        assert(is_string($user));

        $params = [
            '_idp' => $idpEntityId,
            '_sp' => $spEntityId,
            '_user' => $user,
        ];

        $query = 'SELECT _value FROM ' . self::tableName($config);
        $query .= ' WHERE _idp = :_idp AND _sp = :_sp AND _user = :_user';
        $query = self::createAndQuery($query, $params, $config);

        $row = $query->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            // No NameID found
            return null;
        }

        return $row['_value'];
    }


    /**
     * Delete a NameID from the database.
     *
     * @param string $idpEntityId  The IdP entityID.
     * @param string $spEntityId  The SP entityID.
     * @param string $user  The user's unique identificator (e.g. username).
     * @return void
     */
    public static function delete($idpEntityId, $spEntityId, $user, $config = [])
    {
        assert(is_string($idpEntityId));
        assert(is_string($spEntityId));
        assert(is_string($user));

        $params = [
            '_idp' => $idpEntityId,
            '_sp' => $spEntityId,
            '_user' => $user,
        ];

        $query = 'DELETE FROM ' . self::tableName($config);
        $query .= ' WHERE _idp = :_idp AND _sp = :_sp AND _user = :_user';
        self::createAndQuery($query, $params, $config);
    }


    /**
     * Retrieve all federated identities for an IdP-SP pair.
     *
     * @param string $idpEntityId  The IdP entityID.
     * @param string $spEntityId  The SP entityID.
     * @return array  Array of userid => NameID.
     */
    public static function getIdentities($idpEntityId, $spEntityId, $config = [])
    {
        assert(is_string($idpEntityId));
        assert(is_string($spEntityId));

        $params = [
            '_idp' => $idpEntityId,
            '_sp' => $spEntityId,
        ];

        $query = 'SELECT _user, _value FROM ' . self::tableName($config);
        $query .= ' WHERE _idp = :_idp AND _sp = :_sp';
        $query = self::createAndQuery($query, $params, $config);

        $res = [];
        while (($row = $query->fetch(PDO::FETCH_ASSOC)) !== false) {
            $res[$row['_user']] = $row['_value'];
        }

        return $res;
    }
}
