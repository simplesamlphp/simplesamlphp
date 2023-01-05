<?php

declare(strict_types=1);

namespace SimpleSAML\Test;

use Exception;
use SAML2\Constants;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\TestUtils\ClearStateTestCase;

/**
 * Tests for \SimpleSAML\Configuration
 *
 * @covers \SimpleSAML\Configuration
 */
class ConfigurationTest extends ClearStateTestCase
{
    /**
     * Test \SimpleSAML\Configuration::getVersion()
     */
    public function testGetVersion(): void
    {
        $c = Configuration::getOptionalConfig();
        $this->assertEquals($c->getVersion(), Configuration::VERSION);
    }


    /**
     * Test that the default instance fails to load even if we previously loaded another instance.
     */
    public function testLoadDefaultInstance(): void
    {
        $this->expectException(Error\CriticalConfigurationError::class);
        Configuration::loadFromArray(['key' => 'value'], '', 'dummy');
        Configuration::getInstance();
    }


    /**
     * Test that after a \SimpleSAML\Error\CriticalConfigurationError exception, a basic, self-survival configuration
     * is loaded.
     */
    public function testCriticalConfigurationError(): void
    {
        try {
            Configuration::getInstance();
            $this->fail('Exception expected');
        } catch (Error\CriticalConfigurationError $var) {
            // This exception is expected.
        }
        /*
         * After the above failure an emergency configuration is create to allow core SSP components to function and
         * possibly log/display the error.
         */
        $c = Configuration::getInstance();
        $this->assertNotEmpty($c->toArray());
    }


    /**
     * Test \SimpleSAML\Configuration::getValue()
     */
    public function testGetValue(): void
    {
        $c = Configuration::loadFromArray([
            'exists_true' => true,
            'exists_null' => null,
        ]);

        // Normal use
        $this->assertTrue($c->getValue('exists_true'));

        // Null option
        $this->expectException(AssertionFailedException::class);
        $c->getValue('exists_null');

        // Missing option
        $this->expectException(AssertionFailedException::class);
        $c->getValue('missing');
    }


    /**
     * Test \SimpleSAML\Configuration::getOptionalValue().
     */
    public function testGetOptionalValue(): void
    {
        $c = Configuration::loadFromArray([
            'exists_true' => true,
        ]);

        // Normal use
        $this->assertTrue($c->getOptionalValue('exists_true', 'something else'));

        // Missing option
        $this->assertNull($c->getOptionalValue('missing', null));
    }


    /**
     * Test \SimpleSAML\Configuration::hasValue()
     */
    public function testHasValue(): void
    {
        $c = Configuration::loadFromArray([
            'exists_true' => true,
            'exists_null' => null,
        ]);
        $this->assertEquals($c->hasValue('missing'), false);
        $this->assertEquals($c->hasValue('exists_true'), true);
        $this->assertEquals($c->hasValue('exists_null'), false);
    }


    /**
     * Test \SimpleSAML\Configuration::hasValue()
     */
    public function testHasValueOneOf(): void
    {
        $c = Configuration::loadFromArray([
            'exists_true' => true,
            'exists_null' => null,
        ]);
        $this->assertEquals($c->hasValueOneOf([]), false);
        $this->assertEquals($c->hasValueOneOf(['missing']), false);
        $this->assertEquals($c->hasValueOneOf(['exists_true']), true);
        $this->assertEquals($c->hasValueOneOf(['exists_null']), false);

        $this->assertEquals($c->hasValueOneOf(['missing1', 'missing2']), false);
        $this->assertEquals($c->hasValueOneOf(['exists_true', 'missing']), true);
        $this->assertEquals($c->hasValueOneOf(['missing', 'exists_true']), true);
    }


