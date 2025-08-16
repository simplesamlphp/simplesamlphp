<?php

declare(strict_types=1);

namespace SimpleSAML\Module\exampleauth\Controller;

use Exception as BuiltinException;
use SimpleSAML\{Auth, Configuration, Error, Module, Utils};
use SimpleSAML\Module\exampleauth\Auth\Source\UserClick;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\{Request, Response};

use function trim;

/**
 * Controller class for the login module.
 *
 * This class serves the different views available in the module.
 *
 * @package SimpleSAML\Module\login
 */
class ProfileAuth
{
    /**
     * @var \SimpleSAML\Auth\Source|string
     * @psalm-var \SimpleSAML\Auth\Source|class-string
     */
    protected $authSource = Auth\Source::class;

    /**
     * @var \SimpleSAML\Auth\State|string
     * @psalm-var \SimpleSAML\Auth\State|class-string
     */
    protected $authState = Auth\State::class;


    /**
     * Controller constructor.
     *
     * It initializes the global configuration for the controllers implemented here.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use by the controllers.
     *
     * @throws \Exception
     */
    public function __construct(
        protected Configuration $config,
    ) {
    }


    /**
     * This page shows a list of users, and passes information from it
     * to the \SimpleSAML\Module\exampleauth\Auth\UserClick class, which is a class for
     * user authentication.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function login(Request $request): Response
    {
        // Retrieve the authentication state
        if (!$request->query->has('AuthState')) {
            throw new Error\BadRequest('Missing AuthState parameter.');
        }

        $authStateId = $request->query->get('AuthState');
        $this->authState::validateStateId($authStateId);

        $state = $this->authState::loadState($authStateId, UserClick::STAGEID);

        /** @var \SimpleSAML\Module\exampleauth\Auth\UserClick|null $source */
        $source = $this->authSource::getById($state[UserClick::AUTHID]);
        if ($source === null) {
            throw new BuiltinException(
                'Could not find authentication source with id ' . $state[UserClick::AUTHID],
            );
        }

        return $this->handleLogin($request, $source, $state);
    }


    /**
     * This method handles the generic part for both login and loginuserpassorg
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \SimpleSAML\Module\exampleauth\Auth\UserClick $source
     * @param array $state
     */
    private function handleLogin(Request $request, UserClick $source, array $state): Response
    {
        $authStateId = $request->query->get('AuthState');
        $this->authState::validateStateId($authStateId);

        $organizations = $organization = null;

        $id = $this->getIDFromRequest($request, $source, $state);

        $errorCode = null;
        $errorParams = null;

        if (isset($state['error'])) {
            $errorCode = $state['error']['code'];
            $errorParams = $state['error']['params'];
        }

        if ($id !== '') {
            try {
                UserClick::handleLogin($authStateId, (int)$id);
            } catch (Error\Error $e) {
                // Login failed. Extract error code and parameters, to display the error
                $errorCode = $e->getErrorCode();
                $errorParams = $e->getParameters();
                $state['error'] = [
                    'code' => $errorCode,
                    'params' => $errorParams,
                ];
                $authStateId = Auth\State::saveState($state, $source::STAGEID);
            }

            if (isset($state['error'])) {
                unset($state['error']);
            }
        }

        $t = new Template($this->config, 'exampleauth:userclick.twig');

        $t->data['users'] = $source->users;
        $t->data['formURL'] = Module::getModuleURL('exampleauth/profileauth', ['AuthState' => $authStateId]);

        $t->data['errorcode'] = $errorCode;
        $t->data['errorcodes'] = Error\ErrorCodes::getAllErrorCodeMessages();
        $t->data['errorparams'] = $errorParams;

        if (isset($state['SPMetadata'])) {
            $t->data['SPMetadata'] = $state['SPMetadata'];
        } else {
            $t->data['SPMetadata'] = null;
        }

        return $t;
    }


    /**
     * Retrieve the username from the request, a cookie or the state
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \SimpleSAML\Auth\Source $source
     * @param array $state
     * @return string
     */
    private function getIDFromRequest(Request $request, Auth\Source $source, array $state): string
    {
        $id = '';

        if ($request->query->has('id')) {
            $id = trim($request->query->get('id'));
        } elseif (isset($state['exampleauth:id'])) {
            $id = strval($state['exampleauth:id']);
        }

        return $id;
    }
}
