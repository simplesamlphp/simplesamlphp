<?php

namespace SimpleSAML\Test\Utils;

use SimpleSAML\Utils\Crypto;
use \SimpleSAML_Configuration as Configuration;

use \org\bovigo\vfs\vfsStream;

/**
 * Tests for SimpleSAML\Utils\Crypto.
 */
class CryptoTest extends \PHPUnit_Framework_TestCase
{
    const ROOTDIRNAME = 'testdir';
    const DEFAULTCERTDIR = 'certdir';

    public function setUp()
    {
        $this->root = vfsStream::setup(
            self::ROOTDIRNAME,
            null,
            array(
                self::DEFAULTCERTDIR => array(),
            )
        );
        $this->root_directory = vfsStream::url(self::ROOTDIRNAME);
        $this->certdir = $this->root_directory.DIRECTORY_SEPARATOR.self::DEFAULTCERTDIR;
    }

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
        $ciphertext = 'uR2Yu0r4itInKx91D/l9y/08L5CIQyev9nAr27fh3Sshous4vbXRRcMcjqHDOrquD+2vqLyw7ygnbA9jA9TpB4hLZocvAWcTN8tyO82hiSY=';
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

    /**
     * @covers \SimpleSAML\Utils\Crypto::pwHash
     */
    public function testGoodPwHash()
    {
        $pw = "password";
        $algorithm = "SHA1";

        $res = Crypto::pwHash($pw, $algorithm);

        /*
         * echo -n "password" | sha1sum | awk -F " " '{print $1}' | xxd -r -p | base64
         * W6ph5Mm5Pz8GgiULbPgzG37mj9g=
         */
        $expected = "{SHA}W6ph5Mm5Pz8GgiULbPgzG37mj9g=";

        $this->assertEquals($expected, $res);
    }

    /**
     * @covers \SimpleSAML\Utils\Crypto::pwHash
     */
    public function testGoodSaltedPwHash()
    {
        $pw = "password";
        $algorithm = "SSHA1";
        $salt = "salt";

        $res = Crypto::pwHash($pw, $algorithm, $salt);

        /*
         * echo -n "password""salt" | sha1sum | awk -v salt=$(echo -n "salt" | xxd -u -p) -F " " '{print $1 salt}' | xxd -r -p | base64
         * yI6cZwQadOA1e+/f+T+H3eCQQhRzYWx0
         */
        $expected = "{SSHA}yI6cZwQadOA1e+/f+T+H3eCQQhRzYWx0";

        $this->assertEquals($expected, $res);
    }

    /**
     * @expectedException \SimpleSAML_Error_Exception
     *
     * @covers \SimpleSAML\Utils\Crypto::pwHash
     */
    public function testBadHashAlgorithm()
    {
        $pw = "password";
        $algorithm = "wtf";

        Crypto::pwHash($pw, $algorithm);
    }

    /**
     * @covers \SimpleSAML\Utils\Crypto::pwValid
     */
    public function testGoodPwValid()
    {
        $pw = "password";
        $algorithm = "SHA1";

        $hash = Crypto::pwHash($pw, $algorithm);
        $res = Crypto::pwValid($hash, $pw);

        $this->assertTrue($res);
    }

    /**
     * @covers \SimpleSAML\Utils\Crypto::pwValid
     */
    public function testGoodSaltedPwValid()
    {
        $pw = "password";
        $algorithm = "SSHA1";
        $salt = "salt";

        $hash = Crypto::pwHash($pw, $algorithm, $salt);
        $res = Crypto::pwValid($hash, $pw);

        $this->assertTrue($res);
    }

    /**
     * @expectedException \SimpleSAML_Error_Exception
     *
     * @covers \SimpleSAML\Utils\Crypto::pwValid
     */
    public function testBadHashAlgorithmValid()
    {
        $pw = "password";
        $algorithm = "wtf";
        $hash = "{".$algorithm."}B64STRING";

        Crypto::pwValid($hash, $algorithm);
    }

