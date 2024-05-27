<?php

declare(strict_types=1);

namespace SimpleSAML\Module\admin\Controller;

use SimpleSAML\{Configuration, Module, Session, Utils};
use SimpleSAML\Locale\Translate;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\{Request, Response, StreamedResponse};

use function curl_close;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function explode;
use function function_exists;
use function json_decode;
use function ltrim;
use function phpversion;
use function version_compare;

/**
 * Controller class for the admin module.
 *
 * This class serves the configuration views available in the module.
 *
 * @package SimpleSAML\Module\admin
 */
class Config
{
    public const LATEST_VERSION_STATE_KEY = 'core:latest_simplesamlphp_version';

    public const RELEASES_API = 'https://api.github.com/repos/simplesamlphp/simplesamlphp/releases/latest';

    /** @var \SimpleSAML\Utils\Auth */
    protected Utils\Auth $authUtils;

    /** @var \SimpleSAML\Utils\HTTP */
    protected Utils\HTTP $httpUtils;

    /** @var \SimpleSAML\Module\admin\Controller\Menu */
    protected Menu $menu;


    /**
     * ConfigController constructor.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use.
     * @param \SimpleSAML\Session $session The current user session.
     */
    public function __construct(
        protected Configuration $config,
        protected Session $session,
    ) {
        $this->menu = new Menu();
        $this->authUtils = new Utils\Auth();
        $this->httpUtils = new Utils\HTTP();
    }


    /**
     * Inject the \SimpleSAML\Utils\Auth dependency.
     *
     * @param \SimpleSAML\Utils\Auth $authUtils
     */
    public function setAuthUtils(Utils\Auth $authUtils): void
    {
        $this->authUtils = $authUtils;
    }


