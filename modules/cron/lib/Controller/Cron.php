<?php

declare(strict_types=1);

namespace SimpleSAML\Module\cron\Controller;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use SimpleSAML\Auth;
use SimpleSAML\Auth\AuthenticationFactory;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Session;
use SimpleSAML\Utils;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller class for the cron module.
 *
 * This class serves the different views available in the module.
 *
 * @package SimpleSAML\Module\cron
 */
class Cron
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Configuration */
    protected Configuration $cronconfig;

    /** @var \SimpleSAML\Session */
    protected Session $session;

    /**
     * @var \SimpleSAML\Utils\Auth
     */
    protected $authUtils;


    /**
     * Controller constructor.
     *
     * It initializes the global configuration and auth source configuration for the controllers implemented here.
     *
     * @param \SimpleSAML\Configuration              $config The configuration to use by the controllers.
     * @param \SimpleSAML\Session                    $session The session to use by the controllers.
     *
     * @throws \Exception
     */
    public function __construct(
        Configuration $config,
        Session $session
    ) {
        $this->config = $config;
        $this->cronconfig = Configuration::getConfig('module_cron.php');
        $this->session = $session;
        $this->authUtils = new Utils\Auth();
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
     * Show cron info.
     *
     * @return \SimpleSAML\XHTML\Template
     *   An HTML template or a redirection if we are not authenticated.
     */
    public function info(): Template
    {
        $this->authUtils->requireAdmin();

        $key = $this->cronconfig->getOptionalString('key', 'secret');
        $tags = $this->cronconfig->getOptionalArray('allowed_tags', []);

        $def = [
            'weekly' => "22 0 * * 0",
            'daily' => "02 0 * * *",
            'hourly' => "01 * * * *",
            'default' => "XXXXXXXXXX",
        ];

        $urls = [];
        foreach ($tags as $tag) {
            $urls[] = [
                'exec_href' => Module::getModuleURL('cron') . '/run/' . $tag . '/' . $key,
                'href' => Module::getModuleURL('cron') . '/run/' . $tag . '/' . $key . '/xhtml',
                'tag' => $tag,
                'int' => (array_key_exists($tag, $def) ? $def[$tag] : $def['default']),
            ];
        }

        $t = new Template($this->config, 'cron:croninfo.twig');
        $t->data['urls'] = $urls;
        return $t;
    }


    /**
     * Execute a cronjob.
     *
     * This controller will start a cron operation
     *
     * @param string $tag The tag
     * @param string $key The secret key
     * @param string $output The output format, defaulting to xhtml
     *
     * @return \SimpleSAML\XHTML\Template|\Symfony\Component\HttpFoundation\Response
     *   An HTML template, a redirect or a "runnable" response.
     *
     * @throws \SimpleSAML\Error\Exception
     */
    public function run(string $tag, string $key, string $output = 'xhtml'): Response
    {
        $configKey = $this->cronconfig->getOptionalString('key', 'secret');
        if ($key !== $configKey) {
            Logger::error('Cron - Wrong key provided. Cron will not run.');
            exit;
        }

        $cron = new \SimpleSAML\Module\cron\Cron();
        if (!$cron->isValidTag($tag)) {
            Logger::error('Cron - Illegal tag [' . $tag . '].');
            exit;
        }

        $httpUtils = new Utils\HTTP();
        $url = $httpUtils->getSelfURL();
        $time = date(DATE_RFC822);

        $croninfo = $cron->runTag($tag);
        $summary = $croninfo['summary'];

        if ($this->cronconfig->getOptionalBoolean('sendemail', true) && count($summary) > 0) {
            $mail = new Utils\EMail('SimpleSAMLphp cron report');
            $mail->setData(['url' => $url, 'tag' => $croninfo['tag'], 'summary' => $croninfo['summary']]);
            try {
                $mail->send();
            } catch (PHPMailerException $e) {
                Logger::warning("Unable to send cron report; " . $e->getMessage());
            }
        }

        if ($output === 'xhtml') {
            $t = new Template($this->config, 'cron:croninfo-result.twig');
            $t->data['tag'] = $croninfo['tag'];
            $t->data['time'] = $time;
            $t->data['url'] = $url;
            $t->data['mail_required'] = isset($mail);
            $t->data['mail_exception'] = $e ?? null;
            $t->data['summary'] = $summary;
            return $t;
        }
        return new Response();
    }
}
