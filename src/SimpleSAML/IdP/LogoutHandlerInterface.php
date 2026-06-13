<?php

declare(strict_types=1);

namespace SimpleSAML\IdP;

use SimpleSAML\Error;
use SimpleSAML\IdP;
use Symfony\Component\HttpFoundation\Response;

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
     * @param \SimpleSAML\IdP $idp The IdP we are logging out from.
     */
    public function __construct(IdP $idp);


    /**
     * Start a logout operation.
     *
     * This function must never return.
     *
     * @param array &$state The logout state.
     * @param string|null $assocId The association that started the logout.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function startLogout(array &$state, ?string $assocId): Response;


    /**
     * Handles responses to our logout requests.
     *
     * @param string $assocId The association that is terminated.
     * @param string|null $relayState The RelayState from the start of the logout.
     * @param \SimpleSAML\Error\Exception|null $error The error that occurred during session termination (if any).
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onResponse(string $assocId, ?string $relayState, ?Error\Exception $error = null): Response;
}
