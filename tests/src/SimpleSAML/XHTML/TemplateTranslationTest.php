<?php

declare(strict_types=1);

namespace SimpleSAML\Test\XHTML;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Locale\TwigTranslator;
use SimpleSAML\Module;
use SimpleSAML\XHTML\Template;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Finder\Finder;
use Twig\Environment;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 */
#[CoversClass(Template::class)]
class TemplateTranslationTest extends TestCase
{
    public function testCoreCardinalityErrorTemplate(): void
    {
        $c = Configuration::loadFromArray([], '', 'simplesaml');
        $t = new Template($c, 'core:cardinality_error.twig');

        $t->data['cardinalityErrorAttributes'] = [
            'test 1' => [0, 1],
            'test 2' => [1, 2],
        ];

        $getContent = function (): string {
            /** @var \SimpleSAML\XHTML\Template $this */
            return $this->getContents();
        };
        $html = $getContent->call($t);

        $this->assertStringContainsString('got 0 values, want 1', $html);
        $this->assertStringContainsString('got 1 values, want 2', $html);
    }

    public function testCoreLoginUserPassTemplate(): void
    {
        $c = Configuration::loadFromArray([], '', 'simplesaml');
        $t = new Template($c, 'core:loginuserpass.twig');

        $t->data['isProduction'] = false;
        $t->data['errorcode'] = false;
        $t->data['forceUsername'] = false;
        $t->data['username'] = 'h.c oersted';
        $t->data['rememberUsernameEnabled'] = false;
        $t->data['rememberMeEnabled'] = false;
        $t->data['AuthState'] = '_abc123';
        $t->data['formURL'] = Module::getModuleURL('core/loginuserpass');

        $getContent = function (): string {
            /** @var \SimpleSAML\XHTML\Template $this */
            return $this->getContents();
        };
        $html = $getContent->call($t);

        $this->assertStringContainsString('value="h.c oersted"', $html);
    }

    public function testCoreLogoutIframeTemplate(): void
    {
        $c = Configuration::loadFromArray([], '', 'simplesaml');
        $t = new Template($c, 'core:logout-iframe.twig');

        $t->data['auth_state'] = 'logout-test';
        $t->data['type'] = 'test';
        $t->data['terminated_service'] = [
            'name' => [
                'en' => 'ze testing service',
            ],
        ];
        $t->data['remaining_services'] = [
            'test' => [
                'entityID' => 1234,
                'metadata' => [
                    'name' => [
                        'en' => 'ze missing service',
                    ],
                ],
                'status' => 'onhold',
                'logoutURL' => 'https://xxx.yyy/',
            ],
        ];

        $getContent = function (): string {
            /** @var \SimpleSAML\XHTML\Template $this */
            return $this->getContents();
        };
        $html = $getContent->call($t);

        $this->assertStringContainsString('You are now successfully logged out from ze testing service.', $html);
        $this->assertStringContainsString('ze missing service', $html);
    }

    public function testAuthStatusTemplate(): void
    {
        $c = Configuration::loadFromArray([], '', 'simplesaml');
        $t = new Template($c, 'auth_status.twig');

        $t->data['remaining'] = 2;
        $t->data['attributes'] = [];
        $t->data['nameid'] = false;
        $t->data['trackid'] = '';
        $t->data['authData'] = false;

        $getContent = function (): string {
            /** @var \SimpleSAML\XHTML\Template $this */
            return $this->getContents();
        };
        $html = $getContent->call($t);

        $this->assertStringContainsString(
            'Your session is valid for ' . $t->data['remaining'] . ' seconds from now.',
            $html,
        );
    }

    public function testValidateTwigFiles(): void
    {
        $root = dirname(__DIR__, 4);

        // Setup basic twig environment
        $loader = new FilesystemLoader(['templates', 'modules'], $root);
        $twig = new Environment($loader, ['cache' => false]);

        $twigTranslator = new TwigTranslator([Translate::class, 'translateSingularGettext']);
        $twig->addExtension(new TranslationExtension($twigTranslator));
        $twig->addExtension(new IntlExtension());

        // Fake functions
        $twig->addFunction(
            new TwigFunction(
                'asset',
                function () {
                    return '';
                },
            ),
        );
        $twig->addFunction(
            new TwigFunction(
                'moduleURL',
                function () {
                    return '';
                },
            ),
        );

        // Fake filters
        $twig->addFilter(
            new TwigFilter(
                'translateFromArray',
                function () {
                    return '';
                },
                ['needs_context' => true],
            ),
        );
        $twig->addFilter(
            new TwigFilter(
                'entityDisplayName',
                function () {
                    return '';
                },
            ),
        );

        $files = Finder::create()
            ->name('*.twig')
            ->in(
                [
                    $root . '/templates',
                    $root . '/modules',
                ],
            );

        foreach ($files as $file) {
            $twig->load($file->getRelativePathname());
        }

        $this->assertTrue(true, 'All *.twig files parsed load test.');
    }
}
