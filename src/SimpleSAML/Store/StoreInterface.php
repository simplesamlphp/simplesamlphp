<?php

declare(strict_types=1);

namespace SimpleSAML\Store;

/**
 * An interface describing data stores.
 *
 * @package simplesamlphp/simplesamlphp
 */
interface StoreInterface
{
    /**
     * Retrieve a value from the data store.
     *
     * @param string $type The data type.
     * @param string $key The key.
     *
     * @return mixed|null The value.
     */
    public function get(string $type, string $key): mixed;


    /**
     * Save a value to the data store.
     *
     * @param string   $type The data type.
     * @param string   $key The key.
     * @param mixed    $value The value.
     * @param int|null $expire The expiration time (unix timestamp), or null if it never expires.
     */
    public function set(string $type, string $key, mixed $value, ?int $expire = null): void;


    /**
     * Delete a value from the data store.
     *
     * @param string $type The data type.
     * @param string $key The key.
     */
    public function delete(string $type, string $key): void;
}
