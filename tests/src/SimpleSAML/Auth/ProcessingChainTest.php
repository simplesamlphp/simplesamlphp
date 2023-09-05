<?php

declare(strict_types=1);

namespace Test\SimpleSAML\Auth;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Configuration;
use SimpleSAML\Module\core\Auth\Process\AttributeAdd;
use SimpleSAML\Module\core\Auth\Process\AttributeLimit;
use SimpleSAML\Module\core\Auth\Process\AttributeMap;

class ProcessingChainTest extends TestCase
{
    /**
     */
    protected function setUp(): void
    {
        Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');
    }


    public function testInsertAuthProcs(): void
    {
        $config = [];
        $authProcs = [
            new AttributeAdd($config, []),
            new AttributeMap($config, []),
        ];
        $state = [
            ProcessingChain::FILTERS_INDEX => [
                new AttributeLimit($config, [])
            ]
        ];
        $this->assertCount(1, $state[ProcessingChain::FILTERS_INDEX], 'Unexpected number of filters preinsert');

        ProcessingChain::insertFilters($state, $authProcs);

        $filterInChain = $state[ProcessingChain::FILTERS_INDEX];
        $this->assertCount(3, $filterInChain);
        $this->assertInstanceOf(AttributeAdd::class, $filterInChain[0]);
        $this->assertInstanceOf(AttributeMap::class, $filterInChain[1]);
        $this->assertInstanceOf(AttributeLimit::class, $filterInChain[2]);
    }

    public function testInsertAuthFromConfigs(): void
    {
        $config = [];
        $authProcsConfigs = [
            [
                'class' => 'core:AttributeAdd',
                'source' => ['myidp'],
            ],
        ];
        $state = [
            ProcessingChain::FILTERS_INDEX => [
                new AttributeLimit($config, [])
            ]
        ];
        $this->assertCount(1, $state[ProcessingChain::FILTERS_INDEX], 'Unexpected number of filters preinsert');

        ProcessingChain::createAndInsertFilters($state, $authProcsConfigs);

        $filterInChain = $state[ProcessingChain::FILTERS_INDEX];
        $this->assertCount(2, $filterInChain);
        $this->assertInstanceOf(AttributeAdd::class, $filterInChain[0]);
        $this->assertInstanceOf(AttributeLimit::class, $filterInChain[1]);
    }
}
