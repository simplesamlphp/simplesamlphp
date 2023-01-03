<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Controller;

use Exception;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Logger;
use SimpleSAML\Session;
use SimpleSAML\Utils;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function dirname;
use function php_uname;
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
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Session */
    protected Session $session;


    /**
     * Controller constructor.
     *
     * It initializes the global configuration for the controllers implemented here.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use by the controllers.
     * @param \SimpleSAML\Session $config The session to use by the controllers.
     */
    public function __construct(
        Configuration $config,
        Session $session
    ) {
        $this->config = $config;
        $this->session = $session;
    }


    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \SimpleSAML\XHTML\Template|\SimpleSAML\HTTP\RunnableResponse
     */
    public function main(Request $request): Response
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
        } catch (Exception $e) {
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
        return new RunnableResponse([$httpUtils, 'redirectTrustedURL'], [$httpUtils->getSelfURLNoQuery()]);
    }
}
