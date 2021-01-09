<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Error;

use SimpleSAML\SAML2\Constants;
use SimpleSAML\SAML2\XML\samlp\Status;
use SimpleSAML\SAML2\XML\samlp\StatusCode;
use Throwable;

/**
 * A SAML error indicating that none of the IdPs requested are supported.
 *
 * @package SimpleSAMLphp
 */
class NoSupportedIDP extends \SimpleSAML\Module\saml\Error
{
    /**
     * NoSupportedIDP error constructor.
     *
     * @param string $responsible A string telling who is responsible for this error. Can be one of the following:
     *   - \SimpleSAML\SAML2\Constants::STATUS_RESPONDER: in case the error is caused by this SAML responder.
     *   - \SimpleSAML\SAML2\Constants::STATUS_REQUESTER: in case the error is caused by the SAML requester.
     * @param string|null $message A short message explaining why this error happened.
     * @param \Throwable|null $cause An exception that caused this error.
     */
    public function __construct(string $responsible, string $message = null, Throwable $cause = null)
    {
        parent::__construct(
            new Status(
                new StatusCode(
                    $responsible,
                    [new StatusCode(Constants::STATUS_NO_SUPPORTED_IDP)]
                ),
                $message,
            ),
            $cause
        );
    }
}
