<?php

namespace SimpleSAML\Auth;

/**
 * A class that generates and verifies time-limited tokens.
 */
class TimeLimitedToken
{

    /**
     * @var string
     */
    protected $secretSalt;

    /**
     * @var int
     */
    protected $lifetime;

    /**
     * @var int
     */
    protected $skew;


    /**
     * @param $lifetime int Token lifetime in seconds. Defaults to 900 (15 min).
     * @param $secretSalt string A random and unique salt per installation. Defaults to the salt in the configuration.
     * @param $skew int The allowed time skew (in seconds) between what the server generates and the one that calculates
     * the token.
     */
    public function __construct($lifetime = 900, $secretSalt = null, $skew = 1)
    {
        if ($secretSalt === null) {
            $secretSalt = \SimpleSAML\Utils\Config::getSecretSalt();
        }

        $this->secretSalt = $secretSalt;
        $this->lifetime = $lifetime;
        $this->skew = $skew;
    }


    public function addVerificationData($data)
    {
        $this->secretSalt .= '|'.$data;
    }


    /**
     * Calculate the current time offset to the current time slot.
     * With some amount of time skew
     */
    private function getOffset()
    {
        return (time() - $this->skew) % ($this->lifetime + $this->skew);
    }


    /**
     * Calculate the time slot for a given offset.
     */
    private function calculateTimeSlot($offset)
    {
        return floor((time() - $offset) / ($this->lifetime + $this->skew));
    }


    /**
     * Calculates a token value for a given offset.
     */
    private function calculateTokenValue($offset)
    {
        // a secret salt that should be randomly generated for each installation
        return sha1($this->calculateTimeSlot($offset).':'.$this->secretSalt);
    }


    /**
     * Generates a token that contains an offset and a token value, using the current offset.
     */
    public function generateToken()
    {
        $current_offset = $this->getOffset();
        return dechex($current_offset).'-'.$this->calculateTokenValue($current_offset);
    }


    /**
     * @see generateToken
     * @deprecated This method will be removed in SSP 2.0. Use generateToken() instead.
     */
    public function generate_token()
    {
        return $this->generateToken();
    }


    /**
     * Validates a token by calculating the token value for the provided offset and comparing it.
     */
    public function validateToken($token)
    {
        $splittoken = explode('-', $token);
        $offset = hexdec($splittoken[0]);
        $value = $splittoken[1];
        return ($this->calculateTokenValue($offset) === $value);
    }


    /**
     * @see validateToken
     * @deprecated This method will be removed in SSP 2.0. Use validateToken() instead.
     */
    public function validate_token($token)
    {
        return $this->validateToken($token);
    }
}
