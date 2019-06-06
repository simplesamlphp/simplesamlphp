<?php

namespace SimpleSAML\Error;

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
    private $class;


    /**
     * Create a serializable exception representing an unserializable exception.
     *
     * @param \Exception $original  The original exception.
     */
    public function __construct(\Exception $original)
    {

        $this->class = get_class($original);
        $msg = $original->getMessage();
        $code = $original->getCode();

        if ($original instanceof \PDOException) {
            // PDOException uses a string as the code. Filter it out here.
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
    public function getClass()
    {
        return $this->class;
    }
}
