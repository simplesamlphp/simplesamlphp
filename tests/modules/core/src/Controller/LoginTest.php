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
use SimpleSAML\Module;

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
                'module.enable' => ['exampleauth' => true, 'testsauthsource' => true ],
            ],
            '[ARRAY]',
            'simplesaml',
        );

        Configuration::setPreLoadedConfig($this->config, 'config.php');


        $v = \SimpleSAML\Module::isModuleEnabled('testsauthsource');
        echo "in setup() have module v $v \n";

/*
        $core_modules = [
            'core' => true,
            'saml' => true,
        ];
        $config = new Configuration([], "config.php");
        $module = 'testsauthsource';
//        $v = \SimpleSAML\Module::isModuleEnabledWithConf($module, $config->getOptionalArray('module.enable', $core_modules));
 */
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
     * FIXME this seems to give some XML on scren and an incomplete result?
     */
    public function xtestClearDiscoChoicesReturnToDisallowedUrlRejected(): void
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

    /**
     * Setup a testing authsource with the additional configuration given
     * Returns a request that can be passed to loginuserpass().
     *
     * @param asConfig extra configuration array to be set inside the authSource stanza
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    private function setupPrivateAuthSource(array $asConfig): Request
    {
        $request = Request::create(
            '/loginuserpass',
            'GET',
            ['AuthState' => '_abc123'],
        );
        $_SERVER['REQUEST_URI']  = 'https://example.com/simplesaml/module.php/testsauthsource/nothing';

        // things really don't like it without a username
        $request->request->set('username', 'x');


        // Get the default authsources and add a specific configuration
        // of testsauthsource:ThrowCustomErrorCode for this test
        $config = [];
        $config['testsauthsource-ThrowCustomErrorCode'] =  array_merge(
            ['testsauthsource:ThrowCustomErrorCode'],
            $asConfig,
        );
        Configuration::setPreLoadedConfig(new Configuration($config, "authsources.php"), "authsources.php");

        // prepare the AuthState that might have been saved
        // in Auth\Source::authenticate()
        $as = [];
        $as[UserPassBase::AUTHID] = 'testsauthsource-ThrowCustomErrorCode';
        $as['\SimpleSAML\Auth\State.id'] = '_abc123';
        // Save the $state-array, so that we can restore it after a redirect
        $id = Auth\State::saveState($as, UserPassBase::STAGEID);

        return $request;
    }


    /**
     * Perform a login with loginuserpass and check that a normal error code related
     * error message is shown on the resulting screen.
     */
    public function testLoginTestAuthSourceNormalError(): void
    {
        // We want a normal error from our auth source
        $asConfig =  ['errorType' => 'NORMAL'];
        $request = $this->setupPrivateAuthSource($asConfig);
        $c = new Controller\Login($this->config);

        // This relies on setupPrivateAuthSource doing a saveState() and will
        // find the Auth\Source because we preloaded an authsources.php config
        $response = $c->loginuserpass($request);

        $this->assertInstanceOf(Template::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('core:loginuserpass.twig', $response->getTemplateName());
        $this->assertStringContainsString(
            "Incorrect username or password",
            $response->getContents(),
            "response page does not contain the expected normal error message",
        );
    }


    /**
     * Perform a login with loginuserpass and check that a custom error code related
     * error message is shown on the resulting screen.
     */
    public function testLoginTestAuthSourceCustomError(): void
    {
        // We want a custom error from our auth source
        $asConfig =  ['errorType' => ''];
        $request = $this->setupPrivateAuthSource($asConfig);
        $c = new Controller\Login($this->config);

        // This relies on setupPrivateAuthSource doing a saveState() and will
        // find the Auth\Source because we preloaded an authsources.php config
        $response = $c->loginuserpass($request);

        $this->assertInstanceOf(Template::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('core:loginuserpass.twig', $response->getTemplateName());
        $this->assertStringContainsString(
            "ThrowCustomErrorCode: title for bind search error",
            $response->getContents(),
            "response page does not contain the expected custom error message",
        );
    }
}
