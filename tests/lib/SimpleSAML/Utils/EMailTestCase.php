<?php

namespace SimpleSAML\Test\Utils;

use SimpleSAML\Test\Utils\TestCase;

use SimpleSAML\Configuration;
use SimpleSAML\Utils\EMail;

/**
 * A base SSP test case that tests some simple e-mail related calls
 */
class EMailTestCase extends ClearStateTestCase
{
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
     */
    public function testMailFromDefaultConfigurationException()
    {
        $this->expectException(\Exception::class);
        new EMail('test', null, 'phpunit@simplesamlphp.org');
    }

    /**
     * Test that an exception is thrown if using an invalid "From"-address
     */
    public function testInvalidFromAddressException()
    {
        $this->expectException(\Exception::class);
        new EMail('test', "phpunit@simplesamlphp.org\nLorem Ipsum", 'phpunit@simplesamlphp.org');
    }

    /**
     * Test that an exception is thrown if using an invalid "To"-address
     */
    public function testInvalidToAddressException()
    {
        $this->expectException(\Exception::class);
        new EMail('test', 'phpunit@simplesamlphp.org', "phpunit@simplesamlphp.org\nLorem Ipsum");
    }

    /**
     * Test that the data given is visible in the resulting mail
     * @dataProvider mailTemplates
     */
    public function testMailContents($template)
    {
        $mail = new EMail('subject-subject-subject-subject-subject-subject-subject', 'phpunit@simplesamlphp.org', 'phpunit@simplesamlphp.org');
        $mail->setText('text-text-text-text-text-text-text');
        $mail->setData(['key-key-key-key-key-key-key' => 'value-value-value-value-value-value-value']);
        $result = $mail->generateBody($template);
        $this->assertRegexp('/(subject-){6}/', $result);
        $this->assertRegexp('/(text-){6}/', $result);
        $this->assertRegexp('/(key-){6}/', $result);
        $this->assertRegexp('/(value-){6}/', $result);
    }

    /** All templates that should be tested in #testMailContents($template) */
    public static function mailTemplates()
    {
        return [['mailtxt.twig'], ['mailhtml.twig']];
    }
}
