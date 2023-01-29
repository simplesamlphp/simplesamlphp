<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\core\Auth\Source\Selector;

use PHPUnit\Framework\TestCase;
use SAML2\Exception\Protocol\NoAuthnContextException;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Module\core\Auth\Source\Selector\RequestedAuthnContextSelector;

/**
 * @covers \SimpleSAML\Module\core\Auth\Source\AbstractSourceSelector
 * @covers \SimpleSAML\Module\core\Auth\Source\Selector\RequestedAuthnContextSelector
 */
class RequestedAuthnContextSelectorTest extends TestCase
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
            'simplesaml',
        );
        Configuration::setPreLoadedConfig($this->config, 'config.php');

        $this->sourceConfig = Configuration::loadFromArray([
            'selector' => [
                'core:RequestedAuthnContextSelector',

                'contexts' => [
                    10 => [
                        'identifier' => 'urn:x-simplesamlphp:loa1',
                        'source' => 'loa1',
                    ],

                    20 => [
                        'identifier' => 'urn:x-simplesamlphp:loa2',
                        'source' => 'loa2',
                    ],

                    30 => [
                        'identifier' => 'urn:x-simplesamlphp:loa3',
                        'source' => 'loa3',
                    ],

                    'default' => 'loa1',
                ],
            ],

            'loa1' => [
                'core:AdminPassword',
            ],

            'loa2' => [
                'core:AdminPassword',
            ],

            'loa3' => [
                'core:AdminPassword',
            ],
        ]);

        Configuration::setPreLoadedConfig($this->sourceConfig, 'authsources.php');
    }


    /**
     */
    public function testAuthentication(): void
    {
        $info = ['AuthId' => 'selector'];
        $config = $this->sourceConfig->getArray('selector');

        $selector = new class ($info, $config) extends RequestedAuthnContextSelector {
            /**
             * @param \SimpleSAML\Auth\Source $as
             * @param array $state
             * @return void
             */
            public static function doAuthentication(Auth\Source $as, array $state): void
            {
                // Dummy
            }

            /**
             * @param array &$state
             * @return void
             */
            public function authenticate(array &$state): void
            {
                $state['finished'] = true;
            }
        };

        $state = ['saml:RequestedAuthnContext' => ['AuthnContextClassRef' => null]];
        $selector->authenticate($state);
        $this->assertTrue($state['finished']);
    }


    /**
     */
    public function testIncompleteConfigurationThrowsExceptionVariant1(): void
    {
        $sourceConfig = Configuration::loadFromArray([
            'selector' => [
                'core:RequestedAuthnContextSelector',

                'contexts' => [
                    10 => [
                        'identifier' => 'urn:x-simplesamlphp:loa1',
                    ],
                ],
            ],
        ]);

        Configuration::setPreLoadedConfig($this->sourceConfig, 'authsources.php');

        $info = ['AuthId' => 'selector'];
        $config = $sourceConfig->getArray('selector');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Incomplete context '10' due to missing `source` key.");

        new RequestedAuthnContextSelector($info, $config);
    }


    /**
     */
    public function testIncompleteConfigurationThrowsExceptionVariant2(): void
    {
        $sourceConfig = Configuration::loadFromArray([
            'selector' => [
                'core:RequestedAuthnContextSelector',

                'contexts' => [
                    10 => [
                        'source' => 'loa1',
                    ],
                ],
            ],
        ]);

        Configuration::setPreLoadedConfig($this->sourceConfig, 'authsources.php');

        $info = ['AuthId' => 'selector'];
        $config = $sourceConfig->getArray('selector');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Incomplete context '10' due to missing `identifier` key.");

        new RequestedAuthnContextSelector($info, $config);
    }


    /**
     * @dataProvider provideRequestedAuthnContext
     * @param array $requestedAuthnContext  The RequestedAuthnContext
     * @param string $expected  The expected authsource
     */
    public function testSelectAuthSource(array $requestedAuthnContext, string $expected): void
    {
        $info = ['AuthId' => 'selector'];
        $config = $this->sourceConfig->getArray('selector');

        $selector = new class ($info, $config) extends RequestedAuthnContextSelector {
            public function selectAuthSource(array &$state): string
            {
                return parent::selectAuthSource($state);
            }
        };

        $state = ['saml:RequestedAuthnContext' => $requestedAuthnContext];

        try {
            $source = $selector->selectAuthSource($state);
        } catch (AssertionFailedException | NoAuthnContextException | Exception $e) {
            $source = $e::class;
        }

        $this->assertEquals($expected, $source);
    }


    /**
     * @return array
     */
    public function provideRequestedAuthnContext(): array
    {
        return [
            // Normal use-case - No RequestedAuthnContext provided
            [
                ['AuthnContextClassRef' => null],
                'loa1',
            ],

            // Normal use-case
            [
                [
                    'AuthnContextClassRef' => [
                        'urn:x-simplesamlphp:loa1',
                    ],
                    'Comparison' => 'exact',
                ],
                'loa1',
            ],

            // Order is important - see specs
            [
                [
                    'AuthnContextClassRef' => [
                        'urn:x-simplesamlphp:loa1',
                        'urn:x-simplesamlphp:loa2',
                    ],
                    'Comparison' => 'exact',
                ],
                'loa1',
            ],
            [
                [
                    'AuthnContextClassRef' => [
                        'urn:x-simplesamlphp:loa2',
                        'urn:x-simplesamlphp:loa1',
                    ],
                    'Comparison' => 'exact',
                ],
                'loa2',
            ],

            // Unknown context requested
            [
                [
                    'AuthnContextClassRef' => [
                        'urn:x-simplesamlphp:loa4',
                    ],
                    'Comparison' => 'exact',
                ],
                NoAuthnContextException::class,
            ],

            // Unknown comparison requested
            [
                [
                    'AuthnContextClassRef' => [
                        'urn:x-simplesamlphp:loa2',
                    ],
                    'Comparison' => 'phpunit',
                ],
                AssertionFailedException::class,
            ],

            // Non-implemented comparison requested
            [
                [
                    'AuthnContextClassRef' => [
                        'urn:x-simplesamlphp:loa2',
                    ],
                    'Comparison' => 'minimum',
                ],
                Exception::class,
            ],
            [
                [
                    'AuthnContextClassRef' => [
                        'urn:x-simplesamlphp:loa2',
                    ],
                    'Comparison' => 'maximum',
                ],
                Exception::class,
            ],
            [
                [
                    'AuthnContextClassRef' => [
                        'urn:x-simplesamlphp:loa2',
                    ],
                    'Comparison' => 'better',
                ],
                Exception::class,
            ],
        ];
    }
}