    /**
     * Test \SimpleSAML\Configuration::getBasePath()
     */
    public function testGetBasePath(): void
    {
        $c = Configuration::loadFromArray([]);
        $this->assertEquals($c->getBasePath(), '/simplesaml/');

        $c = Configuration::loadFromArray(['baseurlpath' => 'simplesaml/']);
        $this->assertEquals($c->getBasePath(), '/simplesaml/');

        $c = Configuration::loadFromArray(['baseurlpath' => '/simplesaml/']);
        $this->assertEquals($c->getBasePath(), '/simplesaml/');

        $c = Configuration::loadFromArray(['baseurlpath' => 'simplesaml']);
        $this->assertEquals($c->getBasePath(), '/simplesaml/');

        $c = Configuration::loadFromArray(['baseurlpath' => '/simplesaml']);
        $this->assertEquals($c->getBasePath(), '/simplesaml/');

        $c = Configuration::loadFromArray(['baseurlpath' => 'path/to/simplesaml/']);
        $this->assertEquals($c->getBasePath(), '/path/to/simplesaml/');

        $c = Configuration::loadFromArray(['baseurlpath' => '/path/to/simplesaml/']);
        $this->assertEquals($c->getBasePath(), '/path/to/simplesaml/');

        $c = Configuration::loadFromArray(['baseurlpath' => '/path/to/simplesaml']);
        $this->assertEquals($c->getBasePath(), '/path/to/simplesaml/');

        $c = Configuration::loadFromArray(['baseurlpath' => 'https://example.org/ssp/']);
        $this->assertEquals($c->getBasePath(), '/ssp/');

        $c = Configuration::loadFromArray(['baseurlpath' => 'https://example.org/']);
        $this->assertEquals($c->getBasePath(), '/');

        $c = Configuration::loadFromArray(['baseurlpath' => 'http://example.org/ssp/']);
        $this->assertEquals($c->getBasePath(), '/ssp/');

        $c = Configuration::loadFromArray(['baseurlpath' => 'http://example.org/ssp/simplesaml']);
        $this->assertEquals($c->getBasePath(), '/ssp/simplesaml/');

        $c = Configuration::loadFromArray(['baseurlpath' => 'http://example.org/ssp/simplesaml/']);
        $this->assertEquals($c->getBasePath(), '/ssp/simplesaml/');

        $c = Configuration::loadFromArray(['baseurlpath' => '']);
        $this->assertEquals($c->getBasePath(), '/');

        $c = Configuration::loadFromArray(['baseurlpath' => '/']);
        $this->assertEquals($c->getBasePath(), '/');

        $c = Configuration::loadFromArray(['baseurlpath' => 'https://example.org:8443']);
        $this->assertEquals($c->getBasePath(), '/');

        $c = Configuration::loadFromArray(['baseurlpath' => 'https://example.org:8443/']);
        $this->assertEquals($c->getBasePath(), '/');
    }


    /**
     * Test \SimpleSAML\Configuration::resolvePath()
     */
    public function testResolvePath(): void
    {
        $c = Configuration::loadFromArray([
            'basedir' => '/basedir/',
        ]);

        $this->assertEquals($c->resolvePath(null), null);
        $this->assertEquals($c->resolvePath('/otherdir'), '/otherdir');
        $this->assertEquals($c->resolvePath('relativedir'), '/basedir/relativedir');

        $this->assertEquals($c->resolvePath('slash/'), '/basedir/slash');
        $this->assertEquals($c->resolvePath('slash//'), '/basedir/slash');

        $this->assertEquals($c->resolvePath('C:\\otherdir'), 'C:/otherdir');
        $this->assertEquals($c->resolvePath('C:/otherdir'), 'C:/otherdir');
    }


    /**
     * Test \SimpleSAML\Configuration::getPathValue()
     */
    public function testGetPathValue(): void
    {
        $c = Configuration::loadFromArray([
            'basedir' => '/basedir/',
            'path_opt' => 'path',
            'slashes_opt' => 'slashes//',
        ]);

        $this->assertEquals($c->getPathValue('missing'), null);
        $this->assertEquals($c->getPathValue('path_opt'), '/basedir/path/');
        $this->assertEquals($c->getPathValue('slashes_opt'), '/basedir/slashes/');
    }


    /**
     * Test \SimpleSAML\Configuration::getBaseDir()
     */
    public function testGetBaseDir(): void
    {
        $c = Configuration::loadFromArray([]);
        $this->assertEquals($c->getBaseDir(), dirname(__FILE__, 4) . DIRECTORY_SEPARATOR);

        $c = Configuration::loadFromArray([
            'basedir' => DIRECTORY_SEPARATOR . 'basedir',
        ]);
        $this->assertEquals($c->getBaseDir(), DIRECTORY_SEPARATOR . 'basedir' . DIRECTORY_SEPARATOR);

        $c = Configuration::loadFromArray([
            'basedir' => DIRECTORY_SEPARATOR . 'basedir' . DIRECTORY_SEPARATOR,
        ]);
        $this->assertEquals($c->getBaseDir(), DIRECTORY_SEPARATOR . 'basedir' . DIRECTORY_SEPARATOR);
    }


