<?php

/**
 * Tests for SimpleSAML_Configuration
 */
class Test_SimpleSAML_Configuration extends SimpleSAML\Test\Utils\ClearStateTestCase
{

    /**
     * Test SimpleSAML_Configuration::getVersion()
     */
    public function testGetVersion() {
        $c = SimpleSAML_Configuration::getOptionalConfig();
        $this->assertTrue(is_string($c->getVersion()));
    }

    /**
     * Test that the default instance fails to load even if we previously loaded another instance.
     * @expectedException \SimpleSAML\Error\CriticalConfigurationError
     */
    public function testLoadDefaultInstance()
    {
        SimpleSAML_Configuration::loadFromArray(array('key' => 'value'), '', 'dummy');
        SimpleSAML_Configuration::getInstance();
    }


    /**
     * Test that after a \SimpleSAML\Error\CriticalConfigurationError exception, a basic, self-survival configuration
     * is loaded.
     */
    public function testCriticalConfigurationError()
    {
        try {
            SimpleSAML_Configuration::getInstance();
            $this->fail('Exception expected');
        } catch (\SimpleSAML\Error\CriticalConfigurationError $var) {
            // This exception is expected.
        }
        /*
         * After the above failure an emergency configuration is create to allow core SSP components to function and
         * possibly log/display the error.
         */
        $c = SimpleSAML_Configuration::getInstance();
        $this->assertNotEmpty($c->toArray());
    }

