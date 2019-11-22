<?php

namespace SimpleSAML\Test\Utils;

use SimpleSAML\Configuration;
use SimpleSAML\Test\Utils\TestCase;
use SimpleSAML\Utils\EMail;

/**
 * A base SSP test case that tests some simple e-mail related calls
 */
class EMailTestCase extends ClearStateTestCase
{
    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        // Override configuration
        Configuration::loadFromArray([
            'technicalcontact_email' => 'na@example.org',
        ], '[ARRAY]', 'simplesaml');
    }


    /**
     * Test that an exception is thrown if using default configuration,
     * and no custom from address is specified.
     * @return void
     */
    public function testMailFromDefaultConfigurationException()
    {
        $this->expectException(\Exception::class);
        new EMail('test', null, 'phpunit@simplesamlphp.org');
    }


    /**
     * Test that an exception is thrown if using an invalid "From"-address
     * @return void
     */
    public function testInvalidFromAddressException()
    {
        $this->expectException(\Exception::class);
        new EMail('test', "phpunit@simplesamlphp.org\nLorem Ipsum", 'phpunit@simplesamlphp.org');
    }


    /**
     * Test that an exception is thrown if using an invalid "To"-address
     * @return void
     */
    public function testInvalidToAddressException()
    {
        $this->expectException(\Exception::class);
        new EMail('test', 'phpunit@simplesamlphp.org', "phpunit@simplesamlphp.org\nLorem Ipsum");
    }


    /**
     * Test that the data given is visible in the resulting mail
     * @dataProvider mailTemplates
     * @param string $template
     * @return void
     */
    public function testMailContents($template)
    {
        $mail = new EMail(
            'subject-subject-subject-subject-subject-subject-subject',
            'phpunit@simplesamlphp.org',
            'phpunit@simplesamlphp.org'
        );
        $mail->setText('text-text-text-text-text-text-text');
        $mail->setData(['key-key-key-key-key-key-key' => 'value-value-value-value-value-value-value']);
        $result = $mail->generateBody($template);
        $this->assertRegexp('/(subject-){6}/', $result);
        $this->assertRegexp('/(text-){6}/', $result);
        $this->assertRegexp('/(key-){6}/', $result);
        $this->assertRegexp('/(value-){6}/', $result);
    }


    /**
     * All templates that should be tested in #testMailContents($template)
     * @return array
     */
    public static function mailTemplates()
    {
        return [['mailtxt.twig'], ['mailhtml.twig']];
    }


    /**
     * @return void
     */
    public function testInvalidTransportConfiguration()
    {
        // preserve the original configuration
        $originalTestConfiguration = Configuration::getInstance()->toArray();

        // load the configuration with an invalid mail.transport.method
        Configuration::loadFromArray(array_merge($originalTestConfiguration, [
            'mail.transport.method' => 'foobar'
        ]), '[ARRAY]', 'simplesaml');


        $this->expectException(\InvalidArgumentException::class);
        new Email('Test', 'phpunit@simplesamlphp.org', 'phpunit@simplesamlphp.org');

        // reset the configuration
        Configuration::loadFromArray($originalTestConfiguration, '[ARRAY]', 'simplesaml');
    }


    /**
     * @return void
     */
    public function testInvalidSMTPConfiguration()
    {
        // setup a new email
        $email = new Email('Test', 'phpunit@simplesamlphp.org', 'phpunit@simplesamlphp.org');

        // set the transport option to smtp but don't set any transport options (invalid state)
        // NOTE: this is the same method that the constructor calls, so this should be logically equivalent
        // to setting it via the configuration file.
        $this->expectException(\InvalidArgumentException::class);
        $email->setTransportMethod('smtp');
    }
}
