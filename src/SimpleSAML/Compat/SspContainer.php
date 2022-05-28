<?php

declare(strict_types=1);

namespace SimpleSAML\Compat;

use Psr\Log\LoggerInterface;
use SAML2\Compat\AbstractContainer;
use SimpleSAML\Utils;

class SspContainer extends AbstractContainer
{
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
    public function redirect(string $url, array $data = []): void
    {
        $httpUtils = new Utils\HTTP();
        $httpUtils->redirectTrustedURL($url, $data);
    }


    /**
     * {@inheritdoc}
     * @param string $url
     * @param array $data
     */
    public function postRedirect(string $url, array $data = []): void
    {
        $httpUtils = new Utils\HTTP();
        $httpUtils->submitPOSTData($url, $data);
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
    public function writeFile(string $filename, string $data, int $mode = null): void
    {
        $sysUtils = new Utils\System();

        if ($mode === null) {
            $mode = 0600;
        }
        $sysUtils->writeFile($filename, $data, $mode);
    }
}