    /**
     * @covers \SimpleSAML\Utils\Crypto::secureCompare
     */
    public function testSecureCompareEqual()
    {
        $res = Crypto::secureCompare("string", "string");

        $this->assertTrue($res);
    }

    /**
     * @covers \SimpleSAML\Utils\Crypto::secureCompare
     */
    public function testSecureCompareNotEqual()
    {
        $res = Crypto::secureCompare("string1", "string2");

        $this->assertFalse($res);
    }

    /**
     * @expectedException \SimpleSAML_Error_Exception
     *
     * @covers \SimpleSAML\Utils\Crypto::loadPrivateKey
     */
    public function testLoadPrivateKeyRequiredMetadataMissing()
    {
        $config = new Configuration(array(), 'test');
        $required = true;

        Crypto::loadPrivateKey($config, $required);
    }

    /**
     * @covers \SimpleSAML\Utils\Crypto::loadPrivateKey
     */
    public function testLoadPrivateKeyNotRequiredMetadataMissing()
    {
        $config = new Configuration(array(), 'test');
        $required = false;

        $res = Crypto::loadPrivateKey($config, $required);

        $this->assertNull($res);
    }

    /**
     * @expectedException \SimpleSAML_Error_Exception
     *
     * @covers \SimpleSAML\Utils\Crypto::loadPrivateKey
     */
    public function testLoadPrivateKeyMissingFile()
    {
        $config = new Configuration(array('privatekey' => 'nonexistant'), 'test');

        Crypto::loadPrivateKey($config, false, '', true);
    }

    /**
     * @covers \SimpleSAML\Utils\Crypto::loadPrivateKey
     */
    public function testLoadPrivateKeyBasic()
    {
        $filename = $this->certdir.DIRECTORY_SEPARATOR.'key';
        $data = 'data';
        $config = new Configuration(array('privatekey' => $filename), 'test');
        $full_path = true;

        file_put_contents($filename, $data);

        $res = Crypto::loadPrivateKey($config, false, '', $full_path);
        $expected = array('PEM' => $data);

        $this->assertEquals($expected, $res);
    }

    /**
     * @covers \SimpleSAML\Utils\Crypto::loadPrivateKey
     */
    public function testLoadPrivateKeyPassword()
    {
        $password = 'password';
        $filename = $this->certdir.DIRECTORY_SEPARATOR.'key';
        $data = 'data';
        $config = new Configuration(
            array(
                'privatekey' => $filename,
                'privatekey_pass' => $password,
            ),
            'test'
        );
        $full_path = true;

        file_put_contents($filename, $data);

        $res = Crypto::loadPrivateKey($config, false, '', $full_path);
        $expected = array('PEM' => $data, 'password' => $password);

        $this->assertEquals($expected, $res);
    }

    /**
     * @covers \SimpleSAML\Utils\Crypto::loadPrivateKey
     */
    public function testLoadPrivateKeyPrefix()
    {
        $prefix = 'prefix';
        $password = 'password';
        $filename = $this->certdir.DIRECTORY_SEPARATOR.'key';
        $data = 'data';
        $config = new Configuration(
            array(
                $prefix.'privatekey' => $filename,
                $prefix.'privatekey_pass' => $password,
            ),
            'test'
        );
        $full_path = true;

        file_put_contents($filename, $data);

        $res = Crypto::loadPrivateKey($config, false, $prefix, $full_path);
        $expected = array('PEM' => $data, 'password' => $password);

        $this->assertEquals($expected, $res);
    }

    /**
     * @expectedException \SimpleSAML_Error_Exception
     *
     * @covers \SimpleSAML\Utils\Crypto::loadPublicKey
     */
    public function testLoadPublicKeyRequiredMetadataMissing()
    {
        $config = new Configuration(array(), 'test');
        $required = true;

        Crypto::loadPublicKey($config, $required);
    }

