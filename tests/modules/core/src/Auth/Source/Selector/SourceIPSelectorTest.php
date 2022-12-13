<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\core\Auth\Source\Selector;

use Error;
use Exception;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module\core\Auth\Source\Selector\SourceIPSelector;

/**
 * @covers \SimpleSAML\Module\core\Auth\Source\AbstractSourceSelector
 * @covers \SimpleSAML\Module\core\Auth\Source\Selector\SourceIPSelector
 */
class SourceIPSelectorTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    private Configuration $config;

    /** @var \SimpleSAML\Configuration */
    private Configuration $sourceConfig;


    /**
     */
    public function setUp(): void
    {
        $this->config = Configuration::loadFromArray(
            ['module.enable' => ['core' => true]],
            '[ARRAY]',
            'simplesaml'
        );
        Configuration::setPreLoadedConfig($this->config, 'config.php');

        $this->sourceConfig = Configuration::loadFromArray([
            'selector' => [
                'core:SourceIPSelector',

                'zones' => [
                    'internal' => [
                        'source' => 'internal',
                        'subnet' => [
                            '10.0.0.0/8',
                            '2001:0DB8::/108',
                        ],
                    ],

                    'other' => [
                        'source' => 'other',
                        'subnet' => [
                            '172.16.0.0/12',
                            '2002:1234::/108',
                        ],
                    ],

                    'default' => 'external',
                ],
            ],

            'other' => [
                'core:AdminPassword',
            ],

            'internal' => [
                'core:AdminPassword',
            ],

            'external' => [
                'core:AdminPassword',
            ],
        ]);
        Configuration::setPreLoadedConfig($this->sourceConfig, 'authsources.php');
    }


    /**
     */
    public function testDefaultZoneIsRequired(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected the key "default" to exist.');

        $sourceConfig = Configuration::loadFromArray([
            'selector' => [
                'core:SourceI{Selector',

                'zones' => [
                    'internal' => [],
                ],
            ],
        ]);
        Configuration::setPreLoadedConfig($sourceConfig, 'authsources.php');

        new SourceIPSelector(['AuthId' => 'selector'], $sourceConfig->getArray('selector'));
    }


    /**
     */
    public function testAuthentication(): void
    {
        $info = ['AuthId' => 'selector'];
        $config = $this->sourceConfig->getArray('selector');

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = '/';

        $selector = new class ($info, $config) extends SourceIPSelector {
            /**
             * @param \SimpleSAML\Auth\Source $as
             * @param array $state
             * @return void
             */
            public static function doAuthentication(Auth\Source $as, array $state): void
            {
                // Dummy
            }
        };

        $state = [];
        $result = $selector->authenticate($state);
        $this->assertNull($result);
    }


    /**
     * @dataProvider provideClientIP
     * @param string $ip  The client IP
     * @param string $expected  The expected authsource
     */
    public function testSelectAuthSource(string $ip, string $expected): void
    {
        $info = ['AuthId' => 'selector'];
        $config = $this->sourceConfig->getArray('selector');

        $_SERVER['REMOTE_ADDR'] = $ip;

        $selector = new class ($info, $config) extends SourceIPSelector {
            public function selectAuthSource(array &$state): string
            {
                return parent::selectAuthSource($state);
            }
        };

        $state = [];
        $source = $selector->selectAuthSource($state);
        $this->assertEquals($expected, $source);
    }


    /**
     * @return string
     */
    public function provideClientIP(): array
    {
        return [
            ['127.0.0.2', 'external'],
            ['10.4.13.2', 'internal'],
            ['2001:0DB8:0000:0000:0000:0000:0000:0000', 'internal'],
            ['145.21.93.97', 'external'],
            ['172.16.1.2', 'other'],
            ['2002:1234:0000:0000:0000:0000:0000:0000', 'other'],
        ];
    }
}