    /**
     * Display basic diagnostic information on hostname, port and protocol.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request The current request.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function diagnostics(Request $request): Response
    {
        $response = $this->authUtils->requireAdmin();
        if ($response instanceof Response) {
            return $response;
        }

        $t = new Template($this->config, 'admin:diagnostics.twig');
        $t->data = [
            'remaining' => $this->session->getAuthData('admin', 'Expire') - time(),
            'logouturl' => $this->authUtils->getAdminLogoutURL(),
            'items' => [
                'HTTP_HOST' => [$request->getHost()],
                'HTTPS' => $request->isSecure() ? ['on'] : [],
                'SERVER_PROTOCOL' => [$request->getProtocolVersion()],
                'getBaseURL()' => [$this->httpUtils->getBaseURL()],
                'getSelfHost()' => [$this->httpUtils->getSelfHost()],
                'getSelfHostWithNonStandardPort()' => [$this->httpUtils->getSelfHostWithNonStandardPort()],
                'getSelfURLHost()' => [$this->httpUtils->getSelfURLHost()],
                'getSelfURLNoQuery()' => [$this->httpUtils->getSelfURLNoQuery()],
                'getSelfHostWithPath()' => [$this->httpUtils->getSelfHostWithPath()],
                'getSelfURL()' => [$this->httpUtils->getSelfURL()],
            ],
        ];

        $this->menu->addOption('logout', $t->data['logouturl'], Translate::noop('Log out'));
        return $this->menu->insert($t);
    }


    /**
     * Display the main admin page.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request The current request.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function main(/** @scrutinizer ignore-unused */ Request $request): Response
    {
        $response = $this->authUtils->requireAdmin();
        if ($response instanceof Response) {
            return $response;
        }

        $t = new Template($this->config, 'admin:config.twig');
        $t->data = [
            'warnings' => $this->getWarnings(),
            'directory' => $this->config->getBaseDir(),
            'version' => $this->config->getVersion(),
            'links' => [
                [
                    'href' => Module::getModuleURL('admin/diagnostics'),
                    'text' => Translate::noop('Diagnostics on hostname, port and protocol'),
                ],
                [
                    'href' => Module::getModuleURL('admin/phpinfo'),
                    'text' => Translate::noop('Information on your PHP installation'),
                ],
            ],
            'enablematrix' => [
                'saml20idp' => $this->config->getOptionalBoolean('enable.saml20-idp', false),
            ],
            'funcmatrix' => $this->getPrerequisiteChecks(),
            'logouturl' => $this->authUtils->getAdminLogoutURL(),
            'modulelist' => $this->getModuleList(),
        ];

        Module::callHooks('configpage', $t);
        $this->menu->addOption('logout', $this->authUtils->getAdminLogoutURL(), Translate::noop('Log out'));
        return $this->menu->insert($t);
    }


    /**
     * @return array
     */
    protected function getModuleList(): array
    {
        $modules = Module::getModules();
        $modulestates = [];
        foreach ($modules as $module) {
            $modulestates[$module] = Module::isModuleEnabled($module);
        }
        ksort($modulestates);
        return $modulestates;
    }


    /**
     * Display the output of phpinfo().
     *
     * @param \Symfony\Component\HttpFoundation\Request $request The current request.
     *
     * @return \Symfony\Component\HttpFoundation\Response The output of phpinfo()
     */
    public function phpinfo(/** @scrutinizer ignore-unused */ Request $request): Response
    {
        $response = $this->authUtils->requireAdmin();
        if ($response instanceof Response) {
            return $response;
        }

        $response = new StreamedResponse('phpinfo');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; frame-ancestors 'self';",
        );

        return $response;
    }

    /**
     * Perform a list of checks on the current installation, and return the results as an array.
     *
     * The elements in the array returned are also arrays with the following keys:
     *
     *   - required: Whether this prerequisite is mandatory or not. One of "required" or "optional".
     *   - descr: A translatable text that describes the prerequisite. If the text uses parameters, the value must be an
     *     array where the first value is the text to translate, and the second is a hashed array containing the
     *     parameters needed to properly translate the text.
     *   - enabled: True if the prerequisite is met, false otherwise.
     *
     * @return array
     */
    protected function getPrerequisiteChecks(): array
    {
        $matrix = [
            [
                'required' => 'required',
                'descr' => [
                    Translate::noop('PHP %minimum% or newer is needed. You are running: %current%'),
                    [
                        '%minimum%' => '8.1',
                        '%current%' => explode('-', phpversion())[0],
                    ],
                ],
                'enabled' => version_compare(phpversion(), '8.1', '>='),
            ],
        ];
        $store = $this->config->getOptionalString('store.type', null);
        $checkforupdates = $this->config->getOptionalBoolean('admin.checkforupdates', true);

        // check dependencies used via normal functions
        $functions = [
            'time' => [
                'required' => 'required',
                'descr' => [
                    'required' => Translate::noop('Date/Time Extension'),
                ],
            ],
            'hash' => [
                'required' => 'required',
                'descr' => [
                    'required' => Translate::noop('Hashing function'),
                ],
            ],
            'gzinflate' => [
                'required' => 'required',
                'descr' => [
                    'required' => Translate::noop('ZLib'),
                ],
            ],
            'openssl_sign' => [
                'required' => 'required',
                'descr' => [
                    'required' => Translate::noop('OpenSSL'),
                ],
            ],
            'dom_import_simplexml' => [
                'required' => 'required',
                'descr' => [
                    'required' => Translate::noop('XML DOM'),
                ],
            ],
            'preg_match' => [
                'required' => 'required',
                'descr' => [
                    'required' => Translate::noop('Regular expression support'),
                ],
            ],
            'intl_get_error_code' => [
                'required' => 'optional',
                'descr' => [
                    'optional' => Translate::noop('PHP intl extension'),
                ],
            ],
            'json_decode' => [
                'required' => 'required',
                'descr' => [
                    'required' => Translate::noop('JSON support'),
                ],
            ],
            'class_implements' => [
                'required' => 'required',
                'descr' => [
                    'required' => Translate::noop('Standard PHP library (SPL)'),
                ],
            ],
            'mb_strlen' => [
                'required' => 'required',
                'descr' => [
                    'required' => Translate::noop('Multibyte String extension'),
                ],
            ],
            'curl_init' => [
                'required' => ($checkforupdates === true) ? 'required' : 'optional',
                'descr' => [
                    'optional' => Translate::noop(
                        'cURL (might be required by some modules)',
                    ),
                    'required' => Translate::noop(
                        'cURL (required if automatic version checks are used, also by some modules)',
                    ),
                ],
            ],
            'session_start' => [
                'required' => $store === 'phpsession' ? 'required' : 'optional',
                'descr' => [
                    'optional' => Translate::noop('Session extension (required if PHP sessions are used)'),
                    'required' => Translate::noop('Session extension'),
                ],
            ],
            'pdo_drivers' => [
                'required' => $store === 'sql' ? 'required' : 'optional',
                'descr' => [
                    'optional' => Translate::noop('PDO Extension (required if a database backend is used)'),
                    'required' => Translate::noop('PDO extension'),
                ],
            ],
            'ldap_bind' => [
                'required' => Module::isModuleEnabled('ldap') ? 'required' : 'optional',
                'descr' => [
                    'optional' => Translate::noop('LDAP extension (required if an LDAP backend is used)'),
                    'required' => Translate::noop('LDAP extension'),
                ],
            ],
        ];

        foreach ($functions as $function => $description) {
            $matrix[] = [
                'required' => $description['required'],
                'descr' => $description['descr'][$description['required']],
                'enabled' => function_exists($function),
            ];
        }

        // check object-oriented external libraries and extensions
        $libs = [
            [
                'classes' => ['\Predis\Client'],
                'required' => $store === 'redis' ? 'required' : 'optional',
                'descr' => [
                    'optional' => Translate::noop('predis/predis (required if the redis data store is used)'),
                    'required' => Translate::noop('predis/predis library'),
                ],
            ],
            [
                'classes' => ['\Memcache', '\Memcached'],
                'required' => $store === 'memcache' ? 'required' : 'optional',
                'descr' => [
                    'optional' => Translate::noop(
                        'Memcache or Memcached extension (required if the memcache backend is used)',
                    ),
                    'required' => Translate::noop('Memcache or Memcached extension'),
                ],
            ],
        ];

        foreach ($libs as $lib) {
            $enabled = false;
            foreach ($lib['classes'] as $class) {
                /** @psalm-suppress InvalidOperand - See https://github.com/vimeo/psalm/issues/1340 */
                $enabled |= class_exists($class);
            }
            $matrix[] = [
                'required' => $lib['required'],
                'descr' => $lib['descr'][$lib['required']],
                'enabled' => $enabled,
            ];
        }

        // perform some basic configuration checks
        $technicalcontact = $this->config->getOptionalString('technicalcontact_email', 'na@example.org');
        $matrix[] = [
            'required' => 'optional',
            'descr' => Translate::noop('The <code>technicalcontact_email</code> configuration option should be set'),
            'enabled' => $technicalcontact !== 'na@example.org',
        ];

        $matrix[] = [
            'required' => 'required',
            'descr' => Translate::noop('The auth.adminpassword configuration option must be set'),
            'enabled' => $this->config->getOptionalString('auth.adminpassword', '123') !== '123',
        ];


        // Add module specific checks via the sanitycheck hook that a module can provide.
        $hookinfo = [ 'info' => [], 'errors' => [] ];
        Module::callHooks('sanitycheck', $hookinfo);
        foreach (['info', 'errors'] as $resulttype) {
            foreach ($hookinfo[$resulttype] as $result) {
                $matrix[] = [
                    'required' => 'required',
                    'descr' => $result,
                    'enabled' => $resulttype === 'info',
                ];
            }
        }

        return $matrix;
    }


    /**
     * Compile a list of warnings about the current deployment.
     *
     * The returned array can contain either strings that can be translated directly, or arrays. If an element is an
     * array, the first value in that array is a string that can be translated, and the second value will be a hashed
     * array that contains the substitutions that must be applied to the translation, with its corresponding value. This
     * can be used in twig like this, assuming an element called "e":
     *
     *     {{ e[0]|trans(e[1])|raw }}
     *
     * @return array
     */
    protected function getWarnings(): array
    {
        $warnings = [];

        // make sure we're using HTTPS
        if (!$this->httpUtils->isHTTPS()) {
            $warnings[] = Translate::noop(
                '<strong>You are not using HTTPS</strong> to protect communications with your users. HTTP works fine ' .
                'for testing purposes, but in a production environment you should use HTTPS. <a ' .
                'href="https://simplesamlphp.org/docs/stable/simplesamlphp-maintenance">Read more about the ' .
                'maintenance of SimpleSAMLphp</a>.',
            );
        }

        // make sure we have a secret salt set
        $secretSalt = $this->config->getString('secretsalt');
        if ($secretSalt === 'defaultsecretsalt') {
            $warnings[] = Translate::noop(
                '<strong>The configuration uses the default secret salt</strong>. Make sure to modify the <code>' .
                'secretsalt</code> option in the SimpleSAMLphp configuration in production environments. <a ' .
                'href="https://simplesamlphp.org/docs/stable/simplesamlphp-install">Read more about the ' .
                'maintenance of SimpleSAMLphp</a>.',
            );
        } elseif (str_contains($secretSalt, '%')) {
            $warnings[] = Translate::noop(
                'The "secretsalt" configuration option may not contain a `%` sign.',
            );
        }

        /*
         * Check for updates. Store the remote result in the session so we don't need to fetch it on every access to
         * this page.
         */
        $checkforupdates = $this->config->getOptionalBoolean('admin.checkforupdates', true);
        if (($checkforupdates === true) && $this->config->getVersion() !== 'dev-master') {
            if (!function_exists('curl_init')) {
                $warnings[] = Translate::noop(
                    'The cURL PHP extension is missing. Cannot check for SimpleSAMLphp updates.',
                );
            } else {
                $latest = $this->session->getData(self::LATEST_VERSION_STATE_KEY, "version");

                if (!$latest) {
                    $ch = curl_init(self::RELEASES_API);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'SimpleSAMLphp');
                    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
                    curl_setopt($ch, CURLOPT_PROXY, $this->config->getOptionalString('proxy', null));
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->config->getOptionalValue('proxy.auth', null));
                    $response = curl_exec($ch);

                    if (curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 200) {
                        /** @psalm-var string $response */
                        $latest = json_decode($response, true);
                        $this->session->setData(self::LATEST_VERSION_STATE_KEY, 'version', $latest);
                    }
                    curl_close($ch);
                }

                if ($latest && version_compare($this->config->getVersion(), ltrim($latest['tag_name'], 'v'), 'lt')) {
                    $warnings[] = [
                        Translate::noop(
                            'You are running an outdated version of SimpleSAMLphp. Please update to <a href="' .
                            '%latest%">the latest version</a> as soon as possible.',
                        ),
                        [
                            '%latest%' => $latest['html_url'],
                        ],
                    ];
                }
            }
        }

        return $warnings;
    }
}
