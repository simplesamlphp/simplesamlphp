<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Controller;

use Exception;
use SimpleSAML\{Auth, Configuration, Error, Logger, Module, Session, Utils};
use SimpleSAML\Assert\Assert;
use SimpleSAML\XHTML\Template;
use SimpleSAML\XMLSecurity\Alg\Encryption\AES;
use SimpleSAML\XMLSecurity\Constants as C;
use SimpleSAML\XMLSecurity\Key\SymmetricKey;
use Symfony\Component\HttpFoundation\{Request, Response};

use function base64_decode;
use function explode;

/**
 * Controller class for the core module.
 *
 * This class serves the different views available in the module.
 *
 * @package SimpleSAML\Module\core
 */
class Redirection
{
    /**
     * Controller constructor.
     *
     * It initializes the global configuration and auth source configuration for the controllers implemented here.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use by the controllers.
     * @param \SimpleSAML\Session $session The session to use by the controllers.
     *
     * @throws \Exception
     */
    public function __construct(
        protected Configuration $config,
        protected Session $session,
    ) {
    }


    /**
     * This controller provides a way to create a redirect to a POST request
     *
     * @param Request $request The request that lead to this login operation.
     * @throws \SimpleSAML\Error\BadRequest
     * @return \SimpleSAML\XHTML\Template|\Symfony\Component\HttpFoundation\RedirectResponse
     *   An HTML template or a redirection if we are not authenticated.
     */
    public function postredirect(Request $request): Response
    {
        $redirId = $request->query->get('RedirId', false);
        $redirInfo = $request->query->get('RedirInfo', false);
        if ($redirId !== false) {
            $postId = $redirId;
        } elseif ($redirInfo !== false) {
            $encData = base64_decode($redirInfo, true);

            if (empty($encData)) {
                throw new Error\BadRequest('Invalid RedirInfo data.');
            }

            $key = new SymmetricKey((new Utils\Config())->getSecretSalt());
            $decryptor = new AES($key, C::BLOCK_ENC_AES256_GCM);

            list($sessionId, $postId) = explode(':', $decryptor->decrypt($encData));

            if (empty($sessionId) || empty($postId)) {
                throw new Error\BadRequest('Invalid session info data.');
            }
        } else {
            throw new Error\BadRequest('Missing redirection info parameter.');
        }

        $session = $this->session;
        if ($session === null) {
            throw new Exception('Unable to load session.');
        }

        $postData = $session->getData('core_postdatalink', $postId);

        if ($postData === null) {
            // The post data is missing, probably because it timed out
            throw new Exception('The POST data we should restore was lost.');
        }

        $session->deleteData('core_postdatalink', $postId);

        Assert::isArray($postData);
        Assert::keyExists($postData, 'url');
        Assert::keyExists($postData, 'post');

        $httpUtils = new Utils\HTTP();
        if (!$httpUtils->isValidURL($postData['url'])) {
            throw new Error\Exception('Invalid destination URL.');
        }

        $t = new Template($this->config, 'post.twig');
        $t->data['destination'] = $postData['url'];
        $t->data['post'] = $postData['post'];
        return $t;
    }
}
