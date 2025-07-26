<?php

declare(strict_types=1);

namespace SimpleSAML\Test\XHTML;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\XHTML\Template;

/**
 */
#[CoversClass(Template::class)]
class TemplateTest extends TestCase
{
    private const TEMPLATE = 'sandbox.twig';

    public function testSetup(): void
    {
        $c = Configuration::loadFromArray(['assets' => [ 'salt' => '1234567890']], '', 'simplesaml');
        $t = new Template($c, self::TEMPLATE);
        $this->assertEquals(self::TEMPLATE, $t->getTemplateName());
    }

    public function testNormalizeName(): void
    {
        $c = Configuration::loadFromArray(['assets' => [ 'salt' => '1234567890']], '', 'simplesaml');
        $t = new Template($c, 'sandbox');
        $this->assertEquals(self::TEMPLATE, $t->getTemplateName());
    }

    public function testTemplateModuleNamespace(): void
    {
        $c = Configuration::loadFromArray(['assets' => [ 'salt' => '1234567890']], '', 'simplesaml');
        $t = new Template($c, 'core:welcome');
        $this->assertEquals('core:welcome.twig', $t->getTemplateName());
    }

    public static function debugModeProvider(): array
    {
        return [
            'on' => [true],
            'off' => [false],
        ];
    }

    #[DataProvider('debugModeProvider')]
    public function testTemplateDebugMode(bool $debugMode): void
    {
        $c = Configuration::loadFromArray(['template.debug' => $debugMode]);
        $t = new Template($c, self::TEMPLATE);
        $extensionsEnabled = array_keys($t->getTwig()->getExtensions());
        if ($debugMode) {
            $this->assertContains('Twig\Extension\DebugExtension', $extensionsEnabled);
            $this->assertTrue($t->getTwig()->isDebug());
        } else {
            $this->assertNotContains('Twig\Extension\DebugExtension', $extensionsEnabled);
            $this->assertFalse($t->getTwig()->isDebug());
        }
    }

    public function testGetEntityDisplayNameBasic(): void
    {
        $c = Configuration::loadFromArray(['assets' => [ 'salt' => '1234567890']], '', 'simplesaml');
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
        $c = Configuration::loadFromArray(['assets' => [ 'salt' => '1234567890']], '', 'simplesaml');
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

        $c = Configuration::loadFromArray(
            ['language.default' => 'nl', 'assets.salt' => '1234567890'],
            '',
            'simplesaml',
        );
        $t = new Template($c, self::TEMPLATE);

        $name = $t->getEntityDisplayName($data);
        $this->assertEquals('DisplayAnything', $name);

        $data['UIInfo']['DisplayName'] = ['de' => 'UIname', 'nl' => 'UIname NL'];
        $name = $t->getEntityDisplayName($data);
        $this->assertEquals('UIname NL', $name);
    }

    public function testGetEntityPropertyTranslation(): void
    {
        $c = Configuration::loadFromArray(['assets' => [ 'salt' => '1234567890']], '', 'simplesaml');
        $t = new Template($c, self::TEMPLATE);

        $prop = 'description';
        $data = [
            'entityid' => 'urn:example.org',
            $prop => ['nl' => 'Something', 'en' => 'Other lang', 'fr' => 'Another desc'],
        ];
        $name = $t->getEntityPropertyTranslation($prop, $data);
        $this->assertEquals('Other lang', $name);

        $c = Configuration::loadFromArray(
            ['language.default' => 'nl', 'assets.salt' => '1234567890'],
            '',
            'simplesaml',
        );
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

    public function testAssetModuleTagDoesNotMatchCoreTag(): void
    {
        $c = Configuration::loadFromArray(['assets' => [ 'salt' => '1234567890']], '', 'simplesaml');
        $moduleTemplate = new Template($c, 'admin:status');
        $tagModule = $moduleTemplate->asset('css/admin.css', 'admin');
        $this->assertStringContainsString('?tag=', $tagModule);
        $tagModuleQuery = explode("=", $tagModule)[1];

        $coreTemplate = new Template($c, 'status');
        $tagCore = $coreTemplate->asset('css/stylesheet.css');
        $this->assertStringContainsString('?tag=', $tagCore);
        $tagCoreQuery = explode("=", $tagCore)[1];
        $this->assertNotEquals(
            $tagModuleQuery,
            $tagCoreQuery,
        );
    }

    public function testAssetWillReturnPathOnTagIsFalse(): void
    {
        $c = Configuration::loadFromArray(['assets' => [ 'salt' => '1234567890']], '', 'simplesaml');
        $moduleTemplate = new Template($c, 'admin:status');
        $tagModule = $moduleTemplate->asset('css/admin.css', 'admin', false);
        $this->assertStringNotContainsString('?tag=', $tagModule);
        $this->assertEquals(
            'http://localhost/simplesaml/module.php/admin/assets/css/admin.css',
            $tagModule,
        );
    }

    public function testAssetDebugTagProduction(): void
    {
        echo "testAssetDebugTagProduction! \n";
        $c = Configuration::loadFromArray(['assets' => [ 'salt' => '1234567890']], '', 'simplesaml');
        $coreTemplate = new Template($c, 'status');
        $tagCore = $coreTemplate->asset('css/stylesheet.css');
        $this->assertStringContainsString('?tag=', $tagCore);
        echo "asset tag $tagCore \n";
    }
}
