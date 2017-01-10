<?php

namespace SimpleSAML\Test\Utils;

use SimpleSAML\Utils\Crypto;

/**
 * Tests for SimpleSAML\Utils\Crypto.
 */
class CryptoTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test invalid input provided to the aesDecrypt() method.
     *
     * @expectedException \InvalidArgumentException
     *
     * @covers \SimpleSAML\Utils\Crypto::_aesDecrypt
     */
    public function testAesDecryptBadInput()
    {
        $m = new \ReflectionMethod('\SimpleSAML\Utils\Crypto', '_aesDecrypt');
        $m->setAccessible(true);

        $m->invokeArgs(null, array(array(), 'SECRET'));
    }


    /**
     * Test invalid input provided to the aesEncrypt() method.
     *
     * @expectedException \InvalidArgumentException
     *
     * @covers \SimpleSAML\Utils\Crypto::_aesEncrypt
     */
    public function testAesEncryptBadInput()
    {
        $m = new \ReflectionMethod('\SimpleSAML\Utils\Crypto', '_aesEncrypt');
        $m->setAccessible(true);

        $m->invokeArgs(null, array(array(), 'SECRET'));
    }


    /**
     * Test that aesDecrypt() works properly, being able to decrypt some previously known (and correct)
     * ciphertext.
     *
     * @covers \SimpleSAML\Utils\Crypto::_aesDecrypt
     */
    public function testAesDecrypt()
    {
        if (!extension_loaded('openssl')) {
            $this->setExpectedException('\SimpleSAML_Error_Exception');
        }

        $secret = 'SUPER_SECRET_SALT';
        $m = new \ReflectionMethod('\SimpleSAML\Utils\Crypto', '_aesDecrypt');
        $m->setAccessible(true);

        $plaintext = 'SUPER_SECRET_TEXT';
        $ciphertext = 'NmRkODJlZGE2OTA3YTYwMm9En+KAReUk2z7Xi/b3c39kF/c1n6Vdj/zNARQt+UHU';
        $this->assertEquals($plaintext, $m->invokeArgs(null, array(base64_decode($ciphertext), $secret)));
    }


    /**
     * Test that aesEncrypt() produces ciphertexts that aesDecrypt() can decrypt.
     *
     * @covers \SimpleSAML\Utils\Crypto::_aesDecrypt
     * @covers \SimpleSAML\Utils\Crypto::_aesEncrypt
     */
    public function testAesEncrypt()
    {
        if (!extension_loaded('openssl')) {
            $this->setExpectedException('\SimpleSAML_Error_Exception');
        }

        $secret = 'SUPER_SECRET_SALT';
        $e = new \ReflectionMethod('\SimpleSAML\Utils\Crypto', '_aesEncrypt');
        $d = new \ReflectionMethod('\SimpleSAML\Utils\Crypto', '_aesDecrypt');
        $e->setAccessible(true);
        $d->setAccessible(true);

        $original_plaintext = 'SUPER_SECRET_TEXT';
        $ciphertext = $e->invokeArgs(null, array($original_plaintext, $secret));
        $decrypted_plaintext = $d->invokeArgs(null, array($ciphertext, $secret));
        $this->assertEquals($original_plaintext, $decrypted_plaintext);
    }


    /**
     * Test that the pem2der() and der2pem() methods work correctly.
     *
     * @covers \SimpleSAML\Utils\Crypto::der2pem
     * @covers \SimpleSAML\Utils\Crypto::pem2der
     */
    public function testFormatConversion()
    {
        $pem = <<<PHP
-----BEGIN CERTIFICATE-----
MIIF8zCCA9ugAwIBAgIJANSv0D4ZoP9iMA0GCSqGSIb3DQEBCwUAMIGPMQswCQYD
VQQGEwJFWDEQMA4GA1UECAwHRXhhbXBsZTEQMA4GA1UEBwwHRXhhbXBsZTEQMA4G
A1UECgwHRXhhbXBsZTEQMA4GA1UECwwHRXhhbXBsZTEUMBIGA1UEAwwLZXhhbXBs
ZS5jb20xIjAgBgkqhkiG9w0BCQEWE3NvbWVvbmVAZXhhbXBsZS5jb20wHhcNMTcw
MTEwMDk1MTIxWhcNMTgwMTEwMDk1MTIxWjCBjzELMAkGA1UEBhMCRVgxEDAOBgNV
BAgMB0V4YW1wbGUxEDAOBgNVBAcMB0V4YW1wbGUxEDAOBgNVBAoMB0V4YW1wbGUx
EDAOBgNVBAsMB0V4YW1wbGUxFDASBgNVBAMMC2V4YW1wbGUuY29tMSIwIAYJKoZI
hvcNAQkBFhNzb21lb25lQGV4YW1wbGUuY29tMIICIjANBgkqhkiG9w0BAQEFAAOC
Ag8AMIICCgKCAgEA5Mp4xLdV41NtAI3YYr70G4gJYKegTHRwYhMeYAjudmZUng1/
vbHLFGQybm8C6naEireQhHWzYfmDkOMU8dmdItwN4YLypYWwxYuWutWWIsDHHe0y
CfjVz6nnTPSjZEq5PpJYY+2XTZOP+g8FmDo4nmhEchF+8eiGvHQzdBqh26EwJjQ3
LMXyc2F2+9Cm/On+M6BQKvvXkg8FqggW8YwcOujZNWGbfG3LVJcZ0p39PbnNgJX2
ExbscPHfjmv2RlXd5EjruRhW1oX35sB4ycIFfHGWbCl2HPc1VfouJMq/fxgkKJdb
3RNxIBZnGpBdVJ25lCfk6t2dRdWKECrBHmcX/uR19of4H+hd4zOCPrej8IsCF2IS
1umyUBIDyPE4WciWMUERyG1dxSjUI4DBMi4l+LRX1YUrADSthH/0jV1WDsGpHT26
+at2ZBgPy8tEvpLsITw/opUKWPCx3u5JVwFdduL8i0UF2yHmcsq44TUHVEoA1c55
T+46ug7zHzhqFrPIwUN0DTKf33pg30xtL4d1rebc5K1KBNd9IDicd2iL8uD3HG6L
dPdt+1OaSbGlMMKdOte31TdOp7WhqcFANkKxd6TzMUHMVmkbYh2NesaQmCgxJdv6
/pD7L+sbMKdhlcSoJW+1wwtIo5+CzZxPA2ehZ/IWQg+Oh6djvUJzo0/84ncCAwEA
AaNQME4wHQYDVR0OBBYEFOk6cEb397GMRCJe9xMIZ/y3yFvEMB8GA1UdIwQYMBaA
FOk6cEb397GMRCJe9xMIZ/y3yFvEMAwGA1UdEwQFMAMBAf8wDQYJKoZIhvcNAQEL
BQADggIBACc1c8j7oZeVDd8O2k97kY/7pHypVZswLfmg1UqbUmYYqQ9lM6FD0J8R
P+B8i7zST09pJ0FOsCsbyUKQmMIq/citTKmgk8NLK8otWHewHs5KTpsEvJm9XV4s
QjF07GBECJdQWu93Rn8FdR9eJ+H0Y0oHbBu3OtSbHFHyDvaCI5bxM/5FPf4HkJil
qIQunhO5gkz21ebukQUgiZ1YmFl0LjxGUDUDwnQ/3kOejlMUQv+ZXdQp/SaX1z5c
dQlGl/8HDs1YAM3duvdMCXn2LP3QuhrphT/+2o+ZkY32I1p/Q0fDNaE4u7JjaxAd
6+ijpmzZwgG5cFVU+sEeDqCI5MFn2JKiSCrHAHFMTnkpq687qBTLWoYTJ4coxtvs
kmvdoZytKiSf7aDzGQK345BSZWJ+D5RJr2250PHMMeNkFBc+GdGiRsABhhHQAqtE
7TVgdwvc8CYCfXlhRzdSowAVWibiftfPMmItM8Z0w5T/iPW0MsiCLGa5AvCHicN7
pfajpJ9ZzdyLIo6dVjdQtl+S1rpFCx7ziVN8tCCX4fAVCqRqZJaG/UMLvguVqayb
3Aw1B/fVvWoAnAzVN5ZEClZvuyjImnNZpnYSWHzCJ/9JTqB7rq93nf6Olp9QXD5y
5iHKlJ6FlnuhcGCDsUCvG8qCw9FfoS0tuS4tKoQ5WHGQx3sKmr/D
-----END CERTIFICATE-----
PHP;
        $this->assertEquals(trim($pem), trim(Crypto::der2pem(Crypto::pem2der($pem))));
    }
}
