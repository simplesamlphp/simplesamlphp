<?php

namespace SimpleSAML\Logger;

use Psr\Log\LoggerInterface;
use SimpleSAML\Configuration;

/**
 * Basic Implementation of LoggerAwareInterface.
 */
trait LoggerAwareTrait
{
    /**
     * The logger instance.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;


    /**
     * Gets a logger.
     *
     * @return \Psr\Log\LoggerInterface $logger
     */
    public function getLogger(): LoggerInterface
    {
        /** @psalm-var \Psr\Log\LoggerInterface $this->logger */
        if (isset($this->logger)) {
            return $this->logger;
        }

        return Configuration::getInstance()::getLogger();
    }


    /**
     * Sets a logger.
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    protected function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
