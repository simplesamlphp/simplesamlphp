<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils;

use Exception;
use InvalidArgumentException;
use SimpleSAML\Configuration;
use SimpleSAML\Test\Utils\TestCase;
use SimpleSAML\Utils\EMail;

/**
 * A base SSP test case that tests some simple e-mail related calls
 *
 * @covers \SimpleSAML\Utils\EMail
 */
class EMailTest extends ClearStateTestCase
{
    /**
     */
    public function setUp(): void
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
    public function testMailFromDefaultConfigurationException(): void
    {
        $this->expectException(Exception::class);
        new EMail('test', null, 'phpunit@simplesamlphp.org');
    }


    /**
     * Test that an exception is thrown if using an invalid "From"-address
     */
    public function testInvalidFromAddressException(): void
    {
        $this->expectException(Exception::class);
        new EMail('test', "phpunit@simplesamlphp.org\nLorem Ipsum", 'phpunit@simplesamlphp.org');
    }


    /**
     * Test that an exception is thrown if using an invalid "To"-address
     */
    public function testInvalidToAddressException(): void
    {
        $this->expectException(Exception::class);
        new EMail('test', 'phpunit@simplesamlphp.org', "phpunit@simplesamlphp.org\nLorem Ipsum");
    }


    /**
     * Test that the data given is visible in the resulting mail
     * @dataProvider mailTemplates
     * @param string $template
     */
    public function testMailContents($template): void
    {
        $mail = new EMail(
            'subject-subject-subject-subject-subject-subject-subject',
            'phpunit@simplesamlphp.org',
            'phpunit@simplesamlphp.org'
        );
        $mail->setText('text-text-text-text-text-text-text');
        $mail->setData(['key-key-key-key-key-key-key' => 'value-value-value-value-value-value-value']);
        $result = $mail->generateBody($template);
        $this->assertMatchesRegularExpression('/(subject-){6}/', $result);
        $this->assertMatchesRegularExpression('/(text-){6}/', $result);
        $this->assertMatchesRegularExpression('/(key-){6}/', $result);
        $this->assertMatchesRegularExpression('/(value-){6}/', $result);
    }


    /**
     * All templates that should be tested in #testMailContents($template)
     * @return array
     */
    public static function mailTemplates(): array
    {
        return [['mailtxt.twig'], ['mailhtml.twig']];
    }


    /**
     */
    public function testInvalidTransportConfiguration(): void
    {
        // preserve the original configuration
        $originalTestConfiguration = Configuration::getInstance()->toArray();

        // load the configuration with an invalid mail.transport.method
        Configuration::loadFromArray(array_merge($originalTestConfiguration, [
            'mail.transport.method' => 'foobar'
        ]), '[ARRAY]', 'simplesaml');


        $this->expectException(InvalidArgumentException::class);
        new Email('Test', 'phpunit@simplesamlphp.org', 'phpunit@simplesamlphp.org');

        // reset the configuration
        Configuration::loadFromArray($originalTestConfiguration, '[ARRAY]', 'simplesaml');
    }


    /**
     */
    public function testInvalidSMTPConfiguration(): void
    {
        // setup a new email
        $email = new Email('Test', 'phpunit@simplesamlphp.org', 'phpunit@simplesamlphp.org');

        // set the transport option to smtp but don't set any transport options (invalid state)
        // NOTE: this is the same method that the constructor calls, so this should be logically equivalent
        // to setting it via the configuration file.
        $this->expectException(InvalidArgumentException::class);
        $email->setTransportMethod('smtp');
    }

    /**
     * Test setting configuration.
     *
     */
    public function testGetDefaultMailAddress(): void
    {
        Configuration::loadFromArray([
            'technicalcontact_email' => 'gamaarna@example.org',
        ], '[ARRAY]', 'simplesaml');

        $mail = new EMail('test', null, 'phpunit@simplesamlphp.org');
        $this->assertEquals('gamaarna@example.org', $mail->getDefaultMailAddress());

        Configuration::loadFromArray([
            'technicalcontact_email' => 'mailto:gamaarna@example.org',
        ], '[ARRAY]', 'simplesaml');

        $mail = new EMail('test', null, 'phpunit@simplesamlphp.org');
        $this->assertEquals('gamaarna@example.org', $mail->getDefaultMailAddress());
    }
}
