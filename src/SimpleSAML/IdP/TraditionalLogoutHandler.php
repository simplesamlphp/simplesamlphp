<?php

declare(strict_types=1);

namespace SimpleSAML\IdP;

use Exception;
use SimpleSAML\{Auth, Configuration, Error, IdP, Logger, Utils};
use Symfony\Component\HttpFoundation\Response;

use function call_user_func;
use function var_export;

/**
 * Class that handles traditional logout.
 *
 * @package SimpleSAMLphp
 */

class TraditionalLogoutHandler implements LogoutHandlerInterface
{
    /**
     * TraditionalLogout constructor.
     *
     * @param \SimpleSAML\IdP $idp The IdP to log out from.
     */
    public function __construct(
        private IdP $idp,
    ) {
    }


    /**
     * Picks the next SP and issues a logout request.
     *
     * This function never returns.
     *
     * @param array &$state The logout state.
     */
    private function logoutNextSP(array &$state): Response
    {
        $association = array_pop($state['core:LogoutTraditional:Remaining']);
        if ($association === null) {
            return $this->idp->finishLogout($state);
        }

        $relayState = Auth\State::saveState($state, 'core:LogoutTraditional', true);

        $id = $association['id'];
        Logger::info('Logging out of ' . var_export($id, true) . '.');

        try {
            $idp = IdP::getByState(Configuration::getInstance(), $association);
            $url = call_user_func([$association['Handler'], 'getLogoutURL'], $idp, $association, $relayState);
            $httpUtils = new Utils\HTTP();
            return $httpUtils->redirectTrustedURL($url);
        } catch (Exception $e) {
            Logger::warning('Unable to initialize logout to ' . var_export($id, true) . '.');
            $this->idp->terminateAssociation($id);
            $state['core:Failed'] = true;

            // Try the next SP
            return $this->logoutNextSP($state);
        }
    }


    /**
     * Start the logout operation.
     *
     * @param array  &$state The logout state.
     * @param string|null $assocId The association that started the logout.
     */
    public function startLogout(array &$state, /** @scrutinizer ignore-unused */ ?string $assocId): Response
    {
        $state['core:LogoutTraditional:Remaining'] = $this->idp->getAssociations();

        return $this->logoutNextSP($state);
    }


    /**
     * Continue the logout operation.
     *
     * @param string $assocId The association that is terminated.
     * @param string|null $relayState The RelayState from the start of the logout.
     * @param \SimpleSAML\Error\Exception|null $error The error that occurred during session termination (if any).
     *
     * @throws \SimpleSAML\Error\Exception If the RelayState was lost during logout.
     */
    public function onResponse(string $assocId, ?string $relayState, ?Error\Exception $error = null): Response
    {
        if ($relayState === null) {
            throw new Error\Exception('RelayState lost during logout.');
        }

        $state = Auth\State::loadState($relayState, 'core:LogoutTraditional');

        if ($error === null) {
            Logger::info('Logged out of ' . var_export($assocId, true) . '.');
            $this->idp->terminateAssociation($assocId);
        } else {
            Logger::warning('Error received from ' . var_export($assocId, true) . ' during logout:');
            $error->logWarning();
            $state['core:Failed'] = true;
        }

        return $this->logoutNextSP($state);
    }
}
