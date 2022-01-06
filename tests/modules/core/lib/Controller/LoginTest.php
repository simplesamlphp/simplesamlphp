<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\core\Controller;

use ReflectionClass;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Locale\Localization;
use SimpleSAML\Module\core\Controller;
use SimpleSAML\TestUtils\ClearStateTestCase;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Set of tests for the controllers in the "core" module.
 *
 * For now, this test extends ClearStateTestCase so that it doesn't interfere with other tests. Once every class has
 * been made PSR-7-aware, that won't be necessary any longer.
 *
 * @covers \SimpleSAML\Module\core\Controller\Login
 * @package SimpleSAML\Test
 */
class LoginTest extends ClearStateTestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Configuration[] */
    protected array $loadedConfigs;

    /**
     * Set up for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->config = Configuration::loadFromArray(
            [
                'baseurlpath' => 'https://example.org/simplesaml',
                'module.enable' => ['exampleauth' => true],
            ],
            '[ARRAY]',
            'simplesaml'
        );
        Configuration::setPreLoadedConfig($this->config, 'config.php');
    }

    /**
     * Test that we are presented with a regular page if we go to the landing page.
     */
    public function testWelcome(): void
    {
        $c = new Controller\Login($this->config);
        /** @var \SimpleSAML\XHTML\Template $response */
	$response = $c->welcome();
        $this->assertInstanceOf(Template::class, $response);
        $this->assertEquals('core:welcome.twig', $response->getTemplateName());
    }
}