    /**
     * Test \SimpleSAML\Configuration::getBoolean()
     */
    public function testGetBoolean(): void
    {
        $c = Configuration::loadFromArray([
            'true_opt' => true,
            'false_opt' => false,
            'wrong_opt' => 'true',
        ]);

        // Normal use
        $this->assertTrue($c->getBoolean('true_opt'));
        $this->assertFalse($c->getBoolean('false_opt'));

        // Missing option
        $this->expectException(AssertionFailedException::class);
        $c->getBoolean('missing_opt');

        // Invalid option type
        $this->expectException(AssertionFailedException::class);
        $c->getBoolean('wrong_opt');
    }


    /**
     * Test \SimpleSAML\Configuration::getOptionalBoolean()
     */
    public function testGetOptionalBoolean(): void
    {
        $c = Configuration::loadFromArray([
            'true_opt' => true,
            'false_opt' => false,
            'wrong_opt' => 'true',
        ]);

        // Normal use
        $this->assertTrue($c->getOptionalBoolean('true_opt', true));
        $this->assertTrue($c->getOptionalBoolean('true_opt', false));
        $this->assertFalse($c->getOptionalBoolean('false_opt', false));
        $this->assertFalse($c->getOptionalBoolean('false_opt', true));

        // Missing option
        $this->assertEquals($c->getOptionalBoolean('missing_opt', null), null);
        $this->assertEquals($c->getOptionalBoolean('missing_opt', false), false);
        $this->assertEquals($c->getOptionalBoolean('missing_opt', true), true);

        // Invalid option type
        $this->expectException(AssertionFailedException::class);
        $c->getOptionalBoolean('wrong_opt', null);
    }


    /**
     * Test \SimpleSAML\Configuration::getString()
     */
    public function testGetString(): void
    {
        $c = Configuration::loadFromArray([
            'str_opt' => 'Hello World!',
            'wrong_opt' => true,
        ]);

        // Normal use
        $this->assertEquals($c->getString('str_opt'), 'Hello World!');

        // Missing option
        $this->expectException(AssertionFailedException::class);
        $c->getString('missing_opt');

        // Invalid option type
        $this->expectException(AssertionFailedException::class);
        $c->getString('wrong_opt');
    }


    /**
     * Test \SimpleSAML\Configuration::getOptionalString() missing option
     */
    public function testGetOptionalString(): void
    {
        $c = Configuration::loadFromArray([
            'str_opt' => 'Hello World!',
            'wrong_opt' => true,
        ]);

        // Normal use
        $this->assertEquals($c->getOptionalString('str_opt', 'Hello World!'), 'Hello World!');
        $this->assertEquals($c->getOptionalString('str_opt', 'something else'), 'Hello World!');

        // Missing option
        $this->assertEquals($c->getOptionalString('missing_opt', 'Hello World!'), 'Hello World!');

        // Invalid option type
        $this->expectException(AssertionFailedException::class);
        $c->getOptionalString('wrong_opt', 'Hello World!');
    }


    /**
     * Test \SimpleSAML\Configuration::getInteger()
     */
    public function testGetInteger(): void
    {
        $c = Configuration::loadFromArray([
            'int_opt' => 42,
            'wrong_opt' => 'test',
        ]);

        // Normal use
        $this->assertEquals($c->getInteger('int_opt'), 42);

        // Missing option
        $this->expectException(AssertionFailedException::class);
        $c->getInteger('missing_opt');

        // Invalid option type
        $this->expectException(AssertionFailedException::class);
        $c->getInteger('wrong_opt');
    }


    /**
     * Test \SimpleSAML\Configuration::getOptionalInteger()
     */
    public function testGetOptionalInteger(): void
    {
        $c = Configuration::loadFromArray([
            'int_opt' => 42,
            'wrong_opt' => 'test',
        ]);


        // Normal use
        $this->assertEquals($c->getOptionalInteger('int_opt', 42), 42);

        // Missing option
        $this->assertEquals($c->getOptionalInteger('missing_opt', 32), 32);

        // Invalid option type
        $this->expectException(AssertionFailedException::class);
        $c->getOptionalInteger('wrong_opt', 10);
    }


