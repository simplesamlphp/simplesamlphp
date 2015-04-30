<?php

namespace SimpleSAML\Store;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

class DBAL extends \SimpleSAML_Store
{
    /**
     * The prefix we should use for our tables.
     *
     * @var string
     */
    private $prefix;

    /**
     * The key-value table prefix.
     *
     * @var string
     */
    private $kvstorePrefix;

    /**
     * Initialize the SQL datastore.
     */
    protected function __construct()
    {
        $config = \SimpleSAML_Configuration::getInstance();

        $this->prefix = $config->getString('store.sql.prefix', 'simpleSAMLphp');
        $this->kvstorePrefix = $this->prefix.'_kvstore';

        $connectionParams = array(
            'driver' => $config->getString('store.dbal.driver'),
            'user' => $config->getString('store.dbal.user', null),
            'password' => $config->getString('store.dbal.password', null),
            'host' => $config->getString('store.dbal.host', 'localhost'),
            'dbname' => $config->getString('store.dbal.dbname'),
        );

        $this->conn = DriverManager::getConnection($connectionParams);
        $this->initKVTable();
    }

    /**
     * Initialize key-value table.
     */
    private function initKVTable()
    {
        $schema = new Schema();
        $kvstore = $schema->createTable($this->kvstorePrefix);
        $kvstore->addColumn('_type', 'string', array('length' => 30, 'notnull' => true));
        $kvstore->addColumn('_key', 'string', array('length' => 50, 'notnull' => true));
        $kvstore->addColumn('_value', 'text', array('notnull' => true));
        $kvstore->addColumn('_expire', 'datetime', array('notnull' => false));
        $kvstore->setPrimaryKey(array('_key', '_type'));
        $kvstore->addIndex(array('_expire'));

        $this->createOrUpdateSchema($schema, $this->kvstorePrefix);
    }

    /**
     * Create or update a schema.
     *
     * @param Schema $schema
     * @param string $tablePrefix Only tables with this prefix will be updated.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function createOrUpdateSchema(Schema $schema, $tablePrefix)
    {
        $manager = $this->conn->getSchemaManager();
        $platform = $this->conn->getDatabasePlatform();

        $origSchema = $manager->createSchema();
        $tables = [];

        foreach ($origSchema->getTables() as $table) {
            if (0 === strpos($table->getName(), $tablePrefix)) {
                $tables[] = $table;
            }
        }

        $migrateSchema = new Schema($tables);
        $queries = $migrateSchema->getMigrateToSql($schema, $platform);

        foreach ($queries as $query) {
            $this->conn->executeQuery($query);
        }
    }

    /**
     * Retrieve a value from the datastore.
     *
     * @param string $type The datatype.
     * @param string $key  The key.
     *
     * @return mixed|NULL The value.
     */
    public function get($type, $key)
    {
        if (strlen($key) > 50) {
            $key = sha1($key);
        }

        $qb = $this->createQueryBuilder()->from($this->kvstorePrefix);
        $query = $qb->select('_value')
            ->where($qb->expr()->eq('_type', ':type'))
            ->andWhere($qb->expr()->eq('_key', ':key'))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('_expire'),
                $qb->expr()->gt('_expire', ':now')
            ))
            ->setParameter('type', $type, Type::STRING)
            ->setParameter('key', $key, Type::INTEGER)
            ->setParameter('now', new \DateTime(), Type::DATETIME)
            ->execute()
        ;

        $result = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (count($result) === 0) {
            return;
        }

        $value = $result[0]['_value'];
        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }
        $value = urldecode($value);
        $value = unserialize($value);

        if (false === $value) {
            return;
        }

        return $value;
    }

    /**
     * Save a value to the datastore.
     *
     * @param string   $type   The datatype.
     * @param string   $key    The key.
     * @param mixed    $value  The value.
     * @param int|NULL $expire The expiration time (unix timestamp), or NULL if it never expires.
     */
    public function set($type, $key, $value, $expire = null)
    {
        if (strlen($key) > 50) {
            $key = sha1($key);
        }

        if ($expire !== null) {
            $expire = date_timestamp_set(new \DateTime(), $expire);
        }

        $value = serialize($value);
        $value = rawurlencode($value);

        $this->delete($type, $key);

        $qb = $this->createQueryBuilder();
        $query = $qb->update($this->kvstorePrefix)
            ->set('_value', ':value')
            ->set('_expire', ':expire')
            ->where($qb->expr()->eq('_type', ':type'))
            ->andWhere($qb->expr()->eq('_key', ':key'))
            ->setParameter('type', $type, Type::STRING)
            ->setParameter('key', $key, Type::INTEGER)
            ->setParameter('value', $value, Type::TEXT)
            ->setParameter('expire', $expire, Type::DATETIME)
        ;
        $rows = $query->execute();

        if (0 === $rows) {
            $qb = $this->createQueryBuilder();
            $query = $qb->insert($this->kvstorePrefix)
               ->setValue('_type', ':type')
               ->setValue('_key', ':key')
               ->setValue('_value', ':value')
               ->setValue('_expire', ':expire')
               ->setParameter('type', $type, Type::STRING)
               ->setParameter('key', $key, Type::INTEGER)
               ->setParameter('value', $value, Type::TEXT)
               ->setParameter('expire', $expire, Type::DATETIME)
            ;
            $query->execute();
        }
    }

    /**
     * Delete a value from the datastore.
     *
     * @param string $type The datatype.
     * @param string $key  The key.
     */
    public function delete($type, $key)
    {
        if (strlen($key) > 50) {
            $key = sha1($key);
        }

        $qb = $this->createQueryBuilder();
        $qb->delete($this->kvstorePrefix)
            ->where($qb->expr()->eq('_type', ':type'))
            ->andWhere($qb->expr()->eq('_key', ':key'))
            ->setParameter('type', $type, Type::STRING)
            ->setParameter('key', $key, Type::INTEGER)
            ->execute()
        ;
    }

    /**
     * Clean the key-value table of expired entries.
     */
    protected function cleanKVStore()
    {
        \SimpleSAML_Logger::debug('store.dbal: Cleaning key-value store.');

        $qb = $this->createQueryBuilder();
        $qb->delete($this->kvstorePrefix)
            ->where($qb->expr()->lt('_expire', ':now'))
            ->setParameter('now', new \DateTime(), Type::DATETIME)
            ->execute()
        ;
    }

    /**
     * Create QueryBuilder.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function createQueryBuilder()
    {
        return $this->conn->createQueryBuilder();
    }
}
