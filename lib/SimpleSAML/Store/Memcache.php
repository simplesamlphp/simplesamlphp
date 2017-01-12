<?php

namespace SimpleSAML\Store;

use \SimpleSAML_Configuration as Configuration;
use \SimpleSAML\Store;

/**
 * A memcache based data store.
 *
 * @package SimpleSAMLphp
 */
class Memcache extends Store
{
    /**
     * This variable contains the session name prefix.
     *
     * @var string
     */
    private $prefix;


    /**
     * This function implements the constructor for this class. It loads the Memcache configuration.
     */
    protected function __construct()
    {
        $config = Configuration::getInstance();
        $this->prefix = $config->getString('memcache_store.prefix', 'simpleSAMLphp');
    }


    /**
     * Retrieve a value from the data store.
     *
     * @param string $type The data type.
     * @param string $key The key.
     * @return mixed|null The value.
     */
    public function get($type, $key)
    {
        assert('is_string($type)');
        assert('is_string($key)');

        return \SimpleSAML_Memcache::get($this->prefix . '.' . $type . '.' . $key);
    }


    /**
     * Save a value to the data store.
     *
     * @param string $type The data type.
     * @param string $key The key.
     * @param mixed $value The value.
     * @param int|NULL $expire  The expiration time (unix timestamp), or NULL if it never expires.
     */
    public function set($type, $key, $value, $expire = null)
    {
        assert('is_string($type)');
        assert('is_string($key)');
        assert('is_null($expire) || (is_int($expire) && $expire > 2592000)');

        if ($expire === null) {
            $expire = 0;
        }

        \SimpleSAML_Memcache::set($this->prefix . '.' . $type . '.' . $key, $value, $expire);
    }


    /**
     * Delete a value from the data store.
     *
     * @param string $type The data type.
     * @param string $key The key.
     */
    public function delete($type, $key)
    {
        assert('is_string($type)');
        assert('is_string($key)');

        \SimpleSAML_Memcache::delete($this->prefix . '.' . $type . '.' . $key);
    }
}
