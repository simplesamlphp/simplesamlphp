<?php

namespace SimpleSAML\Test\Module\multiauth\Auth\Source;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Module\multiauth\Auth\Source\MultiAuth;

class MultiAuthTest extends \SimpleSAML\Test\Utils\ClearStateTestCase
{
    /** @var Configuration */
    private $sourceConfig;

    public function setUp()
    {
        $this->config = Configuration::loadFromArray(['module.enable' => ['multiauth' => true]], '[ARRAY]', 'simplesaml');
        Configuration::setPreLoadedConfig($this->config, 'config.php');

        $this->sourceConfig = Configuration::loadFromArray(array(
            'example-multi' => array(
                'multiauth:MultiAuth',

                /*
                 * The available authentication sources.
                 * They must be defined in this authsources.php file.
                 */
                'sources' => array(
                    'example-saml' => array(
                        'text' => array(
                            'en' => 'Log in using a SAML SP',
                            'es' => 'Entrar usando un SP SAML',
                        ),
                        'css-class' => 'SAML',
                    ),
                    'example-admin' => array(
                        'text' => array(
                            'en' => 'Log in using the admin password',
                            'es' => 'Entrar usando la contraseña de administrador',
                        ),
                    ),
                ),
                'preselect' => 'example-saml',
            ),

            'example-saml' => array(
                'saml:SP',
                'entityId' => 'my-entity-id',
                'idp' => 'my-idp',
            ),

            'example-admin' => array(
                'core:AdminPassword',
            ),
        ));
        Configuration::setPreLoadedConfig($this->sourceConfig, 'authsources.php');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage The required "sources" config option was not found
     */
    public function testSourcesMustBePresent()
    {
        $sourceConfig = Configuration::loadFromArray(array(
            'example-multi' => array(
                'multiauth:MultiAuth',
            ),
        ));

        Configuration::setPreLoadedConfig($sourceConfig, 'authsources.php');

        new MultiAuth(['AuthId' => 'example-multi'], $sourceConfig->getArray('example-multi'));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage The optional "preselect" config option must be present in "sources"
     */
    public function testPreselectMustBeValid()
    {
        $sourceConfig = Configuration::loadFromArray(array(
            'example-multi' => array(
                'multiauth:MultiAuth',

                /*
                 * The available authentication sources.
                 * They must be defined in this authsources.php file.
                 */
                'sources' => array(
                    'example-saml' => array(
                        'text' => array(
                            'en' => 'Log in using a SAML SP',
                            'es' => 'Entrar usando un SP SAML',
                        ),
                        'css-class' => 'SAML',
                    ),
                    'example-admin' => array(
                        'text' => array(
                            'en' => 'Log in using the admin password',
                            'es' => 'Entrar usando la contraseña de administrador',
                        ),
                    ),
                ),
                'preselect' => 'other',
            ),

            'example-saml' => array(
                'saml:SP',
                'entityId' => 'my-entity-id',
                'idp' => 'my-idp',
            ),

            'example-admin' => array(
                'core:AdminPassword',
            ),
        ));

        Configuration::setPreLoadedConfig($sourceConfig, 'authsources.php');
        new MultiAuth(['AuthId' => 'example-multi'], $sourceConfig->getArray('example-multi'));
    }

    public function testPreselectIsOptional()
    {
        $sourceConfig = Configuration::loadFromArray(array(
            'example-multi' => array(
                'multiauth:MultiAuth',

                /*
                 * The available authentication sources.
                 * They must be defined in this authsources.php file.
                 */
                'sources' => array(
                    'example-saml' => array(
                        'text' => array(
                            'en' => 'Log in using a SAML SP',
                            'es' => 'Entrar usando un SP SAML',
                        ),
                        'css-class' => 'SAML',
                    ),
                    'example-admin' => array(
                        'text' => array(
                            'en' => 'Log in using the admin password',
                            'es' => 'Entrar usando la contraseña de administrador',
                        ),
                    ),
                ),
            ),

            'example-saml' => array(
                'saml:SP',
                'entityId' => 'my-entity-id',
                'idp' => 'my-idp',
            ),

            'example-admin' => array(
                'core:AdminPassword',
            ),
        ));

        Configuration::setPreLoadedConfig($sourceConfig, 'authsources.php');

        $state = [];
        $source = new MultiAuth(['AuthId' => 'example-multi'], $sourceConfig->getArray('example-multi'));

        try {
            $source->authenticate($state);
        } catch (\Error $e) {
        } catch (\Exception $e) {
        }

        $this->assertArrayNotHasKey('multiauth:preselect', $state);
    }

    public function testPreselectCanBeConfigured()
    {
        $state = [];

        $source = new MultiAuth(['AuthId' => 'example-multi'], $this->sourceConfig->getArray('example-multi'));

        try {
            $source->authenticate($state);
        } catch (\Exception $e) {
        }

        $this->assertArrayHasKey('multiauth:preselect', $state);
        $this->assertEquals('example-saml', $state['multiauth:preselect']);
    }

    public function testStatePreselectHasPriority()
    {
        $state = ['multiauth:preselect' => 'example-admin'];

        $source = new MultiAuth(['AuthId' => 'example-multi'], $this->sourceConfig->getArray('example-multi'));

        try {
            $source->authenticate($state);
        } catch (\Exception $e) {
        }

        $this->assertArrayHasKey('multiauth:preselect', $state);
        $this->assertEquals('example-admin', $state['multiauth:preselect']);
    }
}