    /**
     * Test \SimpleSAML\Configuration::getIntegerRange()
     */
    public function testGetIntegerRange(): void
    {
        $c = Configuration::loadFromArray([
            'min_opt' => 0,
            'max_opt' => 100,
            'wrong_opt' => 'test',
        ]);

        // Normal use
        $this->assertEquals($c->getIntegerRange('min_opt', 0, 100), 0);
        $this->assertEquals($c->getIntegerRange('max_opt', 0, 100), 100);

        // Missing option
        $this->expectException(AssertionFailedException::class);
        $c->getIntegerRange('missing_opt', 0, 100);

        // Invalid option type
        $this->expectException(AssertionFailedException::class);
        $c->getIntegerRange('wrong_opt', 0, 100);

        // Below range
        $this->expectException(AssertionFailedException::class);
        $c->getIntegerRange('min_opt', 1, 100);

        // Above range
        $this->expectException(AssertionFailedException::class);
        $c->getIntegerRange('max_opt', 0, 99);
    }


    /**
     * Test \SimpleSAML\Configuration::getOptionalIntegerRange()
     */
    public function testGetOptionalIntegerRange(): void
    {
        $c = Configuration::loadFromArray([
            'min_opt' => 0,
            'max_opt' => 100,
            'wrong_opt' => 'test',
        ]);


        // Normal use
        $this->assertEquals($c->getOptionalIntegerRange('min_opt', 0, 100, 50), 0);
        $this->assertEquals($c->getOptionalIntegerRange('max_opt', 0, 100, 50), 100);

        // Missing option
        $this->assertEquals($c->getOptionalIntegerRange('missing_opt', 0, 100, 50), 50);

        // Invalid option type
        $this->expectException(AssertionFailedException::class);
        $c->getOptionalIntegerRange('wrong_opt', 0, 100, null);

        // Below range
        $this->expectException(AssertionFailedException::class);
        $c->getOptionalIntegerRange('min_opt', 1, 100, null);

        // Above range
        $this->expectException(AssertionFailedException::class);
        $c->getOptionalIntegerRange('max_opt', 0, 99, null);
    }


    /**
     * Test \SimpleSAML\Configuration::getValueValidate()
     */
    public function testGetValueValidate(): void
    {
        $c = Configuration::loadFromArray([
            'opt' => 'b',
        ]);

        // Normal use
        $this->assertEquals($c->getValueValidate('opt', ['a', 'b', 'c']), 'b');

        // Value not allowed
        $this->expectException(AssertionFailedException::class);
        $c->getValueValidate('opt', ['d', 'e', 'f']);

        // Missing option
        $this->expectException(AssertionFailedException::class);
        $c->getValueValidate('missing_opt', ['a', 'b', 'c']);
    }


    /**
     * Test \SimpleSAML\Configuration::getOptionalValueValidate()
     */
    public function testGetOptionalValueValidate(): void
    {
        $c = Configuration::loadFromArray([
            'opt' => 'b',
        ]);

        // Normal use
        $this->assertEquals($c->getOptionalValueValidate('opt', ['a', 'b', 'c'], 'f'), 'b');

        // Missing option
        $this->assertEquals($c->getOptionalValueValidate('missing_opt', ['a', 'b', 'c'], 'b'), 'b');

        // Value not allowed
        $this->expectException(AssertionFailedException::class);
        $c->getOptionalValueValidate('opt', ['d', 'e', 'f'], 'c');
        $c->getOptionalValueValidate('missing_opt', ['d', 'e', 'f'], 'c');
    }


    /**
     * Test \SimpleSAML\Configuration::getArray()
     */
    public function testGetArray(): void
    {
        $c = Configuration::loadFromArray([
            'opt' => ['a', 'b', 'c'],
            'wrong_opt' => false,
        ]);

        // Normal use
        $this->assertEquals($c->getArray('opt'), ['a', 'b', 'c']);

        // Missing option
        $this->expectException(AssertionFailedException::class);
        $c->getArray('missing_opt');

        // Value not allowed
        $this->expectException(AssertionFailedException::class);
        $c->getArray('wrong_opt');
    }


