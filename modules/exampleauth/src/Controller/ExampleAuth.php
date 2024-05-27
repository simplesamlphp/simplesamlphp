<?php

declare(strict_types=1);

namespace SimpleSAML\Module\exampleauth\Controller;

use SimpleSAML\{Auth, Configuration, Error, Session, Utils};
use SimpleSAML\Module\exampleauth\Auth\Source\External;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;

use function preg_match;
use function urldecode;

/**
 * Controller class for the exampleauth module.
 *
 * This class serves the different views available in the module.
 *
 * @package simplesamlphp/simplesamlphp
 */
class ExampleAuth
{
    /**
     * @var \SimpleSAML\Auth\State|string
     * @psalm-var \SimpleSAML\Auth\State|class-string
     */
    protected $authState = Auth\State::class;


    /**
     * Controller constructor.
     *
     * It initializes the global configuration and session for the controllers implemented here.
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
     * Inject the \SimpleSAML\Auth\State dependency.
     *
     * @param \SimpleSAML\Auth\State $authState
     */
    public function setAuthState(Auth\State $authState): void
    {
        $this->authState = $authState;
    }


    /**
     * Auth testpage.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request The current request.
     *
     * @return \SimpleSAML\XHTML\Template|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function authpage(Request $request): Template|RedirectResponse
    {
        /**
         * This page serves as a dummy login page.
         *
         * Note that we don't actually validate the user in this example. This page
         * just serves to make the example work out of the box.
         */
        $returnTo = $request->get('ReturnTo');
        if ($returnTo === null) {
            throw new Error\Exception('Missing ReturnTo parameter.');
        }

        $httpUtils = new Utils\HTTP();
        $returnTo = $httpUtils->checkURLAllowed($returnTo);

        /**
         * The following piece of code would never be found in a real authentication page. Its
         * purpose in this example is to make this example safer in the case where the
         * administrator of the IdP leaves the exampleauth-module enabled in a production
         * environment.
         *
         * What we do here is to extract the $state-array identifier, and check that it belongs to
         * the exampleauth:External process.
         */
        if (!preg_match('@State=(.*)@', $returnTo, $matches)) {
            throw new Error\Exception('Invalid ReturnTo URL for this example.');
        }

        /**
         * The loadState-function will not return if the second parameter does not
         * match the parameter passed to saveState, so by now we know that we arrived here
         * through the exampleauth:External authentication page.
         */
        $this->authState::loadState(urldecode($matches[1]), 'exampleauth:External');

        // our list of users.
        $users = [
            'student' => [
                'password' => 'student',
                'uid' => 'student',
                'name' => 'Student Name',
                'mail' => 'somestudent@example.org',
                'type' => 'student',
            ],
            'admin' => [
                'password' => 'admin',
                'uid' => 'admin',
                'name' => 'Admin Name',
                'mail' => 'someadmin@example.org',
                'type' => 'employee',
            ],
        ];

        // time to handle login responses; since this is a dummy example, we accept any data
        $badUserPass = false;
        if ($request->getMethod() === 'POST') {
            $username = $request->request->get('username');
            $password = $request->request->get('password');

            if (!isset($users[$username]) || $users[$username]['password'] !== $password) {
                $badUserPass = true;
            } else {
                $user = $users[$username];

                $session = new SymfonySession();
                if (!$session->getId()) {
                    $session->start();
                }

                $session->set('uid', $user['uid']);
                $session->set('name', $user['name']);
                $session->set('mail', $user['mail']);
                $session->set('type', $user['type']);

                return $httpUtils->redirectTrustedURL($returnTo);
            }
        }

        // if we get this far, we need to show the login page to the user
        $t = new Template($this->config, 'exampleauth:authenticate.twig');
        $t->data['badUserPass'] = $badUserPass;
        $t->data['returnTo'] = $returnTo;

        return $t;
    }


    /**
     * Redirect testpage.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request The current request.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function redirecttest(Request $request): Response
    {
        /**
         * Request handler for redirect filter test.
         */
        $stateId = $request->query->get('AuthState');
        if ($stateId === null) {
            throw new Error\BadRequest('Missing required AuthState query parameter.');
        }

        $state = $this->authState::loadState($stateId, 'exampleauth:redirectfilter-test');
        $state['Attributes']['RedirectTest2'] = ['OK'];

        return Auth\ProcessingChain::resumeProcessing($state);
    }


    /**
     * Resume testpage.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request The current request.
     */
    public function resume(Request $request): Response
    {
        /**
         * This page serves as the point where the user's authentication
         * process is resumed after the login page.
         *
         * It simply passes control back to the class.
         */
        return External::resume($request, $this->authState);
    }
}
