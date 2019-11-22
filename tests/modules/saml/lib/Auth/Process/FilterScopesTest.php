<?php

namespace SimpleSAML\Test\Module\saml\Auth\Process;

use PHPUnit\Framework\TestCase;

/**
 * Test for the saml:FilterScopes filter.
 *
 * @author Jaime Pérez Crespo, UNINETT AS <jaime.perez@uninett.no>
 * @package SimpleSAMLphp
 */
class FilterScopesTest extends TestCase
{
    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param array $config  The filter configuration.
     * @param array $request  The request state.
     * @return array  The state array after processing.
     */
    private function processFilter(array $config, array $request)
    {
        $filter = new \SimpleSAML\Module\saml\Auth\Process\FilterScopes($config, null);
        $filter->process($request);
        return $request;
    }


    /**
     * Test valid scopes.
     * @return void
     */
    public function testValidScopes()
    {
        // test declared scopes
        $config = [];
        $request = [
            'Source'     => [
                'SingleSignOnService' => [
                    [
                        'Binding'  => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'Location' => 'https://example.org/saml2/idp/SSOService.php',
                    ],
                ],
                'scope' => [
                    'example.com',
                    'example.net',
                ],
            ],
            'Attributes' => [
                'eduPersonPrincipalName' => ['jdoe@example.com'],
            ],
        ];
        $result = $this->processFilter($config, $request);
        $this->assertEquals($request['Attributes'], $result['Attributes']);

        // test multiple values
        $request['Attributes'] = [
            'eduPersonPrincipalName' => [
                'jdoe@example.com',
                'jdoe@example.net',
            ],
        ];
        $result = $this->processFilter($config, $request);
        $this->assertEquals($request['Attributes'], $result['Attributes']);

        // test implicit scope
        $request['Attributes'] = [
            'eduPersonPrincipalName' => ['jdoe@example.org'],
        ];
        $result = $this->processFilter($config, $request);
        $this->assertEquals($request['Attributes'], $result['Attributes']);

        // test alternative attributes
        $config['attributes'] = [
            'mail',
        ];
        $request['Attributes'] = [
            'mail' => ['john.doe@example.org'],
        ];
        $result = $this->processFilter($config, $request);
        $this->assertEquals($request['Attributes'], $result['Attributes']);

        // test non-scoped attributes
        $request['Attributes']['givenName'] = 'John Doe';
        $result = $this->processFilter($config, $request);
        $this->assertEquals($request['Attributes'], $result['Attributes']);
    }


    /**
     * Test invalid scopes.
     * @return void
     */
    public function testInvalidScopes()
    {
        // test scope not matching anything, empty attribute
        $config = [];
        $request = [
            'Source'     => [
                'SingleSignOnService' => [
                    [
                        'Binding'  => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'Location' => 'https://example.org/saml2/idp/SSOService.php',
                    ],
                ],
                'scope' => [
                    'example.com',
                    'example.net',
                ],
            ],
            'Attributes' => [
                'eduPersonPrincipalName' => ['jdoe@example.edu'],
            ],
        ];
        $result = $this->processFilter($config, $request);
        $this->assertEquals([], $result['Attributes']);

        // test some scopes allowed and some others not
        $request['Attributes']['eduPersonPrincipalName'][] = 'jdoe@example.com';
        $result = $this->processFilter($config, $request);
        $this->assertEquals(
            [
                'eduPersonPrincipalName' => [
                    'jdoe@example.com',
                ],
            ],
            $result['Attributes']
        );

        // test attribute missing scope
        $request['Attributes'] = [
            'eduPersonPrincipalName' => ['jdoe'],
        ];
        $result = $this->processFilter($config, $request);
        $this->assertEquals($request['Attributes'], $result['Attributes']);
    }
}
