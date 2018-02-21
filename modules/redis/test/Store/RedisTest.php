<?php
/* vim: set ts=4 sw=4 tw=0 et :*/

namespace Test;

class RedisTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        \SimpleSAML_Configuration::setConfigDir(__DIR__ . '/fixture/singlehost');
        \Predis\Client::$parameters = null;
        \Predis\Client::$options = null;
    }

    public function testDualRedisClientIsUsed()
    {
        \SimpleSAML_Configuration::setConfigDir(__DIR__ . '/fixture/oldhost');

        $expectedConstructorParams = [
            ['key2' => 'value2'],
            ['key1' => 'value1']
        ];
        $expectedConstructorOptions= [
            ['okey2' => 'ovalue2'],
            ['okey1' => 'ovalue1']
        ];

        $store = new \sspmod_redis_Store_Redis();

        $this->assertEquals($expectedConstructorParams, \Predis\Client::$parameters);
        $this->assertEquals($expectedConstructorOptions, \Predis\Client::$options);
    }

    public function testSetKeyInRedis()
    {
        $store = new \sspmod_redis_Store_Redis();
        $store->set('test', 'key', ['one', 'two']);

        $this->assertEquals('unittest.test.key', \Predis\Client::$setKey);
        $this->assertEquals(serialize(['one', 'two']), \Predis\Client::$setValue);
        $this->assertEquals('unittest.test.key', \Predis\Client::$expireKey);
        /**
         * Cannot be tested, because time is used and code is not in
         * namespace, so the normal trick does not work.
         */
        //$this->assertEquals(1427739616, \Predis\Client::$expireValue);
    }

    public function testSetKeyWithExpireInRedis()
    {
        $store = new \sspmod_redis_Store_Redis();
        $store->set('test', 'key', ['one', 'two'], 11);

        $this->assertEquals('unittest.test.key', \Predis\Client::$setKey);
        $this->assertEquals(serialize(['one', 'two']), \Predis\Client::$setValue);
        $this->assertEquals('unittest.test.key', \Predis\Client::$expireKey);
        $this->assertEquals(11, \Predis\Client::$expireValue);
    }

    public function testGetExistingKey()
    {
        $store = new \sspmod_redis_Store_Redis();
        $store->set('test', 'key', ['test' => 'value']);
        $res = $store->get('test', 'key');

        $this->assertEquals('unittest.test.key', \Predis\Client::$getKey);
        $this->assertEquals(['test' => 'value'], $res);
    }

    public function testGetNonExistingKey()
    {
        $store = new \sspmod_redis_Store_Redis();
        $res = $store->get('test', 'nokey');

        $this->assertEquals('unittest.test.nokey', \Predis\Client::$getKey);
        $this->assertNull($res);
    }

    public function testDeleteKey()
    {
        $store = new \sspmod_redis_Store_Redis();
        $res = $store->delete('test', 'nokey');

        $this->assertEquals('unittest.test.nokey', \Predis\Client::$deleteKey);
    }
}
