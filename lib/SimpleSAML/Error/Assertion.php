<?php

declare(strict_types=1);

namespace SimpleSAML\Error;

use SimpleSAML\Assert\Assert;

/**
 * Class for creating exceptions from assertion failures.
 *
 * @package SimpleSAMLphp
 */

class Assertion extends Exception
{
    /**
     * The assertion which failed, or null if only an expression was passed to the
     * assert-function.
     */
    private $assertion;


    /**
     * Constructor for the assertion exception.
     *
     * Should only be called from the onAssertion handler.
     *
     * @param string|null $assertion  The assertion which failed, or null if the assert-function was
     *                                given an expression.
     */
    public function __construct(string $assertion = null)
    {
        $msg = 'Assertion failed: ' . var_export($assertion, true);
        parent::__construct($msg);

        $this->assertion = $assertion;
    }


    /**
     * Retrieve the assertion which failed.
     *
     * @return string|null  The assertion which failed, or null if the assert-function was called with an expression.
     */
    public function getAssertion(): ?string
    {
        return $this->assertion;
    }


    /**
     * Install this assertion handler.
     *
     * This function will register this assertion handler. If will not enable assertions if they are
     * disabled.
     */
    public static function installHandler(): void
    {
        assert_options(ASSERT_WARNING, 0);
        assert_options(ASSERT_QUIET_EVAL, 0);
        assert_options(ASSERT_CALLBACK, [Assertion::class, 'onAssertion']);
    }


    /**
     * Handle assertion.
     *
     * This function handles an assertion.
     *
     * @param string $file  The file assert was called from.
     * @param int $line  The line assert was called from.
     * @param mixed $message  The expression which was passed to the assert-function.
     */
    public static function onAssertion(string $file, int $line, $message): void
    {
        if (!empty($message)) {
            $exception = new self($message);
        } else {
            $exception = new self();
        }

        $exception->logError();
    }
}
