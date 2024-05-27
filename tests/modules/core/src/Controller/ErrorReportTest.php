<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\core\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module\core\Controller;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * Set of tests for the controllers in the "core" module.
 *
 * @package SimpleSAML\Test
 */
#[CoversClass(Controller\ErrorReport::class)]
class ErrorReportTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Session */
    protected Session $session;


    /**
     * Set up for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = Configuration::loadFromArray(
            [
                'errorreporting' => false,
                'module.enable' => ['core' => true],
            ],
            '[ARRAY]',
            'simplesaml',
        );

        Configuration::setPreLoadedConfig($this->config, 'config.php');

        $this->session = Session::getSessionFromRequest();
    }


    /**
     * Test that we are presented with an 'error was reported' page
     */
    public function testErrorReportSent(): void
    {
        $request = Request::create(
            '/errorReport',
            'GET',
        );

        $c = new Controller\ErrorReport($this->config, $this->session);

        $response = $c->main($request);

        $this->assertInstanceOf(Template::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('core:errorreport.twig', $response->getTemplateName());
    }


    /**
     * Test that we are presented with an 'error was reported' page
     */
    public function testErrorReportIncorrectReportID(): void
    {
        $request = Request::create(
            '/errorReport',
            'POST',
            ['reportId' => 'abc123'],
        );

        $c = new Controller\ErrorReport($this->config, $this->session);

        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage('Invalid reportID');

        $c->main($request);
    }


    /**
     * Test that we are presented with an 'error was reported' page
     */
    public function testErrorReport(): void
    {
        $request = Request::create(
            '/errorReport',
            'POST',
            [
                'reportId' => 'abcd1234',
                'email' => 'phpunit@example.org',
                'text' => 'phpunit',
            ],
        );

        $c = new Controller\ErrorReport($this->config, $this->session);

        $response = $c->main($request);

        $this->assertInstanceOf(RunnableResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
    }
}
