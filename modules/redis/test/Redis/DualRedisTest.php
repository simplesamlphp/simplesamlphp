<?php
/* vim: set ts=4 sw=4 tw=0 et :*/

namespace Test;

class DualRedisTest extends \PHPUnit_Framework_TestCase
{
    public function testGetValueFromNewHost()
    {
        $mock = $this->getMock('\Predis\Client', ['get'], [null]);
        $mock->expects($this->once())
            ->method("get")
            ->with('xyzzy')
            ->will($this->returnValue(42));

        $redis = new \sspmod_redis_Redis_DualRedis(null, $mock);
        $this->assertsame(42, $redis->get("xyzzy"));
    }

    public function testGetValueFromOldHost()
    {
        $redis = new \sspmod_redis_Redis_DualRedis(
            $this->predisClientExpectingGet("xyzzy", 420),
            $this->predisClientExpectingGet("xyzzy", null)
        );
        $this->assertsame(420, $redis->get("xyzzy"));
    }

    private function predisClientExpectingGet($key, $value)
    {
        $mock = $this->getMock('\Predis\Client', ['get'], [null]);
        $mock->expects($this->once())
            ->method("get")
            ->with($key)
            ->will($this->returnValue($value));
        return $mock;
    }

    public function testiSetValueOnNewHost()
    {
        $mock = $this->getMock('\Predis\Client', ['set'], [null]);
        $mock->expects($this->once())
            ->method("set")
            ->with('xyzzy', 42);

        $redis = new \sspmod_redis_Redis_DualRedis(null, $mock);
        $redis->set("xyzzy", 42);
    }

    public function testValueIsDeletedOnBothHosts()
    {
        $redis = new \sspmod_redis_Redis_DualRedis(
            $this->predisClientExpectingDelete("quux"),
            $this->predisClientExpectingDelete("quux")
        );
        $redis->del("quux");
    }

    private function predisClientExpectingDelete($key)
    {
        $mock = $this->getMock('\Predis\Client', ['del'], [null]);
        $mock->expects($this->once())
            ->method("del")
            ->with($key);
        return $mock;
    }

    public function testExpireatIsSetOnNewHost()
    {
        $mock = $this->getMock('\Predis\Client', ['expireat'], [null]);
        $mock->expects($this->once())
            ->method("expireat")
            ->with('quux', 1234);

        $redis = new \sspmod_redis_Redis_DualRedis(null, $mock);
        $redis->expireat("quux", 1234);
    }
}
