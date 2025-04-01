<?php

declare(strict_types=1);

namespace SimpleSAML\IdP;

use SimpleSAML\{Auth, Configuration, Error, IdP, Module, Utils};
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Response;

use function count;
use function is_null;
use function sha1;
use function var_export;

/**
 * Class that handles iframe logout.
 *
 * @package SimpleSAMLphp
 */

class IFrameLogoutHandler implements LogoutHandlerInterface
{
    /**
     * LogoutIFrame constructor.
     *
     * @param \SimpleSAML\IdP $idp The IdP to log out from.
     */
    public function __construct(
        private IdP $idp,
    ) {
    }


    /**
     * Start the logout operation.
     *
     * @param array &$state The logout state.
     * @param string|null $assocId The SP we are logging out from.
     */
    public function startLogout(array &$state, ?string $assocId): Response
    {
        $associations = $this->idp->getAssociations();

        if (count($associations) === 0) {
            return $this->idp->finishLogout($state);
        }

        foreach ($associations as $id => &$association) {
            $idp = IdP::getByState(Configuration::getInstance(), $association);
            $association['core:Logout-IFrame:Name'] = $idp->getSPName($id);
            $association['core:Logout-IFrame:State'] = 'onhold';
        }
        $state['core:Logout-IFrame:Associations'] = $associations;

        if (!is_null($assocId)) {
            $spName = $this->idp->getSPName($assocId);
            if ($spName === null) {
                $spName = ['en' => $assocId];
            }

            $state['core:Logout-IFrame:From'] = $spName;
        } else {
            $state['core:Logout-IFrame:From'] = null;
        }

        $params = [
            'id' => Auth\State::saveState($state, 'core:Logout-IFrame'),
        ];
        if (isset($state['core:Logout-IFrame:InitType'])) {
            $params['type'] = $state['core:Logout-IFrame:InitType'];
        }

        $url = Module::getModuleURL('core/logout-iframe', $params);
        $httpUtils = new Utils\HTTP();
        return $httpUtils->redirectTrustedURL($url);
    }


    /**
     * Continue the logout operation.
     *
     * @param string $assocId The association that is terminated.
     * @param string|null $relayState The RelayState from the start of the logout.
     * @param \SimpleSAML\Error\Exception|null $error The error that occurred during session termination (if any).
     */
    public function onResponse(string $assocId, ?string $relayState, ?Error\Exception $error = null): Response
    {
        $this->idp->terminateAssociation($assocId);

        $config = Configuration::getInstance();

        $t = new Template($config, 'IFrameLogoutHandler.twig');
        $t->data['assocId'] = var_export($assocId, true);
        $t->data['spId'] = sha1($assocId);
        if (!is_null($error)) {
            $t->data['errorMsg'] = $error->getMessage();
        }

        return $t;
    }
}