    /**
     * Test \SimpleSAML\Configuration::getOptionalArray()
     */
    public function testGetOptionalArray(): void
    {
        $c = Configuration::loadFromArray([
            'opt' => ['a', 'b', 'c'],
            'wrong_opt' => false,
        ]);

        // Normal use
        $this->assertEquals($c->getOptionalArray('opt', ['d', 'e', 'f']), ['a', 'b', 'c']);

        // Missing option
        $this->assertEquals($c->getOptionalArray('missing_opt', ['d', 'e', 'f']), ['d', 'e', 'f']);

        // Value not allowed
        $this->expectException(AssertionFailedException::class);
        $c->getArray('wrong_opt');
    }


    /**
     * Test \SimpleSAML\Configuration::getArrayize()
     */
    public function testGetArrayize(): void
    {
        $c = Configuration::loadFromArray([
            'opt' => ['a', 'b', 'c'],
            'opt_int' => 42,
            'opt_str' => 'string',
        ]);

        // Normal use
        $this->assertEquals($c->getArrayize('opt'), ['a', 'b', 'c']);
        $this->assertEquals($c->getArrayize('opt_int'), [42]);
        $this->assertEquals($c->getArrayize('opt_str'), ['string']);

        // Missing option
        $this->expectException(AssertionFailedException::class);
        $c->getArrayize('missing_opt');
    }


    /**
     * Test \SimpleSAML\Configuration::getOptionalArrayize()
     */
    public function testGetOptionalArrayize(): void
    {
        $c = Configuration::loadFromArray([
            'opt' => ['a', 'b', 'c'],
            'opt_int' => 42,
            'opt_str' => 'string',
        ]);

        // Normal use
        $this->assertEquals($c->getOptionalArrayize('opt', ['d']), ['a', 'b', 'c']);
        $this->assertEquals($c->getOptionalArrayize('opt_int', [1]), [42]);
        $this->assertEquals($c->getOptionalArrayize('opt_str', ['test']), ['string']);

        // Missing option
        $this->assertEquals($c->getOptionalArrayize('missing_opt', ['test']), ['test']);
    }


    /**
     * Test \SimpleSAML\Configuration::getArrayizeString()
     */
    public function testGetArrayizeString(): void
    {
        $c = Configuration::loadFromArray([
            'opt' => ['a', 'b', 'c'],
            'opt_str' => 'string',
            'opt_wrong' => 4,
        ]);

        // Normal use
        $this->assertEquals($c->getArrayizeString('opt'), ['a', 'b', 'c']);
        $this->assertEquals($c->getArrayizeString('opt_str'), ['string']);

        // Missing option
        $this->expectException(AssertionFailedException::class);
        $c->getArrayizeString('missing_opt');

        // Wrong option
        $this->expectException(AssertionFailedException::class);
        $c->getArrayizeString('opt_wrong');
    }


    /**
     * Test \SimpleSAML\Configuration::getOptionalArrayizeString()
     */
    public function testGetOptionalArrayizeString(): void
    {
        $c = Configuration::loadFromArray([
            'opt' => ['a', 'b', 'c'],
            'opt_str' => 'string',
            'opt_wrong' => 4,
        ]);

        // Normal use
        $this->assertEquals($c->getOptionalArrayizeString('opt', ['d']), ['a', 'b', 'c']);
        $this->assertEquals($c->getOptionalArrayizeString('opt_str', ['test']), ['string']);

        // Missing option
        $this->assertEquals($c->getOptionalArrayizeString('missing_opt', ['test']), ['test']);

        // Wrong option
        $this->expectException(AssertionFailedException::class);
        $c->getOptionalArrayizeString('opt_wrong', ['test']);
    }


    /**
     * Test \SimpleSAML\Configuration::getConfigItem()
     */
    public function testGetConfigItem(): void
    {
        $c = Configuration::loadFromArray([
            'opt' => ['a' => 42],
        ]);

        $opt = $c->getConfigItem('opt');
        $this->assertInstanceOf(Configuration::class, $opt);

        // Missing option
        $this->expectException(AssertionFailedException::class);
        $c->getConfigItem('missing_opt');
    }


    /**
     * Test \SimpleSAML\Configuration::getOptionalConfigItem()
     */
    public function testGetOptionalConfigItem(): void
    {
        $c = Configuration::loadFromArray([
            'opt' => ['a' => 42],
        ]);

        $opt = $c->getOptionalConfigItem('opt', null);
        $this->assertInstanceOf(Configuration::class, $opt);

        // Missing option
        $this->assertNull($c->getOptionalConfigItem('missing_opt', null));
    }


