<?php
/**
 * Class Utils_Net
 */

class Utils_Net extends PHPUnit_Framework_TestCase
{


    /**
     * Test the function that checks for IPs belonging to a CIDR.
     */
    public function testIpCIDRcheck()
    {
        // check CIDR w/o mask
        $this->assertEquals(false, SimpleSAML_Utils_Net::ipCIDRcheck('127.0.0.0', '127.0.0.1'));

        // check wrong CIDR w/ mask
        $this->assertEquals(false, SimpleSAML_Utils_Net::ipCIDRcheck('127.0.0.256/24', '127.0.0.1'));

        // check wrong IP
        $this->assertEquals(false, SimpleSAML_Utils_Net::ipCIDRcheck('127.0.0.0/24', '127.0.0'));
        $this->assertEquals(false, SimpleSAML_Utils_Net::ipCIDRcheck('127.0.0.0/24', '127.0.0.*'));

        // check limits for standard classes
        $this->assertEquals(true,  SimpleSAML_Utils_Net::ipCIDRcheck('127.0.0.0/24', '127.0.0.0'));
        $this->assertEquals(true,  SimpleSAML_Utils_Net::ipCIDRcheck('127.0.0.0/24', '127.0.0.255'));
        $this->assertEquals(false, SimpleSAML_Utils_Net::ipCIDRcheck('127.0.0.0/24', '127.0.0.256'));

        $this->assertEquals(true,  SimpleSAML_Utils_Net::ipCIDRcheck('127.0.0.0/16', '127.0.0.0'));
        $this->assertEquals(true,  SimpleSAML_Utils_Net::ipCIDRcheck('127.0.0.0/16', '127.0.255.255'));
        $this->assertEquals(false, SimpleSAML_Utils_Net::ipCIDRcheck('127.0.0.0/16', '127.0.255.256'));
        $this->assertEquals(false, SimpleSAML_Utils_Net::ipCIDRcheck('127.0.0.0/16', '127.0.256.255'));

        // check limits for non-standard classes
        $this->assertEquals(true,  SimpleSAML_Utils_Net::ipCIDRcheck('127.0.0.0/23', '127.0.0.0'));
        $this->assertEquals(true,  SimpleSAML_Utils_Net::ipCIDRcheck('127.0.0.0/23', '127.0.1.255'));
        $this->assertEquals(false, SimpleSAML_Utils_Net::ipCIDRcheck('127.0.0.0/23', '127.0.1.256'));
        $this->assertEquals(false, SimpleSAML_Utils_Net::ipCIDRcheck('127.0.0.0/23', '127.0.2.0'));
    }
}