    /**
     * @covers \SimpleSAML\Utils\Crypto::loadPublicKey
     */
    public function testLoadPublicKeyNotRequiredMetadataMissing()
    {
        $config = new Configuration(array(), 'test');
        $required = false;

        $res = Crypto::loadPublicKey($config, $required);

        $this->assertNull($res);
    }

    /**
     * @covers \SimpleSAML\Utils\Crypto::loadPublicKey
     */
    public function testLoadPublicKeyFingerprintBasicString()
    {
        $fingerprint = 'fingerprint';
        $config = new Configuration(array('certFingerprint' => $fingerprint), 'test');

        $res = Crypto::loadPublicKey($config);
        $expected = array('certFingerprint' => array($fingerprint));

        $this->assertEquals($expected, $res);
    }

    /**
     * @covers \SimpleSAML\Utils\Crypto::loadPublicKey
     */
    public function testLoadPublicKeyFingerprintBasicArray()
    {
        $fingerprint1 = 'fingerprint1';
        $fingerprint2 = 'fingerprint2';
        $config = new Configuration(
            array(
                'certFingerprint' => array(
                    $fingerprint1,
                    $fingerprint2
                ),
            ),
            'test'
        );

        $res = Crypto::loadPublicKey($config);
        $expected = array('certFingerprint' => array($fingerprint1, $fingerprint2));

        $this->assertEquals($expected, $res);
    }

    /**
     * @covers \SimpleSAML\Utils\Crypto::loadPublicKey
     */
    public function testLoadPublicKeyFingerprintLowercase()
    {
        $fingerprint = 'FINGERPRINT';
        $config = new Configuration(array('certFingerprint' => $fingerprint), 'test');

        $res = Crypto::loadPublicKey($config);
        $expected = array('certFingerprint' => array(strtolower($fingerprint)));

        $this->assertEquals($expected, $res);
    }

    /**
     * @covers \SimpleSAML\Utils\Crypto::loadPublicKey
     */
    public function testLoadPublicKeyFingerprintRemoveColons()
    {
        $fingerprint = 'f:i:n:g:e:r:p:r:i:n:t';
        $config = new Configuration(array('certFingerprint' => $fingerprint), 'test');

        $res = Crypto::loadPublicKey($config);
        $expected = array('certFingerprint' => array(str_replace(':', '', $fingerprint)));

        $this->assertEquals($expected, $res);
    }

    /**
     * @covers \SimpleSAML\Utils\Crypto::loadPublicKey
     */
    public function testLoadPublicKeyNotX509Certificate()
    {
        $config = new Configuration(
            array(
                'keys' => array(
                    array(
                        'X509Certificate' => '',
                        'type' => 'NotX509Certificate',
                        'signing' => true
                    ),
                ),
            ),
            'test'
        );

        $res = Crypto::loadPublicKey($config);

        $this->assertNull($res);
    }

    /**
     * @covers \SimpleSAML\Utils\Crypto::loadPublicKey
     */
    public function testLoadPublicKeyNotSigning()
    {
        $config = new Configuration(
            array(
                'keys' => array(
                    array(
                        'X509Certificate' => '',
                        'type' => 'X509Certificate',
                        'signing' => false
                    ),
                ),
            ),
            'test'
        );

        $res = Crypto::loadPublicKey($config);

        $this->assertNull($res);
    }

    /**
     * @covers \SimpleSAML\Utils\Crypto::loadPublicKey
     */
    public function testLoadPublicKeyBasic()
    {
        $x509certificate = 'x509certificate';
        $config = new Configuration(
            array(
                'keys' => array(
                    array(
                        'X509Certificate' => $x509certificate,
                        'type' => 'X509Certificate',
                        'signing' => true
                    ),
                ),
            ),
            'test'
        );

        $pubkey = Crypto::loadPublicKey($config);
        $res = $pubkey['certData'];
        $expected = $x509certificate;

        $this->assertEquals($expected, $res);
    }
}
