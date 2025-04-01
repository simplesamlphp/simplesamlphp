<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml;

use SimpleSAML\SAML2\Constants as C;
use Throwable;

use function strlen;
use function substr;

/**
 * Class for representing a SAML 2 error.
 *
 * @package SimpleSAMLphp
 */

class Error extends \SimpleSAML\Error\Exception
{
    /**
     * Create a SAML 2 error.
     *
     * @param string $status  The top-level status code.
     * @param string|null $subStatus  The second-level status code.
     * Can be NULL, in which case there is no second-level status code.
     * @param string|null $statusMessage  The status message.
     * Can be NULL, in which case there is no status message.
     * @param \Throwable|null $cause  The cause of this exception. Can be NULL.
     */
    public function __construct(
        private string $status,
        private ?string $subStatus = null,
        private ?string $statusMessage = null,
        ?Throwable $cause = null,
    ) {
        $st = self::shortStatus($status);
        if ($subStatus !== null) {
            $st .= '/' . self::shortStatus($subStatus);
        }
        if ($statusMessage !== null) {
            $st .= ': ' . $statusMessage;
        }
        parent::__construct($st, 0, $cause);
    }


    /**
     * Get the top-level status code.
     *
     * @return string  The top-level status code.
     */
    public function getStatus(): string
    {
        return $this->status;
    }


    /**
     * Get the second-level status code.
     *
     * @return string|null  The second-level status code or NULL if no second-level status code is present.
     */
    public function getSubStatus(): ?string
    {
        return $this->subStatus;
    }


    /**
     * Get the status message.
     *
     * @return string|null  The status message or NULL if no status message is present.
     */
    public function getStatusMessage(): ?string
    {
        return $this->statusMessage;
    }


    /**
     * Create a SAML2 error from an exception.
     *
     * This function attempts to create a SAML2 error with the appropriate
     * status codes from an arbitrary exception.
     *
     * @param \Throwable $e  The original exception.
     * @return \SimpleSAML\Error\Exception  The new exception.
     */
    public static function fromException(Throwable $e): \SimpleSAML\Error\Exception
    {
        if ($e instanceof \SimpleSAML\Module\saml\Error) {
            // Return the original exception unchanged
            return $e;
        } else {
            $e = new self(
                C::STATUS_RESPONDER,
                null,
                $e::class . ': ' . $e->getMessage(),
                $e,
            );
        }

        return $e;
    }


    /**
     * Create a normal exception from a SAML2 error.
     *
     * This function attempts to reverse the operation of the fromException() function.
     * If it is unable to create a more specific exception, it will return the current
     * object.
     *
     * @see \SimpleSAML\Module\saml\Error::fromException()
     *
     * @return \SimpleSAML\Error\Exception  An exception representing this error.
     */
    public function toException(): \SimpleSAML\Error\Exception
    {
        $e = null;

        switch ($this->status) {
            case C::STATUS_RESPONDER:
                switch ($this->subStatus) {
                    case C::STATUS_NO_PASSIVE:
                        $e = new \SimpleSAML\Module\saml\Error\NoPassive(
                            C::STATUS_RESPONDER,
                            $this->statusMessage,
                        );
                        break;
                }
                break;
        }

        if ($e === null) {
            return $this;
        }

        return $e;
    }


    /**
     * Create a short version of the status code.
     *
     * Remove the 'urn:oasis:names:tc:SAML:2.0:status:'-prefix of status codes
     * if it is present.
     *
     * @param string $status  The status code.
     * @return string  A shorter version of the status code.
     */
    private static function shortStatus(string $status): string
    {
        $t = 'urn:oasis:names:tc:SAML:2.0:status:';
        if (substr($status, 0, strlen($t)) === $t) {
            return substr($status, strlen($t));
        }

        return $status;
    }
}
