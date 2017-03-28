<?php
/**
 * Test for the saml:FilterScopes filter.
 *
 * @author Jaime Pérez Crespo, UNINETT AS <jaime.perez@uninett.no>
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\Test\Module\saml\Auth\Process;


class FilterScopesTest extends \PHPUnit_Framework_TestCase
{

    /*
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
     */
    public function testValidScopes()
    {
        // test declared scopes
        $config = array();
        $request = array(
            'Source'     => array(
                'SingleSignOnService' => array(
                    array(
                        'Binding'  => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'Location' => 'https://example.org/saml2/idp/SSOService.php',
                    ),
                ),
                'scope' => array(
                    'example.com',
                    'example.net',
                ),
            ),
            'Attributes' => array(
                'eduPersonPrincipalName' => array('jdoe@example.com'),
            ),
        );
        $result = $this->processFilter($config, $request);
        $this->assertEquals($request['Attributes'], $result['Attributes']);

        // test multiple values
        $request['Attributes'] = array(
            'eduPersonPrincipalName' => array(
                'jdoe@example.com',
                'jdoe@example.net',
            ),
        );
        $result = $this->processFilter($config, $request);
        $this->assertEquals($request['Attributes'], $result['Attributes']);

        // test implicit scope
        $request['Attributes'] = array(
            'eduPersonPrincipalName' => array('jdoe@example.org'),
        );
        $result = $this->processFilter($config, $request);
        $this->assertEquals($request['Attributes'], $result['Attributes']);

        // test alternative attributes
        $config['attributes'] = array(
            'mail',
        );
        $request['Attributes'] = array(
            'mail' => array('john.doe@example.org'),
        );
        $result = $this->processFilter($config, $request);
        $this->assertEquals($request['Attributes'], $result['Attributes']);

        // test non-scoped attributes
        $request['Attributes']['givenName'] = 'John Doe';
        $result = $this->processFilter($config, $request);
        $this->assertEquals($request['Attributes'], $result['Attributes']);
    }


    /**
     * Test invalid scopes.
     */
    public function testInvalidScopes()
    {
        // test scope not matching anything, empty attribute
        $config = array();
        $request = array(
            'Source'     => array(
                'SingleSignOnService' => array(
                    array(
                        'Binding'  => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'Location' => 'https://example.org/saml2/idp/SSOService.php',
                    ),
                ),
                'scope' => array(
                    'example.com',
                    'example.net',
                ),
            ),
            'Attributes' => array(
                'eduPersonPrincipalName' => array('jdoe@example.edu'),
            ),
        );
        $result = $this->processFilter($config, $request);
        $this->assertEquals(array(), $result['Attributes']);

        // test some scopes allowed and some others not
        $request['Attributes']['eduPersonPrincipalName'][] = 'jdoe@example.com';
        $result = $this->processFilter($config, $request);
        $this->assertEquals(
            array(
                'eduPersonPrincipalName' => array(
                    'jdoe@example.com',
                ),
            ),
            $result['Attributes']
        );

        // test attribute missing scope
        $request['Attributes'] = array(
            'eduPersonPrincipalName' => array('jdoe'),
        );
        $result = $this->processFilter($config, $request);
        $this->assertEquals($request['Attributes'], $result['Attributes']);
    }
}
