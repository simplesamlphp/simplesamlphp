<?php

declare(strict_types=1);

namespace SimpleSAML\Utils;

use Symfony\Component\HttpFoundation\{IpUtils, Request};

/**
 * Net-related utility methods.
 *
 * @deprecated This class will be removed in a next major release. Use Symfony IpUtils instead.
 * @package SimpleSAMLphp
 */
class Net
{
    /**
     * Check whether an IP address is part of a CIDR.
     *
     * @param string|array $cidr The network CIDR address.
     * @param string $ip The IP address to check. Optional. Current remote address will be used if none specified. Do
     * not rely on default parameter if running behind load balancers.
     *
     * @return boolean True if the IP address belongs to the specified CIDR, false otherwise.
     *
     */
    public function ipCIDRcheck(string|array $cidr, string $ip = null): bool
    {
        if ($ip === null) {
            $ip = Request::createFromGlobals()->getClientIp() ?? '127.0.0.1';
        }

        return IpUtils::checkIP($ip, $cidr);
    }
}