    /**
     * Test \SimpleSAML\Configuration::getOptions()
     */
    public function testGetOptions(): void
    {
        $c = Configuration::loadFromArray([
            'a' => true,
            'b' => null,
        ]);
        $this->assertEquals($c->getOptions(), ['a', 'b']);
    }


    /**
     * Test \SimpleSAML\Configuration::toArray()
     */
    public function testToArray(): void
    {
        $c = Configuration::loadFromArray([
            'a' => true,
            'b' => null,
        ]);
        $this->assertEquals($c->toArray(), ['a' => true, 'b' => null]);
    }


    /**
     * Test \SimpleSAML\Configuration::getDefaultEndpoint().
     *
     * Iterate over all different valid definitions of endpoints and check if the expected output is produced.
     */
    public function testGetDefaultEndpoint(): void
    {
        /*
         * First we run the full set of tests covering all possible configurations for indexed endpoint types,
         * basically AssertionConsumerService and ArtifactResolutionService. Since both are the same, we just run the
         * tests for AssertionConsumerService.
         */
        $acs_eps = [
            // just a string with the location
            'https://example.com/endpoint.php',
            // an array of strings with location of different endpoints
            [
                'https://www1.example.com/endpoint.php',
                'https://www2.example.com/endpoint.php',
            ],
            // define location and binding
            [
                [
                    'Location' => 'https://example.com/endpoint.php',
                    'Binding' => Constants::BINDING_HTTP_POST,
                ],
            ],
            // define the ResponseLocation too
            [
                [
                    'Location' => 'https://example.com/endpoint.php',
                    'Binding' => Constants::BINDING_HTTP_POST,
                    'ResponseLocation' => 'https://example.com/endpoint.php',
                ],
            ],
            // make sure indexes are NOT taken into account (they just identify endpoints)
            [
                [
                    'index' => 1,
                    'Location' => 'https://www1.example.com/endpoint.php',
                    'Binding' => Constants::BINDING_HTTP_REDIRECT,
                ],
                [
                    'index' => 2,
                    'Location' => 'https://www2.example.com/endpoint.php',
                    'Binding' => Constants::BINDING_HTTP_POST,
                ],
            ],
            // make sure isDefault has priority over indexes
            [
                [
                    'index' => 1,
                    'Location' => 'https://www2.example.com/endpoint.php',
                    'Binding' => Constants::BINDING_HTTP_POST,
                ],
                [
                    'index' => 2,
                    'isDefault' => true,
                    'Location' => 'https://www1.example.com/endpoint.php',
                    'Binding' => Constants::BINDING_HTTP_REDIRECT,
                ],
            ],
            // make sure endpoints with invalid bindings are ignored and those marked as NOT default are still used
            [
                [
                    'index' => 1,
                    'Location' => 'https://www1.example.com/endpoint.php',
                    'Binding' => 'invalid_binding',
                ],
                [
                    'index' => 2,
                    'isDefault' => false,
                    'Location' => 'https://www2.example.com/endpoint.php',
                    'Binding' => Constants::BINDING_HTTP_POST,
                ],
            ],
        ];
        $acs_expected_eps = [
            // output should be completed with the default binding (HTTP-POST for ACS)
            [
                'Location' => 'https://example.com/endpoint.php',
                'Binding' => Constants::BINDING_HTTP_POST,
            ],
            // we should just get the first endpoint with the default binding
            [
                'Location' => 'https://www1.example.com/endpoint.php',
                'Binding' => Constants::BINDING_HTTP_POST,
            ],
            // if we specify the binding, we should get it back
            [
                'Location' => 'https://example.com/endpoint.php',
                'Binding' => Constants::BINDING_HTTP_POST
            ],
            // if we specify ResponseLocation, we should get it back too
            [
                'Location' => 'https://example.com/endpoint.php',
                'Binding' => Constants::BINDING_HTTP_POST,
                'ResponseLocation' => 'https://example.com/endpoint.php',
            ],
            // indexes must NOT be taken into account, order is the only thing that matters here
            [
                'Location' => 'https://www1.example.com/endpoint.php',
                'Binding' => Constants::BINDING_HTTP_REDIRECT,
                'index' => 1,
            ],
            // isDefault must have higher priority than indexes
            [
                'Location' => 'https://www1.example.com/endpoint.php',
                'Binding' => Constants::BINDING_HTTP_REDIRECT,
                'isDefault' => true,
                'index' => 2,
            ],
            // the first valid endpoint should be used even if it's marked as NOT default
            [
                'index' => 2,
                'isDefault' => false,
                'Location' => 'https://www2.example.com/endpoint.php',
                'Binding' => Constants::BINDING_HTTP_POST,
            ]
        ];

        $a = [
            'metadata-set' => 'saml20-sp-remote',
            'ArtifactResolutionService' => 'https://example.com/ars',
            'SingleSignOnService' => 'https://example.com/sso',
            'SingleLogoutService' => [
                'Location' => 'https://example.com/slo',
                'Binding' => 'valid_binding', // test unknown bindings if we don't specify a list of valid ones
            ],
        ];

        $valid_bindings = [
            Constants::BINDING_HTTP_POST,
            Constants::BINDING_HTTP_REDIRECT,
            Constants::BINDING_HOK_SSO,
            Constants::BINDING_HTTP_ARTIFACT,
            Constants::BINDING_SOAP,
        ];

        // run all general tests with AssertionConsumerService endpoint type
        foreach ($acs_eps as $i => $ep) {
            $a['AssertionConsumerService'] = $ep;
            $c = Configuration::loadFromArray($a);
            $this->assertEquals($acs_expected_eps[$i], $c->getDefaultEndpoint(
                'AssertionConsumerService',
                $valid_bindings
            ));
        }

        $a['metadata-set'] = 'saml20-idp-remote';
        $c = Configuration::loadFromArray($a);
        $this->assertEquals(
            [
                'Location' => 'https://example.com/ars',
                'Binding' => Constants::BINDING_SOAP,
            ],
            $c->getDefaultEndpoint('ArtifactResolutionService')
        );
        $this->assertEquals(
            [
                'Location' => 'https://example.com/slo',
                'Binding' => Constants::BINDING_HTTP_REDIRECT,
            ],
            $c->getDefaultEndpoint('SingleLogoutService')
        );

        // test for no valid endpoints specified
        $a['SingleLogoutService'] = [
            [
                'Location' => 'https://example.com/endpoint.php',
                'Binding' => 'invalid_binding',
                'isDefault' => true,
            ],
        ];
        $c = Configuration::loadFromArray($a);
        try {
            $c->getDefaultEndpoint('SingleLogoutService', $valid_bindings);
            $this->fail('Failed to detect invalid endpoint binding.');
        } catch (Exception $e) {
            $this->assertEquals(
                '[ARRAY][\'SingleLogoutService\']:Could not find a supported SingleLogoutService ' . 'endpoint.',
                $e->getMessage()
            );
        }
        $a['metadata-set'] = 'foo';
        $c = Configuration::loadFromArray($a);
        try {
            $c->getDefaultEndpoint('SingleSignOnService');
            $this->fail('No valid metadata set specified.');
        } catch (Exception $e) {
            $this->assertStringStartsWith('Missing default binding for', $e->getMessage());
        }
    }


