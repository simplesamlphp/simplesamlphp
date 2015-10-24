<?php

/**
 * A memcache based datastore.
 *
 * @package simpleSAMLphp
 */
class SimpleSAML_Store_Memcache extends SimpleSAML_Store
{
	/**
	 * Initialize the memcache datastore.
	 */
	protected function __construct()
	{
	}


	/**
	 * Retrieve a value from the datastore.
	 *
	 * @param string $type  The datatype.
	 * @param string $key  The key.
	 * @return mixed|NULL  The value.
	 */
	public function get($type, $key)
	{
		assert('is_string($type)');
		assert('is_string($key)');

		$config = SimpleSAML_Configuration::getInstance();
		$name = $config->getString('memcache_store.name', 'simpleSAMLphp');

		return SimpleSAML_Memcache::get($name . '.' . $type . '.' . $key);
	}


	/**
	 * Save a value to the datastore.
	 *
	 * @param string $type  The datatype.
	 * @param string $key  The key.
	 * @param mixed $value  The value.
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

		$config = SimpleSAML_Configuration::getInstance();
		$name = $config->getString('memcache_store.name', 'simpleSAMLphp');

		SimpleSAML_Memcache::set($name . '.' . $type . '.' . $key, $value, $expire);
	}


	/**
	 * Delete a value from the datastore.
	 *
	 * @param string $type  The datatype.
	 * @param string $key  The key.
	 */
	public function delete($type, $key)
	{
		assert('is_string($type)');
		assert('is_string($key)');

		$config = SimpleSAML_Configuration::getInstance();
		$name = $config->getString('memcache_store.name', 'simpleSAMLphp');

		SimpleSAML_Memcache::delete($name . '.' . $type . '.' . $key);
	}

}
