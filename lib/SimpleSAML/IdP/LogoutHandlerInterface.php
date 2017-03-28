<?php

namespace SimpleSAML\IdP;


/**
 * Interface that all logout handlers must implement.
 *
 * @package SimpleSAMLphp
 */
interface LogoutHandlerInterface
{


    /**
     * Initialize this logout handler.
     *
     * @param \SimpleSAML_IdP $idp The IdP we are logging out from.
     */
    public function __construct(\SimpleSAML_IdP $idp);


    /**
     * Start a logout operation.
     *
     * This function must never return.
     *
     * @param array &$state The logout state.
     * @param string|null $assocId The association that started the logout.
     */
    public function startLogout(array &$state, $assocId);


    /**
     * Handles responses to our logout requests.
     *
     * This function will never return.
     *
     * @param string $assocId The association that is terminated.
     * @param string|null $relayState The RelayState from the start of the logout.
     * @param \SimpleSAML_Error_Exception|null $error The error that occurred during session termination (if any).
     */
    public function onResponse($assocId, $relayState, \SimpleSAML_Error_Exception $error = null);
}
