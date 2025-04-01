<?php

declare(strict_types=1);

namespace SimpleSAML\Compat;

use Beste\Clock\LocalizedClock;
use DateTimeZone;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use SimpleSAML\SAML2\Compat\AbstractContainer;
use SimpleSAML\Utils;

class SspContainer extends AbstractContainer
{
    /** @var \Psr\Clock\ClockInterface */
    private ClockInterface $clock;

    /** @var \Psr\Log\LoggerInterface */
    protected LoggerInterface $logger;

    /**
     * Create a new SimpleSAMLphp compatible container.
     */
    public function __construct()
    {
        $this->logger = new Logger();
    }


    /**
     * {@inheritdoc}
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }


    /**
     * {@inheritdoc}
     * @return string
     */
    public function generateId(): string
    {
        $randomUtils = new Utils\Random();
        return $randomUtils->generateID();
    }


    /**
     * {@inheritdoc}
     * @param mixed $message
     * @param string $type
     */
    public function debugMessage($message, string $type): void
    {
        $xmlUtils = new Utils\XML();
        $xmlUtils->debugSAMLMessage($message, $type);
    }


    /**
     * {@inheritdoc}
     * @param string $url
     * @param array $data
     */
    public function getPOSTRedirectURL(string $url, array $data = []): string
    {
        $httpUtils = new Utils\HTTP();
        return $httpUtils->getPOSTRedirectURL($url, $data);
    }


    /**
     * {@inheritdoc}
     * @return string
     */
    public function getTempDir(): string
    {
        $sysUtils = new Utils\System();
        return $sysUtils->getTempDir();
    }


    /**
     * {@inheritdoc}
     * @param string $filename
     * @param string $date
     * @param int|null $mode
     */
    public function writeFile(string $filename, string $data, ?int $mode = null): void
    {
        $sysUtils = new Utils\System();

        if ($mode === null) {
            $mode = 0600;
        }
        $sysUtils->writeFile($filename, $data, $mode);
    }


    /**
     * @inheritDoc
     */
    public function setBlacklistedAlgorithms(?array $algos): void
    {
        $this->blacklistedEncryptionAlgorithms = $algos;
    }


    /**
     * Get the system clock
     *
     * @return \Psr\Clock\ClockInterface
     */
    public function getClock(): ClockInterface
    {
        return LocalizedClock::in(new DateTimeZone('Z'));
    }
}