    /**
     * Test \SimpleSAML\Configuration::getEndpoints().
     */
    public function testGetEndpoints(): void
    {
        // test response location for old-style configurations
        $c = Configuration::loadFromArray([
            'metadata-set' => 'saml20-idp-remote',
            'SingleSignOnService' => 'https://example.com/endpoint.php',
            'SingleSignOnServiceResponse' => 'https://example.com/response.php',
        ]);
        $e = [
            [
                'Location' => 'https://example.com/endpoint.php',
                'Binding' => Constants::BINDING_HTTP_REDIRECT,
                'ResponseLocation' => 'https://example.com/response.php',
            ]
        ];
        $this->assertEquals($e, $c->getEndpoints('SingleSignOnService'));

        // test for input failures

        // define a basic configuration array
        $a = [
            'metadata-set' => 'saml20-idp-remote',
            'SingleSignOnService' => null,
        ];

        // define a set of tests
        $tests = [
            // invalid endpoint definition
            10,
            // invalid definition of endpoint inside the endpoints array
            [
                1234
            ],
            // missing location
            [
                [
                    'foo' => 'bar',
                ],
            ],
            // invalid location
            [
                [
                    'Location' => 1234,
                ]
            ],
            // missing binding
            [
                [
                    'Location' => 'https://example.com/endpoint.php',
                ],
            ],
            // invalid binding
            [
                [
                    'Location' => 'https://example.com/endpoint.php',
                    'Binding' => 1234,
                ],
            ],
            // invalid response location
            [
                [
                    'Location' => 'https://example.com/endpoint.php',
                    'Binding' => Constants::BINDING_HTTP_REDIRECT,
                    'ResponseLocation' => 1234,
                ],
            ],
            // invalid index
            [
                [
                    'Location' => 'https://example.com/endpoint.php',
                    'Binding' => Constants::BINDING_HTTP_REDIRECT,
                    'index' => 'string',
                ],
            ],
        ];

        // define a set of exception messages to expect
        $msgs = [
            'Expected array or string.',
            'Expected a string or an array.',
            'Missing Location.',
            'Location must be a string.',
            'Missing Binding.',
            'Binding must be a string.',
            'ResponseLocation must be a string.',
            'index must be an integer.',
        ];

        // now run all the tests expecting the correct exception message
        foreach ($tests as $i => $test) {
            $a['SingleSignOnService'] = $test;
            $c = Configuration::loadFromArray($a);
            try {
                $c->getEndpoints('SingleSignOnService');
            } catch (Exception $e) {
                $this->assertStringEndsWith($msgs[$i], $e->getMessage());
            }
        }
    }


