<?php

declare(strict_types=1);

namespace SimpleSAML\Error;

use Throwable;

use function is_int;

/**
 * Class for saving normal exceptions for serialization.
 *
 * This class is used by the \SimpleSAML\Auth\State class when it needs
 * to serialize an exception which doesn't subclass the
 * \SimpleSAML\Error\Exception class.
 *
 * It creates a new exception which contains the backtrace and message
 * of the original exception.
 *
 * @package SimpleSAMLphp
 */

class UnserializableException extends Exception
{
    /**
     * The classname of the original exception.
     *
     * @var string
     */
    private string $class;


    /**
     * Create a serializable exception representing an unserializable exception.
     *
     * @param \Throwable $original  The original exception.
     */
    public function __construct(Throwable $original)
    {

        $this->class = $original::class;
        $msg = $original->getMessage();

        $code = $original->getCode();

        if (!is_int($code)) {
            // PDOException and possibly others use a string for the code. Filter it out here.
            $code = -1;
        }

        parent::__construct($msg, $code);
        $this->initBacktrace($original);
    }


    /**
     * Retrieve the class of this exception.
     *
     * @return string  The classname.
     */
    public function getClass(): string
    {
        return $this->class;
    }
}
