<?php
/**
 * Test for the consent:Process filter.
 *
 * @author Vincent Rioux <vrioux@ctech.ca>
 * @package SimpleSAMLphp
 */

// Consent module has no namespace yet.  We should add it and then add it here also
//namespace SimpleSAML\Test\Module\consent\Auth\Process;


class ConsentTest extends \PHPUnit_Framework_TestCase
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
        $filter = new sspmod_consent_Auth_Process_Consent($config, null);
        $filter->process($request);
        return $request;
    }


    /**
     * Test valid consent disable.
     */
    public function testValidConsentDisableRegex()
    {
        // test consent disable regex with match
        $config = array(
            'consent.disable' => array(
                'type'=>'regex', 'pattern'=>'/.*\.example\.org.*/i',
            ),
        );
        $request = array(
            'Source'     => array(
                'SingleSignOnService' => array(
                    array(
                        'Binding'  => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'Location' => 'https://www.example.org/saml2/idp/SSOService.php',
                    ),
                ),
            ),
            'Attributes' => array(
                'eduPersonPrincipalName' => array('jdoe@example.com'),
            ),
        );
        $result = $this->processFilter($config, $request);
        $this->assertEquals($request['Attributes'], $result['Attributes']);

        // test consent disable regex without match
        $config = array(
            'consent.disable' => array(
                'type'=>'regex', 'pattern'=>'/.*\.otherexample\.org.*/i',
            ),
        );
        $request = array(
            'Source'     => array(
                'SingleSignOnService' => array(
                    array(
                        'Binding'  => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'Location' => 'https://www.example.org/saml2/idp/SSOService.php',
                    ),
                ),
            ),
            'Attributes' => array(
                'eduPersonPrincipalName' => array('jdoe@example.com'),
            ),
        );
        $result = $this->processFilter($config, $request);
        $this->assertEquals(array(), $result['Attributes']);
    }


    /**
     * Test invalid consent disable.
     */
    public function testInvalidConsentDisable()
    {
        // test consent disable regex with wrong value format in config
        $config = array(
            'consent.disable' => array(
                'type'=>'regex', '/.*\.example\.org.*/i',
            ),
        );
        $request = array(
            'Source'     => array(
                'SingleSignOnService' => array(
                    array(
                        'Binding'  => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'Location' => 'https://www.example.org/saml2/idp/SSOService.php',
                    ),
                ),
            ),
            'Attributes' => array(
                'eduPersonPrincipalName' => array('jdoe@example.com'),
            ),
        );
        $result = $this->processFilter($config, $request);
        $this->assertEquals(array(), $result['Attributes']);
    }
}
