<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Controller;

use Exception as BuiltinException;
use SimpleSAML\{Configuration, Error, Logger, Session, Utils};
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};

use function dirname;
use function filter_var;
use function php_uname;
use function preg_match;
use function var_export;

/**
 * Controller class for the core module.
 *
 * This class serves the different views available in the module.
 *
 * @package SimpleSAML\Module\core
 */
class ErrorReport
{
    /**
     * Controller constructor.
     *
     * It initializes the global configuration for the controllers implemented here.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use by the controllers.
     * @param \SimpleSAML\Session $session The session to use by the controllers.
     */
    public function __construct(
        protected Configuration $config,
        protected Session $session,
    ) {
    }


    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \SimpleSAML\XHTML\Template|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function main(Request $request): RedirectResponse|Template
    {
        // this page will redirect to itself after processing a POST request and sending the email
        if ($request->server->get('REQUEST_METHOD') !== 'POST') {
            // the message has been sent. Show error report page

            return new Template($this->config, 'core:errorreport.twig');
        }

        $reportId = $request->request->get('reportId');
        $email = $request->request->get('email');
        $text = $request->request->get('text');

        if (!preg_match('/^[0-9a-f]{8}$/', $reportId)) {
            throw new Error\Exception('Invalid reportID');
        }

        try {
            $data = $this->session->getData('core:errorreport', $reportId);
        } catch (BuiltinException $e) {
            $data = null;
            Logger::error('Error loading error report data: ' . var_export($e->getMessage(), true));
        }

        if ($data === null) {
            $data = [
                'exceptionMsg'   => 'not set',
                'exceptionTrace' => 'not set',
                'trackId'        => 'not set',
                'url'            => 'not set',
                'referer'        => 'not set',
            ];

            if (isset($this->session)) {
                $data['trackId'] = $this->session->getTrackID();
            }
        }

        $data['reportId'] = $reportId;
        $data['version'] = $this->config->getVersion();
        $data['hostname'] = php_uname('n');
        $data['directory'] = dirname(__FILE__, 2);

        if ($this->config->getOptionalBoolean('errorreporting', true)) {
            $mail = new Utils\EMail('SimpleSAMLphp error report from ' . $email);
            $mail->setData($data);
            if (filter_var($email, FILTER_VALIDATE_EMAIL, FILTER_REQUIRE_SCALAR)) {
                $mail->addReplyTo($email);
            }
            $mail->setText($text);
            $mail->send();
            Logger::error('Report with id ' . $reportId . ' sent');
        }

        // redirect the user back to this page to clear the POST request
        $httpUtils = new Utils\HTTP();
        return $httpUtils->redirectTrustedURL($httpUtils->getSelfURLNoQuery());
    }
}
