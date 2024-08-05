<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\core\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\{Auth, Configuration, Error};
use SimpleSAML\Module\core\Controller;
use SimpleSAML\Module\core\Auth\UserPassBase;
use SimpleSAML\TestUtils\ClearStateTestCase;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * Set of tests for the controllers in the "core" module.
 *
 * For now, this test extends ClearStateTestCase so that it doesn't interfere with other tests. Once every class has
 * been made PSR-7-aware, that won't be necessary any longer.
 *
 * @package SimpleSAML\Test
 */
#[CoversClass(Controller\Login::class)]
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
                'trusted.url.domains' => [],
            ],
            '[ARRAY]',
            'simplesaml',
        );

        Configuration::setPreLoadedConfig($this->config, 'config.php');
    }


    /**
     * Test that we are presented with a regular page if we go to the landing page.
     */
    public function testWelcome(): void
    {
        $c = new Controller\Login($this->config);

        $response = $c->welcome();

        $this->assertInstanceOf(Template::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('core:welcome.twig', $response->getTemplateName());
    }


    /**
     */
    public function testClearDiscoChoicesReturnToDisallowedUrlRejected(): void
    {
        $request = Request::create(
            '/cleardiscochoices',
            'GET',
            ['ReturnTo' => 'https://loeki.tv/asjemenou'],
        );
        $_SERVER['REQUEST_URI']  = 'https://example.com/simplesaml/module.php/core/cleardiscochoices';

        $c = new Controller\Login($this->config);

        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage('URL not allowed: https://loeki.tv/asjemenou');

        $c->cleardiscochoices($request);
    }


    /**
     */
    public function testLoginUserPass(): void
    {
        $request = Request::create(
            '/loginuserpass',
            'GET',
            ['AuthState' => '_abc123'],
        );

        $c = new Controller\Login($this->config);

        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [UserPassBase::AUTHID => 'something'];
            }
        });

        $c->setAuthSource(new class () extends UserPassBase {
            public function __construct()
            {
                // stub
            }

            public function authenticate(array &$state): void
            {
                // stub
            }

            public static function getById(string $authId, ?string $type = null): ?UserPassBase
            {
                return new static();
            }

            protected function login(string $username, string $password): array
            {
                return ['mail' => 'noreply@simplesamlphp.org'];
            }
        });

        $response = $c->loginuserpass($request);

        $this->assertInstanceOf(Template::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('core:loginuserpass.twig', $response->getTemplateName());
    }


    /**
     */
    public function testLoginUserPassOrgNoState(): void
    {
        $request = Request::create(
            '/loginuserpassorg',
            'GET',
            [],
        );

        $c = new Controller\Login($this->config);

        $this->expectException(Error\BadRequest::class);

        $c->loginuserpassorg($request);
    }


    /**
    public function testLoginUserPassOrg(): void
    {
        $request = Request::create(
            '/loginuserpassorg',
            'GET',
            ['AuthState' => 'someState'],
        );

        $c = new Controller\Login($this->config);

        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [UserPassOrgBase::AUTHID => 'something'];
            }
        });

        $c->setAuthSource(new class () extends UserPassOrgBase {
            public function __construct()
            {
                // stub
            }

            public function authenticate(array &$state): void
            {
                // stub
            }

            public static function getById(string $authId, ?string $type = null): ?UserPassOrgBase
            {
                return new static();
            }

            protected function login(string $username, string $password, string $organization): array
            {
                return ['mail' => 'noreply@simplesamlphp.org'];
            }

            protected function getOrganizations(): array
            {
                return ['ssp' => 'SimpleSAMLphp'];
            }
        });

        $response = $c->loginuserpassorg($request);

        $this->assertInstanceOf(Template::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('core:loginuserpass.twig', $response->getTemplateName());
    }
     */
}