    /**
     * Test \SimpleSAML\Configuration::getLocalizedString()
     */
    public function testGetLocalizedString(): void
    {
        $c = Configuration::loadFromArray([
            'str_opt' => 'Hello World!',
            'str_array' => [
                'en' => 'Hello World!',
                'no' => 'Hei Verden!',
            ],
        ]);
        $this->assertEquals($c->getLocalizedString('str_opt'), ['en' => 'Hello World!']);
        $this->assertEquals($c->getLocalizedString('str_array'), ['en' => 'Hello World!', 'no' => 'Hei Verden!']);

        $this->expectException(AssertionFailedException::class);
        $c->getLocalizedString('missing_opt');
    }


    /**
     * Test \SimpleSAML\Configuration::getLocalizedString() not array nor simple string
     */
    public function testGetLocalizedStringNotArray(): void
    {
        $this->expectException(Exception::class);
        $c = Configuration::loadFromArray([
            'opt' => 42,
        ]);
        $c->getLocalizedString('opt');
    }


    /**
     * Test \SimpleSAML\Configuration::getLocalizedString() not string key
     */
    public function testGetLocalizedStringNotStringKey(): void
    {
        $this->expectException(Exception::class);
        $c = Configuration::loadFromArray([
            'opt' => [42 => 'text'],
        ]);
        $c->getLocalizedString('opt');
    }


    /**
     * Test \SimpleSAML\Configuration::getLocalizedString() not string value
     */
    public function testGetLocalizedStringNotStringValue(): void
    {
        $this->expectException(Exception::class);
        $c = Configuration::loadFromArray([
            'opt' => ['en' => 42],
        ]);
        $c->getLocalizedString('opt');
    }


    /**
     * Test \SimpleSAML\Configuration::getConfig() nonexistent file
     */
    public function testGetConfigNonexistentFile(): void
    {
        $this->expectException(Exception::class);
        Configuration::getConfig('nonexistent-nopreload.php');
    }


    /**
     * Test \SimpleSAML\Configuration::getConfig() preloaded nonexistent file
     */
    public function testGetConfigNonexistentFilePreload(): void
    {
        $c = Configuration::loadFromArray([
            'key' => 'value'
        ]);
        $virtualFile = 'nonexistent-preload.php';
        Configuration::setPreLoadedConfig($c, $virtualFile);
        $nc = Configuration::getConfig($virtualFile);
        $this->assertEquals('value', $nc->getOptionalValue('key', null));
    }


    /**
     * Test that Configuration objects can be initialized from an array.
     *
     * ATTENTION: this test must be kept the last.
     */
    public function testLoadInstanceFromArray(): void
    {
        $c = [
            'key' => 'value'
        ];
        // test loading a custom instance
        Configuration::loadFromArray($c, '', 'dummy');
        $this->assertEquals('value', Configuration::getInstance('dummy')->getOptionalValue('key', null));

        // test loading the default instance
        Configuration::loadFromArray($c, '', 'simplesaml');
        $this->assertEquals('value', Configuration::getInstance()->getOptionalValue('key', null));
    }
}
