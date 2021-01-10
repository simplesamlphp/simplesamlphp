<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Error;

use SAML2\Constants;
use Throwable;

/**
 * A SAML error indicating that none of the requested Authentication Contexts can be used.
 *
 * @package SimpleSAMLphp
 */
class NoAuthnContext extends \SimpleSAML\Module\saml\Error
{
    /**
     * NoAuthnContext error constructor.
     *
     * @param string $responsible A string telling who is responsible for this error. Can be one of the following:
     *   - \SAML2\Constants::STATUS_RESPONDER: in case the error is caused by this SAML responder.
     *   - \SAML2\Constants::STATUS_REQUESTER: in case the error is caused by the SAML requester.
     * @param string|null $message A short message explaining why this error happened.
     * @param \Throwable|null $cause An exception that caused this error.
     */
    public function __construct(string $responsible, string $message = null, Throwable $cause = null)
    {
        parent::__construct($responsible, Constants::STATUS_NO_AUTHN_CONTEXT, $message, $cause);
    }
}
