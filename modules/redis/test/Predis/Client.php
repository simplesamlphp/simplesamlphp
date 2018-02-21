<?php
/* vim: set ts=4 sw=4 tw=0 et :*/

namespace Predis;

/**
 * Mock implenmentation
 */
class Client
{
    public static $parameters = [];
    public static $options = [];
    public static $setKey = null;
    public static $setValue = null;
    public static $getKey = null;
    public static $expireKey = null;
    public static $expireValue = null;
    public static $deleteKey = null;
    public static $values = [];

    public function __construct($parameters = null, $options = null)
    {
        self::$parameters[] = $parameters;
        self::$options[] = $options;
    }

    public function get($key)
    {
        self::$getKey = $key;

        if (isset(self::$values[$key])) {
            return self::$values[$key];
        }

        return null;
    }

    public function set($key, $value)
    {
        self::$setKey = $key;
        self::$setValue = $value;
        self::$values[$key] = $value;
    }

    public function expireat($key, $expire)
    {
        self::$expireKey = $key;
        self::$expireValue = $expire;
    }

    public function del($key)
    {
        self::$deleteKey = $key;
    }
}
