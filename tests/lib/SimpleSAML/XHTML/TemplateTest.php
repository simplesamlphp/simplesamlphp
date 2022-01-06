<?php

declare(strict_types=1);

namespace SimpleSAML\Test\XHTML;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\XHTML\Template;

/**
 * @covers \SimpleSAML\XHTML\Template
 */
class TemplateTest extends TestCase
{
    private const TEMPLATE = 'sandbox.twig';

    public function testSetup(): void
    {
        $c = Configuration::loadFromArray([], '', 'simplesaml');
        $t = new Template($c, self::TEMPLATE);
        $this->assertEquals(self::TEMPLATE, $t->getTemplateName());
    }

    public function testNormalizeName(): void
    {
        $c = Configuration::loadFromArray([], '', 'simplesaml');
        $t = new Template($c, 'sandbox');
        $this->assertEquals(self::TEMPLATE, $t->getTemplateName());
    }

    public function testTemplateModuleNamespace(): void
    {
        $c = Configuration::loadFromArray([], '', 'simplesaml');
        $t = new Template($c, 'core:welcome');
        $this->assertEquals('core:welcome.twig', $t->getTemplateName());
    }

    public function testGetEntityDisplayNameBasic(): void
    {
        $c = Configuration::loadFromArray([], '', 'simplesaml');
        $t = new Template($c, self::TEMPLATE);

        $data = [
            'entityid' => 'urn:example.org',
            'name' => ['nl' => 'Something', 'en' => 'Other lang'],
        ];
        $name = $t->getEntityDisplayName($data);
        $this->assertEquals('Other lang', $name);

        $c = Configuration::loadFromArray(['language.default' => 'nl'], '', 'simplesaml');
        $t = new Template($c, self::TEMPLATE);
        $name = $t->getEntityDisplayName($data);
        $this->assertEquals('Something', $name);
    }

    public function testGetEntityDisplayNamePriorities(): void
    {
        $c = Configuration::loadFromArray([], '', 'simplesaml');
        $t = new Template($c, self::TEMPLATE);

        $data = [
            'entityid' => 'urn:example.org',
        ];
        $name = $t->getEntityDisplayName($data);
        $this->assertEquals('urn:example.org', $name);

        $data['OrganizationName'] = ['fr' => 'Example Org', 'nl' => 'Anything Org'];
        $data['OrganizationDisplayName'] = ['fr' => 'DisplayExample', 'nl' => 'DisplayAnything'];

        $name = $t->getEntityDisplayName($data);
        $this->assertEquals('urn:example.org', $name);

        $data['OrganizationName']['en'] = 'Example Org EN';

        $name = $t->getEntityDisplayName($data);
        $this->assertEquals('Example Org EN', $name);

        $c = Configuration::loadFromArray(['language.default' => 'nl'], '', 'simplesaml');
        $t = new Template($c, self::TEMPLATE);

        $name = $t->getEntityDisplayName($data);
        $this->assertEquals('DisplayAnything', $name);

        $data['UIInfo']['DisplayName'] = ['de' => 'UIname', 'nl' => 'UIname NL'];
        $name = $t->getEntityDisplayName($data);
        $this->assertEquals('UIname NL', $name);
    }

    public function testGetEntityPropertyTranslation(): void
    {
        $c = Configuration::loadFromArray([], '', 'simplesaml');
        $t = new Template($c, self::TEMPLATE);

        $prop = 'description';
        $data = [
            'entityid' => 'urn:example.org',
            $prop => ['nl' => 'Something', 'en' => 'Other lang', 'fr' => 'Another desc'],
        ];
        $name = $t->getEntityPropertyTranslation($prop, $data);
        $this->assertEquals('Other lang', $name);

        $c = Configuration::loadFromArray(['language.default' => 'nl'], '', 'simplesaml');
        $t = new Template($c, self::TEMPLATE);
        $name = $t->getEntityPropertyTranslation($prop, $data);
        $this->assertEquals('Something', $name);

        unset($data[$prop]['nl']);
        $name = $t->getEntityPropertyTranslation($prop, $data);
        $this->assertEquals('Other lang', $name);

        unset($data[$prop]['en']);
        $name = $t->getEntityPropertyTranslation($prop, $data);
        $this->assertNull($name);
    }
}
