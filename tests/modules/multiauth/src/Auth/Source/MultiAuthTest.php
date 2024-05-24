<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\multiauth\Auth\Source;

use Error;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\Configuration;
use SimpleSAML\Module\multiauth\Auth\Source\MultiAuth;
use SimpleSAML\TestUtils\ClearStateTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 */
#[CoversClass(MultiAuth::class)]
class MultiAuthTest extends ClearStateTestCase
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
            ['module.enable' => ['multiauth' => true]],
            '[ARRAY]',
            'simplesaml'
        );
        Configuration::setPreLoadedConfig($this->config, 'config.php');

        $this->sourceConfig = Configuration::loadFromArray([
            'example-multi' => [
                'multiauth:MultiAuth',

                /*
                 * The available authentication sources.
                 * They must be defined in this authsources.php file.
                 */
                'sources' => [
                    'example-saml' => [
                        'text' => [
                            'en' => 'Log in using a SAML SP',
                            'es' => 'Entrar usando un SP SAML',
                        ],
                        'css-class' => 'SAML',
                    ],
                    'example-admin' => [
                        'text' => [
                            'en' => 'Log in using the admin password',
                            'es' => 'Entrar usando la contraseña de administrador',
                        ],
                    ],
                ],
                'preselect' => 'example-saml',
            ],

            'example-saml' => [
                'saml:SP',
                'entityId' => 'my-entity-id',
                'idp' => 'my-idp',
            ],

            'example-admin' => [
                'core:AdminPassword',
            ],
        ]);
        Configuration::setPreLoadedConfig($this->sourceConfig, 'authsources.php');
    }


    /**
     */
    public function testSourcesMustBePresent(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The required "sources" config option was not found');
        $sourceConfig = Configuration::loadFromArray([
            'example-multi' => [
                'multiauth:MultiAuth',
            ],
        ]);

        Configuration::setPreLoadedConfig($sourceConfig, 'authsources.php');

        new MultiAuth(['AuthId' => 'example-multi'], $sourceConfig->getArray('example-multi'));
    }


    /**
     */
    public function testPreselectMustBeValid(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The optional "preselect" config option must be present in "sources"');
        $sourceConfig = Configuration::loadFromArray([
            'example-multi' => [
                'multiauth:MultiAuth',

                /*
                 * The available authentication sources.
                 * They must be defined in this authsources.php file.
                 */
                'sources' => [
                    'example-saml' => [
                        'text' => [
                            'en' => 'Log in using a SAML SP',
                            'es' => 'Entrar usando un SP SAML',
                        ],
                        'css-class' => 'SAML',
                    ],
                    'example-admin' => [
                        'text' => [
                            'en' => 'Log in using the admin password',
                            'es' => 'Entrar usando la contraseña de administrador',
                        ],
                    ],
                ],
                'preselect' => 'other',
            ],

            'example-saml' => [
                'saml:SP',
                'entityId' => 'my-entity-id',
                'idp' => 'my-idp',
            ],

            'example-admin' => [
                'core:AdminPassword',
            ],
        ]);

        Configuration::setPreLoadedConfig($sourceConfig, 'authsources.php');
        new MultiAuth(['AuthId' => 'example-multi'], $sourceConfig->getArray('example-multi'));
    }


    /**
     */
    public function testPreselectIsOptional(): void
    {
        $sourceConfig = Configuration::loadFromArray([
            'example-multi' => [
                'multiauth:MultiAuth',

                /*
                 * The available authentication sources.
                 * They must be defined in this authsources.php file.
                 */
                'sources' => [
                    'example-saml' => [
                        'text' => [
                            'en' => 'Log in using a SAML SP',
                            'es' => 'Entrar usando un SP SAML',
                        ],
                        'css-class' => 'SAML',
                    ],
                    'example-admin' => [
                        'text' => [
                            'en' => 'Log in using the admin password',
                            'es' => 'Entrar usando la contraseña de administrador',
                        ],
                    ],
                ],
            ],

            'example-saml' => [
                'saml:SP',
                'entityId' => 'my-entity-id',
                'idp' => 'my-idp',
            ],

            'example-admin' => [
                'core:AdminPassword',
            ],
        ]);

        Configuration::setPreLoadedConfig($sourceConfig, 'authsources.php');

        $state = [];
        $source = new MultiAuth(['AuthId' => 'example-multi'], $sourceConfig->getArray('example-multi'));
        $request = Request::createFromGlobals();

        try {
            $source->authenticate($request, $state);
        } catch (Error $e) {
        } catch (Exception $e) {
        }

        $this->assertArrayNotHasKey('multiauth:preselect', $state);
    }


    /**
     */
    public function testPreselectCanBeConfigured(): void
    {
        $state = [];

        $request = Request::createFromGlobals();
        $source = new MultiAuth(['AuthId' => 'example-multi'], $this->sourceConfig->getArray('example-multi'));

        try {
            $source->authenticate($request, $state);
        } catch (Exception $e) {
        }

        $this->assertArrayHasKey('multiauth:preselect', $state);
        $this->assertEquals('example-saml', $state['multiauth:preselect']);
    }


    /**
     */
    public function testStatePreselectHasPriority(): void
    {
        $request = Request::createFromGlobals();
        $state = ['multiauth:preselect' => 'example-admin'];

        $source = new MultiAuth(['AuthId' => 'example-multi'], $this->sourceConfig->getArray('example-multi'));

        try {
            $source->authenticate($request, $state);
        } catch (Exception $e) {
        }

        $this->assertArrayHasKey('multiauth:preselect', $state);
        $this->assertEquals('example-admin', $state['multiauth:preselect']);
    }
}
