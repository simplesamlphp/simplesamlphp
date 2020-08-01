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
    protected $config;

    /** @var \SimpleSAML\Configuration */
    protected $cronconfig;

    /** @var \SimpleSAML\Session */
    protected $session;

    /**
     * @var \SimpleSAML\Utils\Auth|string
     * @psalm-var \SimpleSAML\Utils\Auth|class-string
     */
    protected $authUtils = Utils\Auth::class;


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
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \SimpleSAML\XHTML\Template
     *   An HTML template or a redirection if we are not authenticated.
     */
    public function info(Request $request): Template
    {
        $this->authUtils::requireAdmin();

        $key = $this->cronconfig->getValue('key', 'secret');
        $tags = $this->cronconfig->getValue('allowed_tags', []);

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

        $t = new Template($this->config, 'cron:croninfo.twig', 'cron:cron');
        $t->data['urls'] = $urls;
        return $t;
    }


    /**
     * Execute a cronjob.
     *
     * This controller will start a cron operation
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $tag The tag
     * @param string $key The secret key
     * @param string $output The output format, defaulting to xhtml
     *
     * @return \SimpleSAML\XHTML\Template|\Symfony\Component\HttpFoundation\Response
     *   An HTML template, a redirect or a "runnable" response.
     *
     * @throws \SimpleSAML\Error\Exception
     */
    public function run(Request $request, string $tag, string $key, string $output = 'xhtml'): Response
    {
        $configKey = $this->cronconfig->getValue('key', 'secret');
        if ($key !== $configKey) {
            Logger::error('Cron - Wrong key provided. Cron will not run.');
            exit;
        }

        $cron = new \SimpleSAML\Module\cron\Cron();
        if (!$cron->isValidTag($tag)) {
            Logger::error('Cron - Illegal tag [' . $tag . '].');
            exit;
        }

        $url = Utils\HTTP::getSelfURL();
        $time = date(DATE_RFC822);

        $croninfo = $cron->runTag($tag);
        $summary = $croninfo['summary'];

        if ($this->cronconfig->getValue('sendemail', true) && count($summary) > 0) {
            $mail = new Utils\EMail('SimpleSAMLphp cron report');
            $mail->setData(['url' => $url, 'tag' => $croninfo['tag'], 'summary' => $croninfo['summary']]);
            try {
                $mail->send();
            } catch (PHPMailerException $e) {
                Logger::warning("Unable to send cron report; " . $e->getMessage());
            }
        }

        if ($output === 'xhtml') {
            $t = new Template($this->config, 'cron:croninfo-result.twig', 'cron:cron');
            $t->data['tag'] = $croninfo['tag'];
            $t->data['time'] = $time;
            $t->data['url'] = $url;
            $t->data['mail_required'] = isset($mail);
            $t->data['mail_sent'] = !isset($e);
            $t->data['summary'] = $summary;
            return $t;
        }
        return new Response();
    }
}
