<?php

namespace SimpleSAML\Test\Store;

use \SimpleSAML_Configuration as Configuration;
use \SimpleSAML\Store;

/**
 * Tests for the Redis Sentinel store.
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source
 * code.
 *
 * @package simplesamlphp/simplesamlphp
 */
class RedisSentinelTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->config = array();

        $this->mocked_redis = $this->getMockBuilder('Predis\Client')
                                   ->setMethods(array('get', 'set', 'setex', 'del', 'disconnect'))
                                   ->disableOriginalConstructor()
                                   ->getMock();

        $this->mocked_redis->method('get')
                           ->will($this->returnCallback(array($this, 'getMocked')));

        $this->mocked_redis->method('set')
                           ->will($this->returnCallback(array($this, 'setMocked')));

        $this->mocked_redis->method('setex')
                           ->will($this->returnCallback(array($this, 'setexMocked')));

        $this->mocked_redis->method('del')
                           ->will($this->returnCallback(array($this, 'delMocked')));

        $nop = function () {
            return;
        };

        $this->mocked_redis->method('disconnect')
                           ->will($this->returnCallback($nop));

        $this->redis = new Store\RedisSentinel($this->mocked_redis);
    }

    public function getMocked($key)
    {
        return array_key_exists($key, $this->config) ? $this->config[$key] : false;
    }

    public function setMocked($key, $value)
    {
        $this->config[$key] = $value;
    }

    public function setexMocked($key, $expire, $value)
    {
        // Testing expiring data is more trouble than it's worth for now
        $this->setMocked($key, $value);
    }

    public function delMocked($key)
    {
        unset($this->config[$key]);
    }

    /**
     * @covers \SimpleSAML\Store::getInstance
     * @covers \SimpleSAML\Store\RedisSentinel::__construct
     * @test
     */
    public function testRedisSentinelInstance()
    {
        $config = Configuration::loadFromArray(array(
            'store.type' => 'redissentinel',
            'store.redissentinel.prefix' => 'phpunit_',
            'store.redissentinel.mastergroup' => 'phpunit_mastergroup',
            'store.redissentinel.sentinels' => array('tcp://sentinel1', 'tcp://sentinel2', 'tcp://sentinel3'),
        ), '[ARRAY]', 'simplesaml');

        $store = Store::getInstance();

        $this->assertInstanceOf('SimpleSAML\Store\RedisSentinel', $store);

        $this->clearInstance($config, '\SimpleSAML_Configuration');
        $this->clearInstance($store, '\SimpleSAML\Store');
    }

    /**
     * @covers \SimpleSAML\Store\RedisSentinel::get
     * @covers \SimpleSAML\Store\RedisSentinel::set
     * @test
     */
    public function testInsertData()
    {
        $value = 'TEST';

        $this->redis->set('test', 'key', $value);
        $res = $this->redis->get('test', 'key');
        $expected = $value;

        $this->assertEquals($expected, $res);
    }

    /**
     * @covers \SimpleSAML\Store\RedisSentinel::get
     * @covers \SimpleSAML\Store\RedisSentinel::set
     * @test
     */
    public function testInsertExpiringData()
    {
        $value = 'TEST';

        $this->redis->set('test', 'key', $value, $expire = 80808080);
        $res = $this->redis->get('test', 'key');
        $expected = $value;

        $this->assertEquals($expected, $res);
    }

    /**
     * @covers \SimpleSAML\Store\RedisSentinel::get
     * @test
     */
    public function testGetEmptyData()
    {
        $res = $this->redis->get('test', 'key');

        $this->assertNull($res);
    }

    /**
     * @covers \SimpleSAML\Store\RedisSentinel::get
     * @covers \SimpleSAML\Store\RedisSentinel::set
     * @test
     */
    public function testOverwriteData()
    {
        $value1 = 'TEST1';
        $value2 = 'TEST2';

        $this->redis->set('test', 'key', $value1);
        $this->redis->set('test', 'key', $value2);
        $res = $this->redis->get('test', 'key');
        $expected = $value2;

        $this->assertEquals($expected, $res);
    }

    /**
     * @covers \SimpleSAML\Store\RedisSentinel::get
     * @covers \SimpleSAML\Store\RedisSentinel::set
     * @covers \SimpleSAML\Store\RedisSentinel::delete
     * @test
     */
    public function testDeleteData()
    {
        $this->redis->set('test', 'key', 'TEST');
        $this->redis->delete('test', 'key');
        $res = $this->redis->get('test', 'key');

        $this->assertNull($res);
    }

    protected function clearInstance($service, $className)
    {
        $reflectedClass = new \ReflectionClass($className);
        $reflectedInstance = $reflectedClass->getProperty('instance');
        $reflectedInstance->setAccessible(true);
        $reflectedInstance->setValue($service, null);
        $reflectedInstance->setAccessible(false);
    }
}
