<?php

/**
 * Test for the SQLNameID helper class.
 *
 * @author Pavel Brousek <brousek@ics.muni.cz>
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\Test\Module\saml\IdP;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Module\saml\IdP\SQLNameID;
use SimpleSAML\Store;

class SQLNameIDTest extends TestCase
{
    /**
     * @param array $config
     * @return void
     */
    private function addGetDelete(array $config = [])
    {
        SQLNameID::add('idp', 'sp', 'user', 'value', $config);
        $this->assertEquals('value', SQLNameID::get('idp', 'sp', 'user', $config));
        SQLNameID::delete('idp', 'sp', 'user', $config);
        $this->assertNull(SQLNameID::get('idp', 'sp', 'user', $config));
    }

    /**
     * Test Store.
     * @test
     * @return void
     */
    public function testSQLStore()
    {
        Configuration::loadFromArray([
            'store.type'                    => 'sql',
            'store.sql.dsn'                 => 'sqlite::memory:',
            'store.sql.prefix'              => 'phpunit_',
        ], '[ARRAY]', 'simplesaml');
        $this->addGetDelete();
        $config = Configuration::getInstance();
        /** @var \SimpleSAML\Store $store */
        $store = Store::getInstance();
        $this->clearInstance($config, Configuration::class);
        $this->clearInstance($store, Store::class);
    }

    /**
     * Test incompatible Store.
     * @test
     * @return void
     */
    public function testIncompatibleStore()
    {
        Configuration::loadFromArray([
            'store.type'                    => 'memcache',
        ], '[ARRAY]', 'simplesaml');
        $store = Store::getInstance();
        $this->assertInstanceOf(Store\Memcache::class, $store);
        $this->expectException(Error\Exception::class);
        $this->addGetDelete();
        $config = Configuration::getInstance();
        /** @var \SimpleSAML\Store $store */
        $store = Store::getInstance();
        $this->clearInstance($config, Configuration::class);
        $this->clearInstance($store, Store::class);
    }

    /**
     * Test Database.
     * @test
     * @return void
     */
    public function testDatabase()
    {
        $config = [
            'database.dsn'        => 'sqlite::memory:',
            'database.username'   => null,
            'database.password'   => null,
            'database.prefix'     => 'phpunit_',
            'database.persistent' => true,
            'database.slaves'     => [
                [
                    'dsn'      => 'sqlite::memory:',
                    'username' => null,
                    'password' => null,
                ],
            ],
        ];
        $this->addGetDelete($config);
    }

    /**
     * @param \SimpleSAML\Configuration|\SimpleSAML\Store $service
     * @param string $className
     * @return void
     */
    protected function clearInstance($service, $className)
    {
        $reflectedClass = new \ReflectionClass($className);
        $reflectedInstance = $reflectedClass->getProperty('instance');
        $reflectedInstance->setAccessible(true);
        $reflectedInstance->setValue($service, null);
        $reflectedInstance->setAccessible(false);
    }
}
