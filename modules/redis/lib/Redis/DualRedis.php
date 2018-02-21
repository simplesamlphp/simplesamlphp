<?php
/* vim: set ts=4 sw=4 tw=0 et :*/

class sspmod_redis_Redis_DualRedis
{
    public function __construct($oldHost, $newHost)
    {
        $this->oldHost = $oldHost;
        $this->newHost = $newHost;
    }

    public function get($key)
    {
        $res = $this->newHost->get($key);
        if (is_null($res)) {
            $res = $this->oldHost->get($key);
        }
        return $res;
    }

    public function set($key, $value)
    {
        $this->newHost->set($key, $value);
    }

    public function del($key)
    {
        $this->newHost->del($key);
        $this->oldHost->del($key);
    }

    public function expireat($key, $timestamp)
    {
        $this->newHost->expireat($key, $timestamp);
    }
}
