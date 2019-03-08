<?php

namespace SimpleSAML\IdP;

use SimpleSAML\Module;
use SimpleSAML\Utils\HTTP;

/**
 * Class that handles iframe logout.
 *
 * @package SimpleSAMLphp
 */

class IFrameLogoutHandler implements LogoutHandlerInterface
{
    /**
     * The IdP we are logging out from.
     *
     * @var \SimpleSAML\IdP
     */
    private $idp;

    /**
     * LogoutIFrame constructor.
     *
     * @param \SimpleSAML\IdP $idp The IdP to log out from.
     */
    public function __construct(\SimpleSAML\IdP $idp)
    {
        $this->idp = $idp;
    }

    /**
     * Start the logout operation.
     *
     * @param array &$state The logout state.
     * @param string|null $assocId The SP we are logging out from.
     * @return void
     */
    public function startLogout(array &$state, $assocId)
    {
        assert(is_string($assocId) || $assocId === null);

        $associations = $this->idp->getAssociations();

        if (count($associations) === 0) {
            $this->idp->finishLogout($state);
        }

        foreach ($associations as $id => &$association) {
            $idp = \SimpleSAML\IdP::getByState($association);
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
            'id' => \SimpleSAML\Auth\State::saveState($state, 'core:Logout-IFrame'),
        ];
        if (isset($state['core:Logout-IFrame:InitType'])) {
            $params['type'] = $state['core:Logout-IFrame:InitType'];
        }

        $url = Module::getModuleURL('core/idp/logout-iframe.php', $params);
        HTTP::redirectTrustedURL($url);
    }


    /**
     * Continue the logout operation.
     *
     * This function will never return.
     *
     * @param string $assocId The association that is terminated.
     * @param string $relayState The RelayState from the start of the logout.
     * @param \SimpleSAML\Error\Exception|null $error The error that occurred during session termination (if any).
     * @return void
     */
    public function onResponse($assocId, $relayState, \SimpleSAML\Error\Exception $error = null)
    {
        assert(is_string($assocId));

        $config = \SimpleSAML\Configuration::getInstance();
        $this->idp->terminateAssociation($assocId);

        $t = new \SimpleSAML\XHTML\Template($config, 'IFrameLogoutHandler.twig');
        $t->data['assocId'] = var_export($assocId, true);
        $t->data['spId'] = sha1($assocId);
        if (!is_null($error)) {
            $t->data['errorMsg'] = $error->getMessage();
        }
        $t->show();
        exit(0);
    }
}
