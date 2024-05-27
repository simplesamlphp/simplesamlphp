<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\admin\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Module\admin\Controller;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;

/**
 * Set of tests for the controllers in the "admin" module.
 *
 * @package SimpleSAML\Test
 */
#[CoversClass(Controller\Sandbox::class)]
class SandboxTest extends TestCase
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
                'module.enable' => ['admin' => true],
            ],
            '[ARRAY]',
            'simplesaml',
        );

        $this->session = Session::getSessionFromRequest();
    }


    /**
     */
    public function testSandbox(): void
    {
        $c = new Controller\Sandbox($this->config, $this->session);
        $response = $c->main();

        $this->assertInstanceOf(Template::class, $response);
        $this->assertTrue($response->isSuccessful());
    }
}
