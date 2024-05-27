<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\core\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\{Configuration, Logger, Session};
use SimpleSAML\Module\core\Controller;
use SimpleSAML\TestUtils\ArrayLogger;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

use function date;
use function sprintf;
use function time;

/**
 * Set of tests for the controllers in the "core" module.
 *
 * @package SimpleSAML\Test
 */
#[CoversClass(Controller\Exception::class)]
class ExceptionTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected static Configuration $config;

    /** @var \SimpleSAML\Session */
    protected static Session $session;


    /**
     * Set up before running the test-suite.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$config = Configuration::loadFromArray(
            [
                'logging.handler' => ArrayLogger::class,
                'errorreporting' => false,
                'module.enable' => ['core' => true],
            ],
            '[ARRAY]',
            'simplesaml',
        );

        Configuration::setPreLoadedConfig(self::$config, 'config.php');

        self::$session = Session::getSessionFromRequest();
    }


    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        Logger::clearCapturedLog();
    }


    /**
     * @param string $code
     * Test that we are presented with an 'error was reported' page
     */
    #[DataProvider('codeProvider')]
    public function testErrorURL(string $code, string $ts, string $rp, string $tid, string $ctx): void
    {
        $request = Request::create(
            sprintf('/error/%s?ts=%s&rp=%s&tid=%s&ctx=%s', $code, $ts, $rp, $tid, $ctx),
            'GET',
        );

        $c = new Controller\Exception(self::$config, self::$session);

        Logger::setCaptureLog();
        $response = $c->error($request, $code);
        Logger::setCaptureLog(false);

        $log = Logger::getCapturedLog();
        self::assertCount(7, $log);

        self::assertStringContainsString(
            "A Service Provider reported the following error during authentication:  "
            . sprintf(
                "Code: %s; Timestamp: %s; Relying party: %s; Transaction ID: %s; Context: [%s]",
                $code,
                ($ts === 'ERRORURL_TS') ? 'null' : date(DATE_W3C, intval($ts)),
                ($rp === 'ERRORURL_RP') ? 'null' : $rp,
                ($tid === 'ERRORURL_TID') ? 'null' : $tid,
                ($ctx === 'ERRORURL_CTX') ? '' : urldecode($ctx),
            ),
            $log[0],
        );

        $this->assertInstanceOf(Template::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('core:error.twig', $response->getTemplateName());
    }


    /**
     */
    public static function codeProvider(): array
    {
        $codes = [
            'ERRORURL_CODE',
            'IDENTIFICATION_FAILURE',
            'AUTHENTICATION_FAILURE',
            'AUTHORIZATION_FAILURE',
            'OTHER_ERROR',
        ];

        $tss = ['ERRORURL_TS', strval(time())];
        $rps = ['ERRORURL_RP', 'phpunit'];
        $tids = ['ERRORURL_TID', '123456'];
        $ctxs = ['ERRORURL_CTX', urlencode("phpunit did it's job")];

        $matrix = [];
        foreach ($codes as $code) {
            foreach ($tss as $ts) {
                foreach ($rps as $rp) {
                    foreach ($tids as $tid) {
                        foreach ($ctxs as $ctx) {
                            $matrix[] = [$code, $ts, $rp, $tid, $ctx];
                        }
                    }
                }
            }
        }

        return $matrix;
    }


    /**
     * Test that an exception was thrown when an invalid error code was used.
     */
    public function testErrorURLInvalidCode(): void
    {
        $request = Request::create(
            '/error/doesNotExist',
        );

        $c = new Controller\Exception(self::$config, self::$session);

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage(
            'Expected one of: "IDENTIFICATION_FAILURE", "AUTHENTICATION_FAILURE",'
            . ' "AUTHORIZATION_FAILURE", "OTHER_ERROR". Got: "doesNotExist"',
        );

        $c->error($request, 'doesNotExist');
    }
}
