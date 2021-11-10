<?php

declare(stricttypes=1);

namespace SimpleSAML\Store;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;

/**
 * A data store using MongoDB to keep the data.
 *
 * @package simplesamlphp/simplesamlphp
 */
class MongoStore implements StoreInterface
{
    /**
     * The MongoDB database.
     *
     * @var \MongoDB\Database
     */
    public \MongoDB\Database $db;

    /**
     * The MongoDB collection we should use.
     *
     * @var \MongoDB\Collection
     */
    public \MongoDB\Collection $collection;


    /**
     * Initialize the SQL data store.
     */
    public function __construct()
    {
        $config = Configuration::getInstance();

        $connection_string = $config->getString('store.mongo.connection_string');
        $client = new \MongoDB\Client($connection_string, [], [
            'typeMap' => [
                'array' => 'array',
                'document' => 'array',
                'root' => 'array',
            ],
        ]);
        $database_name = $config->getString('store.mongo.database', 'simpleSAMLphp');
        $this->db = $client->$database_name;
        $collection_name = $config->getString('store.mongo.collection', 'kvstore');
        $this->initCollection($collection_name);
    }


    /**
     * Initialize collection.
     */
    private function initCollection($collection_name): void
    {
        $this->db->createCollection($collection_name);
        $this->collection = $this->db->$collection_name;
        $this->collection->createIndexes([
            ['key' => [['key' => 1, 'type' => 1]], 'unique' => true],
            ['key' => ['expire' => 1]],
        ]);
    }


    /**
     * Clean the collection of expired entries.
     */
    private function cleanExpired(): void
    {
        Logger::debug('store.mongo: Cleaning store.');

        $this->$collection->deleteMany(['expire' => ['$lt' => gmdate('Y-m-d H:i:s')]]);
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
        // TODO: optimize for mongoDB
        $record = $this->collection->findOne([
            '$and' => [
                ['type' => $type],
                ['key' => $key],
                ['$or' => [
                    ['expire' => null],
                    ['expire' => ['$gt' => gmdate('Y-m-d H:i:s')]],
                ]],
            ],
        ]);

        if ($record) {
            return unserialize($record['value']);
        }

        return null;
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

        // TODO: make this configurable
        if (rand(0, 1000) < 10) {
            $this->cleanExpired();
        }

        if ($expire !== null) {
            $expire = gmdate('Y-m-d H:i:s', $expire);
        }

        $value = serialize($value);

        $data = [
            'type'   => $type,
            'key'    => $key,
            'value'  => $value,
            'expire' => $expire,
        ];

        $this->collection->replaceOne(['type' => $type, 'key' => $key], $data, ['upsert' => true]);
    }


    /**
     * Delete an entry from the data store.
     *
     * @param string $type The type of the data
     * @param string $key The key to delete.
     */
    public function delete(string $type, string $key): void
    {
        $this->collection->deleteOne(['type' => $type, 'key' => $key]);
    }
}
