<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml\IdP;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Module\saml\IdP\SQLNameID;
use SimpleSAML\Store;
use SimpleSAML\Store\StoreFactory;

/**
 * Test for the SQLNameID helper class.
 *
 * @covers \SimpleSAML\Module\saml\IdP\SQLNameID
 *
 * @package SimpleSAMLphp
 */
class SQLNameIDTest extends TestCase
{
    /**
     * @param array $config
     */
    private function addGetDelete(array $config = []): void
    {
        SQLNameID::add('idp', 'sp', 'user', 'value', $config);
        $this->assertEquals('value', SQLNameID::get('idp', 'sp', 'user', $config));
        SQLNameID::delete('idp', 'sp', 'user', $config);
        $this->assertNull(SQLNameID::get('idp', 'sp', 'user', $config));
    }


    /**
     * Test Store.
     * @test
     */
    public function testSQLStore(): void
    {
        Configuration::loadFromArray([
            'store.type'                    => 'sql',
            'store.sql.dsn'                 => 'sqlite::memory:',
            'store.sql.prefix'              => 'phpunit_',
        ], '[ARRAY]', 'simplesaml');
        $this->addGetDelete();
        $config = Configuration::getInstance();
        $storeType = $config->getString('store.type');
        /** @var \SimpleSAML\Store\StoreInterface $store */
        $store = StoreFactory::getInstance($storeType);
        $this->clearInstance($config, Configuration::class);
        $this->clearInstance($store, StoreFactory::class);
    }


    /**
     * Test incompatible Store.
     * @test
     */
    public function testIncompatibleStore(): void
    {
        Configuration::loadFromArray([
            'store.type'                    => 'memcache',
        ], '[ARRAY]', 'simplesaml');
        $config = Configuration::getInstance();
        $storeType = $config->getString('store.type');
        $store = StoreFactory::getInstance($storeType);
        $this->assertInstanceOf(Store\MemcacheStore::class, $store);
        $this->expectException(Error\Exception::class);
        $this->addGetDelete();
        $config = Configuration::getInstance();
        $storeType = $config->getString('store.type');
        /** @var \SimpleSAML\Store\StoreInterface $store */
        $store = StoreFactory::getInstance($storeType);
        $this->clearInstance($config, Configuration::class);
        $this->clearInstance($store, StoreFactory::class);
    }


    /**
     * Test Database.
     * @test
     */
    public function testDatabase(): void
    {
        $config = [
            'database.dsn'         => 'sqlite::memory:',
            'database.username'    => '',
            'database.password'    => '',
            'database.prefix'      => 'phpunit_',
            'database.persistent'  => true,
            'database.secondaries' => [
                [
                    'dsn'      => 'sqlite::memory:',
                    'username' => '',
                    'password' => '',
                ],
            ],
        ];
        $this->addGetDelete($config);
    }


    /**
     * @param \SimpleSAML\Configuration|\SimpleSAML\Store\StoreInterface $service
     * @param class-string $className
     */
    protected function clearInstance($service, string $className): void
    {
        $reflectedClass = new ReflectionClass($className);
        $reflectedInstance = $reflectedClass->getProperty('instance');
        $reflectedInstance->setAccessible(true);
        if ($service instanceof Configuration) {
            $reflectedInstance->setValue($service, []);
        } else {
            $reflectedInstance->setValue($service, null);
        }
        $reflectedInstance->setAccessible(false);
    }
}
