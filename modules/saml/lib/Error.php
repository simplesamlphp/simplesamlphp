<?php

/**
 * Class for representing a SAML 2 error.
 *
 * @package SimpleSAMLphp
 */
class sspmod_saml_Error extends SimpleSAML_Error_Exception
{
    /**
     * The top-level status code.
     *
     * @var string
     */
    private $status;

    /**
     * The second-level status code, or NULL if no second-level status code is defined.
     *
     * @var string|null
     */
    private $subStatus;

    /**
     * The status message, or NULL if no status message is defined.
     *
     * @var string|null
     */
    private $statusMessage;


    /**
     * Create a SAML 2 error.
     *
     * @param string $status  The top-level status code.
     * @param string|null $subStatus  The second-level status code. Can be NULL, in which case there is no second-level status code.
     * @param string|null $statusMessage  The status message. Can be NULL, in which case there is no status message.
     * @param Exception|null $cause  The cause of this exception. Can be NULL.
     */
    public function __construct($status, $subStatus = null, $statusMessage = null, Exception $cause = null)
    {
        assert(is_string($status));
        assert($subStatus === null || is_string($subStatus));
        assert($statusMessage === null || is_string($statusMessage));

        $st = self::shortStatus($status);
        if ($subStatus !== null) {
            $st .= '/' . self::shortStatus($subStatus);
        }
        if ($statusMessage !== null) {
            $st .= ': ' . $statusMessage;
        }
        parent::__construct($st, 0, $cause);

        $this->status = $status;
        $this->subStatus = $subStatus;
        $this->statusMessage = $statusMessage;
    }


    /**
     * Get the top-level status code.
     *
     * @return string  The top-level status code.
     */
    public function getStatus()
    {
        return $this->status;
    }


    /**
     * Get the second-level status code.
     *
     * @return string|null  The second-level status code or NULL if no second-level status code is present.
     */
    public function getSubStatus()
    {
        return $this->subStatus;
    }


    /**
     * Get the status message.
     *
     * @return string|null  The status message or NULL if no status message is present.
     */
    public function getStatusMessage()
    {
        return $this->statusMessage;
    }


    /**
     * Create a SAML2 error from an exception.
     *
     * This function attempts to create a SAML2 error with the appropriate
     * status codes from an arbitrary exception.
     *
     * @param Exception $exception  The original exception.
     * @return sspmod_saml_Error  The new exception.
     */
    public static function fromException(Exception $exception)
    {
        if ($exception instanceof sspmod_saml_Error) {
            // Return the original exception unchanged
            return $exception;

        // TODO: remove this branch in 2.0
        } elseif ($exception instanceof SimpleSAML_Error_NoPassive) {
            $e = new self(
                \SAML2\Constants::STATUS_RESPONDER,
                \SAML2\Constants::STATUS_NO_PASSIVE,
                $exception->getMessage(),
                $exception
                );
        // TODO: remove this branch in 2.0
        } elseif ($exception instanceof SimpleSAML_Error_ProxyCountExceeded) {
            $e = new self(
                \SAML2\Constants::STATUS_RESPONDER,
                \SAML2\Constants::STATUS_PROXY_COUNT_EXCEEDED,
                $exception->getMessage(),
                $exception
            );
        } else {
            $e = new self(
                \SAML2\Constants::STATUS_RESPONDER,
                null,
                get_class($exception) . ': ' . $exception->getMessage(),
                $exception
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
     * @see sspmod_saml_Error::fromException()
     *
     * @return SimpleSAML_Error_Exception  An exception representing this error.
     */
    public function toException()
    {
        $e = null;

        switch ($this->status) {
            case \SAML2\Constants::STATUS_RESPONDER:
                switch ($this->subStatus) {
                    case \SAML2\Constants::STATUS_NO_PASSIVE:
                        $e = new SimpleSAML\Module\saml\Error\NoPassive(
                            \SAML2\Constants::STATUS_RESPONDER,
                            $this->statusMessage
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
    private static function shortStatus($status)
    {
        assert(is_string($status));

        $t = 'urn:oasis:names:tc:SAML:2.0:status:';
        if (substr($status, 0, strlen($t)) === $t) {
            return substr($status, strlen($t));
        }

        return $status;
    }
}
