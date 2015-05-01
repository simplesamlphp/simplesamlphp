<?php

class DBALTest extends \PHPUnit_Framework_TestCase
{
    /** @var \SimpleSAML\Store\DBAL */
    public $store;

    public static function setUpBeforeClass()
    {
        $config = SimpleSAML_Configuration::getInstance();
        $config->setConfigDir(__DIR__ . '/config');
    }

    public function setUp()
    {
        $this->store = SimpleSAML_Store::getInstance();
    }

    public function tearDown()
    {
        $this->store->createQueryBuilder()->delete('test_kvstore')->execute();
    }

    public function testGetSetValue()
    {
        $value = array(
            'data' => 'example',
        );

        // Check set-get value
        $this->store->set('test', 'key', $value);
        $this->assertEquals($value, $this->store->get('test', 'key'));

        // Check expire
        $this->store->set('test', 'key', $value, time() + 10);
        $this->assertEquals($value, $this->store->get('test', 'key'));
        $this->store->set('test', 'key', $value, time() - 1);
        $this->assertEquals(null, $this->store->get('test', 'key'));

        // Check long key
        $key = bin2hex(openssl_random_pseudo_bytes(100));
        $this->store->set('test', $key, $value);
        $this->assertEquals($value, $this->store->get('test', $key));
    }

    public function testDeleteValue()
    {
        $value = array(
            'data' => 'example',
        );

        // Check delete similar name values
        $this->store->set('test', 'key', $value);
        $this->store->delete('test2', 'key');
        $this->store->delete('test', 'key2');
        $this->assertEquals($value, $this->store->get('test', 'key'));

        // Check delete value
        $this->store->delete('test', 'key');
        $this->assertEquals(null, $this->store->get('test', 'key'));

        // Check delete long key value
        $key = bin2hex(openssl_random_pseudo_bytes(100));
        $this->store->set('test', $key, $value);
        $this->store->delete('test', $key);
        $this->assertEquals(null, $this->store->get('test', $key));
    }

    public function testCleanKVStore()
    {
        $tableName = $this->store->getPrefix() . '_kvstore';

        $this->store->set('test', 'key1', 'value');
        $this->store->set('test', 'key2', 'value', time()+10);
        $this->store->set('test', 'key3', 'value', time()-10);

        $this->store->cleanKVStore();
        $rows = $this->store->createQueryBuilder()
            ->select("*")
            ->from($tableName)
            ->execute()
            ->fetchAll()
        ;

        $this->assertEquals(2, count($rows));
    }

    public function testCreateOrUpdateSchema()
    {
        $tableName = $this->store->getPrefix() . '_kvstore';

        // Get database schema
        $manager = $this->store->getConnection()->getSchemaManager();
        $schema = $manager->createSchema();

        // Clone the schema and modify the KVSTORE with a new column
        $newSchema = clone $schema;
        $table = $newSchema->getTable($tableName);
        $table->addColumn('test', 'string', array('notnull' => false));

        $this->store->createOrUpdateSchema($newSchema, $tableName);

        $schema = $manager->createSchema();
        $table = $schema->getTable($tableName);
        $this->assertEquals(true, $table->hasColumn('test'));
    }
}