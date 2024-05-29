<?php

declare(strict_types=1);

namespace SimpleSAML\Module\cron\Controller;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use SimpleSAML\{Auth, Configuration, Error, Logger, Module, Session, Utils};
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};

use function array_key_exists;
use function count;
use function date;
use function sprintf;

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
    protected Configuration $cronconfig;

    /** @var \SimpleSAML\Utils\Auth */
    protected Utils\Auth $authUtils;


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
        protected Configuration $config,
        protected Session $session,
    ) {
        $this->cronconfig = Configuration::getConfig('module_cron.php');
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
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response|\SimpleSAML\XHTML\Template
     *   An HTML template or a redirection if we are not authenticated.
     */
    public function info(/** @scrutinizer ignore-unused */Request $request): Response|Template
    {
        $response = $this->authUtils->requireAdmin();
        if ($response instanceof Response) {
            return $response;
        }

        $key = $this->cronconfig->getString('key');
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
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $tag The tag
     * @param string $key The secret key
     * @param string $output The output format, defaulting to xhtml
     *
     * @return \SimpleSAML\XHTML\Template An HTML template.
     *
     * @throws \SimpleSAML\Error\Exception
     */
    public function run(
        /** @scrutinizer ignore-unused */Request $request,
        string $tag,
        #[\SensitiveParameter]
        string $key,
        string $output = 'xhtml',
    ): Template {
        $configKey = $this->cronconfig->getString('key');

        if ($key === 'secret' || $key === 'RANDOM_KEY') {
            // TODO: Replace with condition in route when Symfony 6.1 is available
            // Possible malicious attempt to run cron tasks with default secret
            throw new Error\NotFound();
        } elseif ($configKey === 'secret' || $configKey === 'RANDOM_KEY') {
            throw new Error\ConfigurationError("Cron: no proper key has been configured.");
        } elseif ($key !== $configKey) {
            throw new Error\Exception('Cron: Wrong key provided. Cron will not run.');
        }

        $cron = new Module\cron\Cron();
        if (!$cron->isValidTag($tag)) {
            throw new Error\Exception(sprintf('Cron: Illegal tag [%s].', $tag));
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

        throw new Error\Exception('Unknown output type.');
    }
}
