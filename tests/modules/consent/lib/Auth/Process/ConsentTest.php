<?php
/**
 * Test for the consent:Consent authproc filter.
 *
 * @author Vincent Rioux <vrioux@ctech.ca>
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\Test\Module\consent\Auth\Process;

use PHPUnit\Framework\TestCase;
use \SimpleSAML_Configuration as Configuration;

class ConsentTest extends TestCase
{
    public function setUp()
    {
        $this->config = Configuration::loadFromArray(array(), '[ARRAY]', 'simplesaml');
    }

    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param array $config  The filter configuration.
     * @param array $request  The request state.
     * @return array  The state array after processing.
     */
    private function processFilter(array $config, array $request)
    {
        $filter = new \sspmod_consent_Auth_Process_Consent($config, null);
        $filter->process($request);
        return $request;
    }


    /**
     * Test for the private checkDisable() method.
     */
    public function testCheckDisable()
    {
        // test consent disable regex with match
        $config = array();

        // test consent disable with match on specific SP entityid
        $request = array(
            'Source'     => array(
                'entityid' => 'https://idp.example.org',
                'metadata-set' => 'saml20-idp-local',
                'consent.disable' => array(
                    'https://valid.flatstring.example.that.does.not.match',
                ),
                'SingleSignOnService' => array(
                    array(
                        'Binding'  => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'Location' => 'https://idp.example.org/saml2/idp/SSOService.php',
                    ),
                ),
            ),
            'Destination' => array(
                // valid entityid equal to the last one in the consent.disable array
                'entityid' => 'https://sp.example.org/my-sp',
                'metadata-set' => 'saml20-sp-remote',
                'consent.disable' => array(
                    array('type' => 'regex', 'pattern' => '/invalid/i'),
                    'https://sp.example.org/my-sp', // accept the SP that has this specific entityid
                    'https://idp.example.org',
                ),
            ),
            'UserID' => 'jdoe',
            'Attributes' => array(
                'eduPersonPrincipalName' => array('jdoe@example.com'),
            ),
        );
        $result = $this->processFilter($config, $request);
        // the state should NOT have changed because NO consent should be necessary (match)
        $this->assertEquals($request, $result);

        // test consent disable with match on SP through regular expression
        $request = array(
            'Source'     => array(
                'entityid' => 'https://idp.example.org',
                'metadata-set' => 'saml20-idp-local',
                'consent.disable' => array(
                    array(), // invalid consent option array should be ignored
                    1234, // bad option
                    array(''), // no type
                    array('type'=>'invalid'), // invalid consent option type should be ignored
                    array('type'=>'regex'), // regex consent option without pattern should be ignored
                    array('type'=>'regex', 'pattern'=>'/.*\.valid.regex\.that\.does\.not\.match.*/i'),
                    // accept any SP that has an entityid that contains the string ".example.org"
                    array('type'=>'regex', 'pattern'=>'/.*\.example\.org\/.*/i'),
                ),
                'SingleSignOnService' => array(
                    array(
                        'Binding'  => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'Location' => 'https://idp.example.org/saml2/idp/SSOService.php',
                    ),
                ),
            ),
            'Destination' => array(
                'entityid' => 'https://sp.example.org/my-sp', // sp contains the string ".example.org"
                'metadata-set' => 'saml20-sp-remote',
            ),
            'UserID' => 'jdoe',
            'Attributes' => array(
                'eduPersonPrincipalName' => array('jdoe@example.com'),
            ),
        );
        $result = $this->processFilter($config, $request);
        // the state should NOT have changed because NO consent should be necessary (match)
        $this->assertEquals($request, $result);

        // test corner cases
        $request['Source']['consent.disable'] = array(
            'https://valid.flatstring.example.that.does.not.match',
            array('foo' => 'bar'),
        );
        $request['Destination']['consent.disable'] = 1;
        $result = $this->processFilter($config, $request);
        // the state should NOT have changed because NO consent should be necessary (match)
        $this->assertEquals($request, $result);
    }

    public function testAttributeHashIsConsistentWhenOrderOfValuesChange()
    {
        $attributes1 = array(
            'attribute1' => array('val1', 'val2'),
            'attribute2' => array('val1', 'val2')
        );
        $attributeHash1 = \sspmod_consent_Auth_Process_Consent::getAttributeHash($attributes1, true);

        $attributes2 = array(
            'attribute1' => array('val1', 'val2'),
            'attribute2' => array('val2', 'val1')
        );
        $attributeHash2 = \sspmod_consent_Auth_Process_Consent::getAttributeHash($attributes2, true);

        $this->assertEquals($attributeHash1, $attributeHash2, "Hash is not the same when the order of values changes");
    }

    public function testAttributeHashIsConsistentWhenOrderOfAttributesChange()
    {
        $attributes1 = array(
            'attribute2' => array('val1', 'val2'),
            'attribute1' => array('val1', 'val2')
        );
        $attributeHash1 = \sspmod_consent_Auth_Process_Consent::getAttributeHash($attributes1, true);

        $attributes2 = array(
            'attribute1' => array('val1', 'val2'),
            'attribute2' => array('val1', 'val2')
        );
        $attributeHash2 = \sspmod_consent_Auth_Process_Consent::getAttributeHash($attributes2, true);

        $this->assertEquals(
            $attributeHash1,
            $attributeHash2,
            "Hash is not the same when the order of the attributs changes"
        );
    }

    public function testAttributeHashIsConsistentWithoutValuesWhenOrderOfAttributesChange()
    {
        $attributes1 = array(
            'attribute2' => array('val1', 'val2'),
            'attribute1' => array('val1', 'val2')
        );
        $attributeHash1 = \sspmod_consent_Auth_Process_Consent::getAttributeHash($attributes1);

        $attributes2 = array(
            'attribute1' => array('val1', 'val2'),
            'attribute2' => array('val1', 'val2')
        );
        $attributeHash2 = \sspmod_consent_Auth_Process_Consent::getAttributeHash($attributes2);

        $this->assertEquals(
            $attributeHash1,
            $attributeHash2,
            "Hash is not the same when the order of the attributs changes and the values are not included"
        );
    }

    public function testConstructorSetsInstancePrivateVars()
    {
        $reflection = new \ReflectionClass('\sspmod_consent_Auth_Process_Consent');

        foreach (array(
            '_includeValues', '_checked', '_focus', '_hiddenAttributes', '_noconsentattributes', '_showNoConsentAboutService'
        ) as $v) {
            $instanceVars[$v] = $reflection->getProperty($v);
            $instanceVars[$v]->setAccessible(true);
        }

        /* these just need to be different to the default values */
        $config = array(
            'includeValues' => true,
            'checked' => true,
            'focus' => 'yes',
            'hiddenAttributes' => array('attribute1', 'attribute2'),
            'attributes.exclude' => array('attribute1', 'attribute2'),
            'showNoConsentAboutService' => false,
        );

        $testcase = $reflection->newInstance($config, null);

        $this->assertEquals($instanceVars['_includeValues']->getValue($testcase), $config['includeValues']);
        $this->assertEquals($instanceVars['_checked']->getValue($testcase), $config['checked']);
        $this->assertEquals($instanceVars['_focus']->getValue($testcase), $config['focus']);
        $this->assertEquals($instanceVars['_hiddenAttributes']->getValue($testcase), $config['hiddenAttributes']);
        $this->assertEquals($instanceVars['_noconsentattributes']->getValue($testcase), $config['attributes.exclude']);
        $this->assertEquals($instanceVars['_showNoConsentAboutService']->getValue($testcase), $config['showNoConsentAboutService']);

        $deprecated = $reflection->newInstance(array('noconsentattributes' => $config['attributes.exclude'],), null);
        $this->assertEquals($instanceVars['_noconsentattributes']->getValue($deprecated), $config['attributes.exclude']);

    }
}