    /**
     * Test SimpleSAML_Configuration::getValue()
     */
    public function testGetValue() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'exists_true' => true,
            'exists_null' => null,
        ));
        $this->assertEquals($c->getValue('missing'), null);
        $this->assertEquals($c->getValue('missing', true), true);
        $this->assertEquals($c->getValue('missing', true), true);

        $this->assertEquals($c->getValue('exists_true'), true);

        $this->assertEquals($c->getValue('exists_null'), null);
        $this->assertEquals($c->getValue('exists_null', false), null);
    }

    /**
     * Test SimpleSAML_Configuration::getValue(), REQUIRED_OPTION flag.
     * @expectedException Exception
     */
    public function testGetValueRequired() {
        $c = SimpleSAML_Configuration::loadFromArray(array());
        $c->getValue('missing', SimpleSAML_Configuration::REQUIRED_OPTION);
    }

    /**
     * Test SimpleSAML_Configuration::hasValue()
     */
    public function testHasValue() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'exists_true' => true,
            'exists_null' => null,
        ));
        $this->assertEquals($c->hasValue('missing'), false);
        $this->assertEquals($c->hasValue('exists_true'), true);
        $this->assertEquals($c->hasValue('exists_null'), true);
    }

    /**
     * Test SimpleSAML_Configuration::hasValue()
     */
    public function testHasValueOneOf() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'exists_true' => true,
            'exists_null' => null,
        ));
        $this->assertEquals($c->hasValueOneOf(array()), false);
        $this->assertEquals($c->hasValueOneOf(array('missing')), false);
        $this->assertEquals($c->hasValueOneOf(array('exists_true')), true);
        $this->assertEquals($c->hasValueOneOf(array('exists_null')), true);

        $this->assertEquals($c->hasValueOneOf(array('missing1', 'missing2')), false);
        $this->assertEquals($c->hasValueOneOf(array('exists_true', 'missing')), true);
        $this->assertEquals($c->hasValueOneOf(array('missing', 'exists_true')), true);
    }

    /**
     * Test SimpleSAML_Configuration::getBaseURL()
     */
    public function testGetBaseURL() {

        // Need to set a default configuration because the SSP Logger attempts to use it.
        SimpleSAML_Configuration::loadFromArray(array(), '[ARRAY]', 'simplesaml');
        $c = SimpleSAML_Configuration::loadFromArray(array());
        $this->assertEquals($c->getBaseURL(), 'simplesaml/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => 'simplesaml/'));
        $this->assertEquals($c->getBaseURL(), 'simplesaml/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => '/simplesaml/'));
        $this->assertEquals($c->getBaseURL(), 'simplesaml/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => 'path/to/simplesaml/'));
        $this->assertEquals($c->getBaseURL(), 'path/to/simplesaml/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => '/path/to/simplesaml/'));
        $this->assertEquals($c->getBaseURL(), 'path/to/simplesaml/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => 'https://example.org/ssp/'));
        $this->assertEquals($c->getBaseURL(), 'ssp/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => 'https://example.org/'));
        $this->assertEquals($c->getBaseURL(), '');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => 'http://example.org/ssp/'));
        $this->assertEquals($c->getBaseURL(), 'ssp/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => ''));
        $this->assertEquals($c->getBaseURL(), '');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => '/'));
        $this->assertEquals($c->getBaseURL(), '');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => 'simplesaml'));
        $this->assertEquals($c->getBaseURL(), 'simplesaml/');
    }

    /**
     * Test SimpleSAML_Configuration::getBasePath()
     */
    public function testGetBasePath() {
        $c = SimpleSAML_Configuration::loadFromArray(array());
        $this->assertEquals($c->getBasePath(), '/simplesaml/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => 'simplesaml/'));
        $this->assertEquals($c->getBasePath(), '/simplesaml/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => '/simplesaml/'));
        $this->assertEquals($c->getBasePath(), '/simplesaml/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => 'simplesaml'));
        $this->assertEquals($c->getBasePath(), '/simplesaml/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => '/simplesaml'));
        $this->assertEquals($c->getBasePath(), '/simplesaml/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => 'path/to/simplesaml/'));
        $this->assertEquals($c->getBasePath(), '/path/to/simplesaml/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => '/path/to/simplesaml/'));
        $this->assertEquals($c->getBasePath(), '/path/to/simplesaml/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => '/path/to/simplesaml'));
        $this->assertEquals($c->getBasePath(), '/path/to/simplesaml/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => 'https://example.org/ssp/'));
        $this->assertEquals($c->getBasePath(), '/ssp/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => 'https://example.org/'));
        $this->assertEquals($c->getBasePath(), '/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => 'http://example.org/ssp/'));
        $this->assertEquals($c->getBasePath(), '/ssp/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => 'http://example.org/ssp/simplesaml'));
        $this->assertEquals($c->getBasePath(), '/ssp/simplesaml/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => 'http://example.org/ssp/simplesaml/'));
        $this->assertEquals($c->getBasePath(), '/ssp/simplesaml/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => ''));
        $this->assertEquals($c->getBasePath(), '/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => '/'));
        $this->assertEquals($c->getBasePath(), '/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => 'https://example.org:8443'));
        $this->assertEquals($c->getBasePath(), '/');

        $c = SimpleSAML_Configuration::loadFromArray(array('baseurlpath' => 'https://example.org:8443/'));
        $this->assertEquals($c->getBasePath(), '/');
    }

    /**
     * Test SimpleSAML_Configuration::resolvePath()
     */
    public function testResolvePath() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'basedir' => '/basedir/',
        ));

        $this->assertEquals($c->resolvePath(null), null);
        $this->assertEquals($c->resolvePath('/otherdir'), '/otherdir');
        $this->assertEquals($c->resolvePath('relativedir'), '/basedir/relativedir');

        $this->assertEquals($c->resolvePath('slash/'), '/basedir/slash');
        $this->assertEquals($c->resolvePath('slash//'), '/basedir/slash');
    }

    /**
     * Test SimpleSAML_Configuration::getPathValue()
     */
    public function testGetPathValue() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'basedir' => '/basedir/',
            'path_opt' => 'path',
            'slashes_opt' => 'slashes//',
        ));

        $this->assertEquals($c->getPathValue('missing'), null);
        $this->assertEquals($c->getPathValue('path_opt'), '/basedir/path/');
        $this->assertEquals($c->getPathValue('slashes_opt'), '/basedir/slashes/');
    }

    /**
     * Test SimpleSAML_Configuration::getBaseDir()
     */
    public function testGetBaseDir() {
        $c = SimpleSAML_Configuration::loadFromArray(array());
        $this->assertEquals($c->getBaseDir(), dirname(dirname(dirname(dirname(__FILE__)))) . '/');

        $c = SimpleSAML_Configuration::loadFromArray(array(
            'basedir' => '/basedir',
        ));
        $this->assertEquals($c->getBaseDir(), '/basedir/');

        $c = SimpleSAML_Configuration::loadFromArray(array(
            'basedir' => '/basedir/',
        ));
        $this->assertEquals($c->getBaseDir(), '/basedir/');
    }

    /**
     * Test SimpleSAML_Configuration::getBoolean()
     */
    public function testGetBoolean() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'true_opt' => true,
            'false_opt' => false,
        ));
        $this->assertEquals($c->getBoolean('missing_opt', '--missing--'), '--missing--');
        $this->assertEquals($c->getBoolean('true_opt', '--missing--'), true);
        $this->assertEquals($c->getBoolean('false_opt', '--missing--'), false);
    }

    /**
     * Test SimpleSAML_Configuration::getBoolean() missing option
     * @expectedException Exception
     */
    public function testGetBooleanMissing() {
        $c = SimpleSAML_Configuration::loadFromArray(array());
        $c->getBoolean('missing_opt');
    }

    /**
     * Test SimpleSAML_Configuration::getBoolean() wrong option
     * @expectedException Exception
     */
    public function testGetBooleanWrong() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'wrong' => 'true',
        ));
        $c->getBoolean('wrong');
    }

    /**
     * Test SimpleSAML_Configuration::getString()
     */
    public function testGetString() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'str_opt' => 'Hello World!',
        ));
        $this->assertEquals($c->getString('missing_opt', '--missing--'), '--missing--');
        $this->assertEquals($c->getString('str_opt', '--missing--'), 'Hello World!');
    }

    /**
     * Test SimpleSAML_Configuration::getString() missing option
     * @expectedException Exception
     */
    public function testGetStringMissing() {
        $c = SimpleSAML_Configuration::loadFromArray(array());
        $c->getString('missing_opt');
    }

    /**
     * Test SimpleSAML_Configuration::getString() wrong option
     * @expectedException Exception
     */
    public function testGetStringWrong() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'wrong' => false,
        ));
        $c->getString('wrong');
    }

    /**
     * Test SimpleSAML_Configuration::getInteger()
     */
    public function testGetInteger() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'int_opt' => 42,
        ));
        $this->assertEquals($c->getInteger('missing_opt', '--missing--'), '--missing--');
        $this->assertEquals($c->getInteger('int_opt', '--missing--'), 42);
    }

    /**
     * Test SimpleSAML_Configuration::getInteger() missing option
     * @expectedException Exception
     */
    public function testGetIntegerMissing() {
        $c = SimpleSAML_Configuration::loadFromArray(array());
        $c->getInteger('missing_opt');
    }

    /**
     * Test SimpleSAML_Configuration::getInteger() wrong option
     * @expectedException Exception
     */
    public function testGetIntegerWrong() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'wrong' => '42',
        ));
        $c->getInteger('wrong');
    }

    /**
     * Test SimpleSAML_Configuration::getIntegerRange()
     */
    public function testGetIntegerRange() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'int_opt' => 42,
        ));
        $this->assertEquals($c->getIntegerRange('missing_opt', 0, 100, '--missing--'), '--missing--');
        $this->assertEquals($c->getIntegerRange('int_opt', 0, 100), 42);
    }

    /**
     * Test SimpleSAML_Configuration::getIntegerRange() below limit
     * @expectedException Exception
     */
    public function testGetIntegerRangeBelow() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'int_opt' => 9,
        ));
        $this->assertEquals($c->getIntegerRange('int_opt', 10, 100), 42);
    }

    /**
     * Test SimpleSAML_Configuration::getIntegerRange() above limit
     * @expectedException Exception
     */
    public function testGetIntegerRangeAbove() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'int_opt' => 101,
        ));
        $this->assertEquals($c->getIntegerRange('int_opt', 10, 100), 42);
    }

    /**
     * Test SimpleSAML_Configuration::getValueValidate()
     */
    public function testGetValueValidate() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'opt' => 'b',
        ));
        $this->assertEquals($c->getValueValidate('missing_opt', array('a', 'b', 'c'), '--missing--'), '--missing--');
        $this->assertEquals($c->getValueValidate('opt', array('a', 'b', 'c')), 'b');
    }

    /**
     * Test SimpleSAML_Configuration::getValueValidate() wrong option
     * @expectedException Exception
     */
    public function testGetValueValidateWrong() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'opt' => 'd',
        ));
        $c->getValueValidate('opt', array('a', 'b', 'c'));
    }

    /**
     * Test SimpleSAML_Configuration::getArray()
     */
    public function testGetArray() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'opt' => array('a', 'b', 'c'),
        ));
        $this->assertEquals($c->getArray('missing_opt', '--missing--'), '--missing--');
        $this->assertEquals($c->getArray('opt'), array('a', 'b', 'c'));
    }

    /**
     * Test SimpleSAML_Configuration::getArray() wrong option
     * @expectedException Exception
     */
    public function testGetArrayWrong() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'opt' => 'not_an_array',
        ));
        $c->getArray('opt');
    }

    /**
     * Test SimpleSAML_Configuration::getArrayize()
     */
    public function testGetArrayize() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'opt' => array('a', 'b', 'c'),
            'opt_int' => 42,
            'opt_str' => 'string',
        ));
        $this->assertEquals($c->getArrayize('missing_opt', '--missing--'), '--missing--');
        $this->assertEquals($c->getArrayize('opt'), array('a', 'b', 'c'));
        $this->assertEquals($c->getArrayize('opt_int'), array(42));
        $this->assertEquals($c->getArrayize('opt_str'), array('string'));
    }

    /**
     * Test SimpleSAML_Configuration::getArrayizeString()
     */
    public function testGetArrayizeString() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'opt' => array('a', 'b', 'c'),
            'opt_str' => 'string',
        ));
        $this->assertEquals($c->getArrayizeString('missing_opt', '--missing--'), '--missing--');
        $this->assertEquals($c->getArrayizeString('opt'), array('a', 'b', 'c'));
        $this->assertEquals($c->getArrayizeString('opt_str'), array('string'));
    }

    /**
     * Test SimpleSAML_Configuration::getArrayizeString() option with an array that contains something that isn't a string.
     * @expectedException Exception
     */
    public function testGetArrayizeStringWrongValue() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'opt' => array('a', 'b', 42),
        ));
        $c->getArrayizeString('opt');
    }

    /**
     * Test SimpleSAML_Configuration::getConfigItem()
     */
    public function testGetConfigItem() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'opt' => array('a' => 42),
        ));
        $this->assertEquals($c->getConfigItem('missing_opt', '--missing--'), '--missing--');
        $opt = $c->getConfigItem('opt');
        $this->assertInstanceOf('SimpleSAML_Configuration', $opt);
        $this->assertEquals($opt->getValue('a'), 42);
    }

    /**
     * Test SimpleSAML_Configuration::getConfigItem() wrong option
     * @expectedException Exception
     */
    public function testGetConfigItemWrong() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'opt' => 'not_an_array',
        ));
        $c->getConfigItem('opt');
    }

    /**
     * Test SimpleSAML_Configuration::getConfigList()
     */
    public function testGetConfigList() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'opts' => array(
               'a' => array('opt1' => 'value1'),
               'b' => array('opt2' => 'value2'),
            ),
        ));
        $this->assertEquals($c->getConfigList('missing_opt', '--missing--'), '--missing--');
        $opts = $c->getConfigList('opts');
        $this->assertInternalType('array', $opts);
        $this->assertEquals(array_keys($opts), array('a', 'b'));
        $this->assertInstanceOf('SimpleSAML_Configuration', $opts['a']);
        $this->assertEquals($opts['a']->getValue('opt1'), 'value1');
        $this->assertInstanceOf('SimpleSAML_Configuration', $opts['b']);
        $this->assertEquals($opts['b']->getValue('opt2'), 'value2');
    }

    /**
     * Test SimpleSAML_Configuration::getConfigList() wrong option
     * @expectedException Exception
     */
    public function testGetConfigListWrong() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'opt' => 'not_an_array',
        ));
        $c->getConfigList('opt');
    }


    /**
     * Test SimpleSAML_Configuration::getConfigList() with an array of wrong options.
     * @expectedException Exception
     */
    public function testGetConfigListWrongArrayValues()
    {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'opts' => array(
                'a',
                'b',
            ),
        ));
        $c->getConfigList('opts');
    }


    /**
     * Test SimpleSAML_Configuration::getOptions()
     */
    public function testGetOptions() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'a' => true,
            'b' => null,
        ));
        $this->assertEquals($c->getOptions(), array('a', 'b'));
    }

    /**
     * Test SimpleSAML_Configuration::toArray()
     */
    public function testToArray() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'a' => true,
            'b' => null,
        ));
        $this->assertEquals($c->toArray(), array('a' => true, 'b' => null));
    }


    /**
     * Test SimpleSAML_Configuration::getDefaultEndpoint().
     *
     * Iterate over all different valid definitions of endpoints and check if the expected output is produced.
     */
    public function testGetDefaultEndpoint()
    {
        /*
         * First we run the full set of tests covering all possible configurations for indexed endpoint types,
         * basically AssertionConsumerService and ArtifactResolutionService. Since both are the same, we just run the
         * tests for AssertionConsumerService.
         */
        $acs_eps = array(
            // just a string with the location
            'https://example.com/endpoint.php',
            // an array of strings with location of different endpoints
            array(
                'https://www1.example.com/endpoint.php',
                'https://www2.example.com/endpoint.php',
            ),
            // define location and binding
            array(
                array(
                    'Location' => 'https://example.com/endpoint.php',
                    'Binding' => \SAML2\Constants::BINDING_HTTP_POST,
                ),
            ),
            // define the ResponseLocation too
            array(
                array(
                    'Location' => 'https://example.com/endpoint.php',
                    'Binding' => \SAML2\Constants::BINDING_HTTP_POST,
                    'ResponseLocation' => 'https://example.com/endpoint.php',
                ),
            ),
            // make sure indexes are NOT taken into account (they just identify endpoints)
            array(
                array(
                    'index' => 1,
                    'Location' => 'https://www1.example.com/endpoint.php',
                    'Binding' => \SAML2\Constants::BINDING_HTTP_REDIRECT,
                ),
                array(
                    'index' => 2,
                    'Location' => 'https://www2.example.com/endpoint.php',
                    'Binding' => \SAML2\Constants::BINDING_HTTP_POST,
                ),
            ),
            // make sure isDefault has priority over indexes
            array(
                array(
                    'index' => 1,
                    'Location' => 'https://www2.example.com/endpoint.php',
                    'Binding' => \SAML2\Constants::BINDING_HTTP_POST,
                ),
                array(
                    'index' => 2,
                    'isDefault' => true,
                    'Location' => 'https://www1.example.com/endpoint.php',
                    'Binding' => \SAML2\Constants::BINDING_HTTP_REDIRECT,
                ),
            ),
            // make sure endpoints with invalid bindings are ignored and those marked as NOT default are still used
            array(
                array(
                    'index' => 1,
                    'Location' => 'https://www1.example.com/endpoint.php',
                    'Binding' => 'invalid_binding',
                ),
                array(
                    'index' => 2,
                    'isDefault' => false,
                    'Location' => 'https://www2.example.com/endpoint.php',
                    'Binding' => \SAML2\Constants::BINDING_HTTP_POST,
                ),
            ),
        );
        $acs_expected_eps = array(
            // output should be completed with the default binding (HTTP-POST for ACS)
            array(
                'Location' => 'https://example.com/endpoint.php',
                'Binding' => \SAML2\Constants::BINDING_HTTP_POST,
            ),
            // we should just get the first endpoint with the default binding
            array(
                'Location' => 'https://www1.example.com/endpoint.php',
                'Binding' => \SAML2\Constants::BINDING_HTTP_POST,
            ),
            // if we specify the binding, we should get it back
            array(
                'Location' => 'https://example.com/endpoint.php',
                'Binding' => \SAML2\Constants::BINDING_HTTP_POST
            ),
            // if we specify ResponseLocation, we should get it back too
            array(
                'Location' => 'https://example.com/endpoint.php',
                'Binding' => \SAML2\Constants::BINDING_HTTP_POST,
                'ResponseLocation' => 'https://example.com/endpoint.php',
            ),
            // indexes must NOT be taken into account, order is the only thing that matters here
            array(
                'Location' => 'https://www1.example.com/endpoint.php',
                'Binding' => \SAML2\Constants::BINDING_HTTP_REDIRECT,
                'index' => 1,
            ),
            // isDefault must have higher priority than indexes
            array(
                'Location' => 'https://www1.example.com/endpoint.php',
                'Binding' => \SAML2\Constants::BINDING_HTTP_REDIRECT,
                'isDefault' => true,
                'index' => 2,
            ),
            // the first valid enpoint should be used even if it's marked as NOT default
            array(
                'index' => 2,
                'isDefault' => false,
                'Location' => 'https://www2.example.com/endpoint.php',
                'Binding' => \SAML2\Constants::BINDING_HTTP_POST,
            )
        );

        $a = array(
            'metadata-set' => 'saml20-sp-remote',
            'ArtifactResolutionService' => 'https://example.com/ars',
            'SingleSignOnService' => 'https://example.com/sso',
            'SingleLogoutService' => array(
                'Location' => 'https://example.com/slo',
                'Binding' => 'valid_binding', // test unknown bindings if we don't specify a list of valid ones
            ),
        );

        $valid_bindings = array(
            \SAML2\Constants::BINDING_HTTP_POST,
            \SAML2\Constants::BINDING_HTTP_REDIRECT,
            \SAML2\Constants::BINDING_HOK_SSO,
            \SAML2\Constants::BINDING_HTTP_ARTIFACT.
            \SAML2\Constants::BINDING_SOAP,
        );

        // run all general tests with AssertionConsumerService endpoint type
        foreach ($acs_eps as $i => $ep) {
            $a['AssertionConsumerService'] = $ep;
            $c = SimpleSAML_Configuration::loadFromArray($a);
            $this->assertEquals($acs_expected_eps[$i], $c->getDefaultEndpoint(
                'AssertionConsumerService',
                $valid_bindings
            ));
        }

        // now make sure SingleSignOnService, SingleLogoutService and ArtifactResolutionService works fine
        $a['metadata-set'] = 'shib13-idp-remote';
        $c = SimpleSAML_Configuration::loadFromArray($a);
        $this->assertEquals(
            array(
                'Location' => 'https://example.com/sso',
                'Binding' => 'urn:mace:shibboleth:1.0:profiles:AuthnRequest',
            ),
            $c->getDefaultEndpoint('SingleSignOnService')
        );
        $a['metadata-set'] = 'saml20-idp-remote';
        $c = SimpleSAML_Configuration::loadFromArray($a);
        $this->assertEquals(
            array(
                'Location' => 'https://example.com/ars',
                'Binding' => \SAML2\Constants::BINDING_SOAP,
            ),
            $c->getDefaultEndpoint('ArtifactResolutionService')
        );
        $this->assertEquals(
            array(
                'Location' => 'https://example.com/slo',
                'Binding' => \SAML2\Constants::BINDING_HTTP_REDIRECT,
            ),
            $c->getDefaultEndpoint('SingleLogoutService')
        );

        // test for old shib1.3 AssertionConsumerService
        $a['metadata-set'] = 'shib13-sp-remote';
        $a['AssertionConsumerService'] = 'https://example.com/endpoint.php';
        $c = SimpleSAML_Configuration::loadFromArray($a);
        $this->assertEquals(
            array(
                'Location' => 'https://example.com/endpoint.php',
                'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
            ),
            $c->getDefaultEndpoint('AssertionConsumerService')
        );

        // test for no valid endpoints specified
        $a['SingleLogoutService'] = array(
            array(
                'Location' => 'https://example.com/endpoint.php',
                'Binding' => 'invalid_binding',
                'isDefault' => true,
            ),
        );
        $c = SimpleSAML_Configuration::loadFromArray($a);
        try {
            $c->getDefaultEndpoint('SingleLogoutService', $valid_bindings);
            $this->fail('Failed to detect invalid endpoint binding.');
        } catch (Exception $e) {
            $this->assertEquals(
                '[ARRAY][\'SingleLogoutService\']:Could not find a supported SingleLogoutService '.'endpoint.',
                $e->getMessage()
            );
        }
        $a['metadata-set'] = 'foo';
        $c = SimpleSAML_Configuration::loadFromArray($a);
        try {
            $c->getDefaultEndpoint('SingleSignOnService');
            $this->fail('No valid metadata set specified.');
        } catch (Exception $e) {
            $this->assertStringStartsWith('Missing default binding for', $e->getMessage());
        }
    }


    /**
     * Test SimpleSAML_Configuration::getEndpoints().
     */
    public function testGetEndpoints()
    {
        // test response location for old-style configurations
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'metadata-set' => 'saml20-idp-remote',
            'SingleSignOnService' => 'https://example.com/endpoint.php',
            'SingleSignOnServiceResponse' => 'https://example.com/response.php',
        ));
        $e = array(
            array(
                'Location' => 'https://example.com/endpoint.php',
                'Binding' => \SAML2\Constants::BINDING_HTTP_REDIRECT,
                'ResponseLocation' => 'https://example.com/response.php',
            )
        );
        $this->assertEquals($e, $c->getEndpoints('SingleSignOnService'));

        // test for input failures

        // define a basic configuration array
        $a = array(
            'metadata-set' => 'saml20-idp-remote',
            'SingleSignOnService' => null,
        );

        // define a set of tests
        $tests = array(
            // invalid endpoint definition
            10,
            // invalid definition of endpoint inside the endpoints array
            array(
                1234
            ),
            // missing location
            array(
                array(
                    'foo' => 'bar',
                ),
            ),
            // invalid location
            array(
                array(
                    'Location' => 1234,
                )
            ),
            // missing binding
            array(
                array(
                    'Location' => 'https://example.com/endpoint.php',
                ),
            ),
            // invalid binding
            array(
                array(
                    'Location' => 'https://example.com/endpoint.php',
                    'Binding' => 1234,
                ),
            ),
            // invalid response location
            array(
                array(
                    'Location' => 'https://example.com/endpoint.php',
                    'Binding' => \SAML2\Constants::BINDING_HTTP_REDIRECT,
                    'ResponseLocation' => 1234,
                ),
            ),
            // invalid index
            array(
                array(
                    'Location' => 'https://example.com/endpoint.php',
                    'Binding' => \SAML2\Constants::BINDING_HTTP_REDIRECT,
                    'index' => 'string',
                ),
            ),
        );

        // define a set of exception messages to expect
        $msgs = array(
            'Expected array or string.',
            'Expected a string or an array.',
            'Missing Location.',
            'Location must be a string.',
            'Missing Binding.',
            'Binding must be a string.',
            'ResponseLocation must be a string.',
            'index must be an integer.',
        );

        // now run all the tests expecting the correct exception message
        foreach ($tests as $i => $test) {
            $a['SingleSignOnService'] = $test;
            $c = SimpleSAML_Configuration::loadFromArray($a);
            try {
                $c->getEndpoints('SingleSignOnService');
            } catch (Exception $e) {
                $this->assertStringEndsWith($msgs[$i], $e->getMessage());
            }
        }
    }


    /**
     * Test SimpleSAML_Configuration::getLocalizedString()
     */
    public function testGetLocalizedString() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'str_opt' => 'Hello World!',
            'str_array' => array(
                'en' => 'Hello World!',
                'no' => 'Hei Verden!',
            ),
        ));
        $this->assertEquals($c->getLocalizedString('missing_opt', '--missing--'), '--missing--');
        $this->assertEquals($c->getLocalizedString('str_opt'), array('en' => 'Hello World!'));
        $this->assertEquals($c->getLocalizedString('str_array'), array('en' => 'Hello World!', 'no' => 'Hei Verden!'));
    }

    /**
     * Test SimpleSAML_Configuration::getLocalizedString() not array nor simple string
     * @expectedException Exception
     */
    public function testGetLocalizedStringNotArray() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'opt' => 42,
        ));
        $c->getLocalizedString('opt');
    }

    /**
     * Test SimpleSAML_Configuration::getLocalizedString() not string key
     * @expectedException Exception
     */
    public function testGetLocalizedStringNotStringKey() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'opt' => array(42 => 'text'),
        ));
        $c->getLocalizedString('opt');
    }

    /**
     * Test SimpleSAML_Configuration::getLocalizedString() not string value
     * @expectedException Exception
     */
    public function testGetLocalizedStringNotStringValue() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'opt' => array('en' => 42),
        ));
        $c->getLocalizedString('opt');
    }

    /**
     * Test that Configuration objects can be initialized from an array.
     *
     * ATTENTION: this test must be kept the last.
     */
    public function testLoadInstanceFromArray()
    {
        $c = array(
            'key' => 'value'
        );
        // test loading a custom instance
        SimpleSAML_Configuration::loadFromArray($c, '', 'dummy');
        $this->assertEquals('value', SimpleSAML_Configuration::getInstance('dummy')->getValue('key', null));

        // test loading the default instance
        SimpleSAML_Configuration::loadFromArray($c, '', 'simplesaml');
        $this->assertEquals('value', SimpleSAML_Configuration::getInstance()->getValue('key', null));
    }
}
