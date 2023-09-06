<?php

declare(strict_types=1);

namespace SimpleSAML\HTTP;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class modelling a response that consists on running some function.
 *
 * This is a helper class that allows us to have the new and the old architecture coexist. This way, classes and files
 * that aren't PSR-7-aware can still be plugged into a PSR-7-compatible environment.
 *
 * @deprecated Will be removed in 3.0
 * @package SimpleSAML
 */
class RunnableResponse extends StreamedResponse
{
    protected array $arguments = [];

    /**
     * RunnableResponse constructor.
     *
     * @param callable $callback A callable that we should run as part of this response.
     * @param array $args An array of arguments to be passed to the callable. Note that each element of the array
     */
    public function __construct(
        callable $callback,
        array $arguments = [],
        int $status = 200,
        array $headers = []
    ) {
        $this->setCharset('UTF-8');
        $this->arguments = $arguments;

        parent::__construct($callback, $status, $headers);
    }


    /**
     * Get the callable for this response.
     *
     * @return callable
     */
    public function getCallable(): callable
    {
        return $this->callback;
    }


    /**
     * Get the arguments to the callable.
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }


    /**
     * {@inheritdoc}
     *
     * This method only sends the content once.
     *
     * @return $this
     *
     * Note: No return-type possible due to upstream limitations
     */
    public function sendContent()
    {
        if ($this->streamed) {
            return $this;
        }

        $this->streamed = true;

        if (null === $this->callback) {
            throw new \LogicException('The Response callback must not be null.');
        }

        call_user_func_array($this->callback, $this->arguments);

        return $this;
    }
}
