<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils;

use InvalidArgumentException;
use org\bovigo\vfs\{vfsStream, vfsStreamDirectory};
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Utils;

/**
 * Tests for SimpleSAML\Utils\Crypto.
 */
#[CoversClass(Utils\Crypto::class)]
class CryptoTest extends TestCase
{
    private const ROOTDIRNAME = 'testdir';

    private const DEFAULTCERTDIR = 'certdir';

    /** @var \org\bovigo\vfs\vfsStreamDirectory */
    protected VfsStreamDirectory $root;

    /** @var string */
    protected string $root_directory;

    /** @var string */
    protected string $certdir;

    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Utils\Crypto */
    protected $cryptoUtils;

    /** @var string */
    protected $pem = <<<PHP
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

    /**
     */
    public function setUp(): void
    {
        $this->config = Configuration::loadFromArray(
            [
                'module.enable' => [],
                'secretsalt' => 'SUPER_SECRET_SALT',
            ],
            '[ARRAY]',
            'simplesaml',
        );

        $this->root = vfsStream::setup(
            self::ROOTDIRNAME,
            null,
            [
                self::DEFAULTCERTDIR => [],
            ],
        );
        $this->root_directory = vfsStream::url(self::ROOTDIRNAME);
        $this->certdir = $this->root_directory . DIRECTORY_SEPARATOR . self::DEFAULTCERTDIR;
        $this->cryptoUtils = new Utils\Crypto();
    }


    /**
     * Test that aesDecrypt() works properly, being able to decrypt some previously known (and correct)
     * ciphertext.
     * @deprecated
     */
    public function testAesDecrypt(): void
    {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('The openssl PHP module is not loaded.');
        }

        $plaintext = 'SUPER_SECRET_TEXT';
        $ciphertext = <<<CIPHER
uR2Yu0r4itInKx91D/l9y/08L5CIQyev9nAr27fh3Sshous4vbXRRcMcjqHDOrquD+2vqLyw7ygnbA9jA9TpB4hLZocvAWcTN8tyO82hiSY=
CIPHER;

        $decrypted = $this->cryptoUtils->aesDecrypt(base64_decode($ciphertext, true));
        $this->assertEquals($plaintext, $decrypted);
    }


    /**
     * @return void
     * @deprecated
     */
    public function testAesDecryptWithSmallCipherTextThrowsException(): void
    {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('The openssl PHP module is not loaded.');
        }

        $secret = 'SUPER_SECRET_SALT';
        $plaintext = 'SUPER_SECRET_TEXT';
        // This is too small!
        $ciphertext = 'AWcTN8tyO82hiSY=';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Input parameter "$ciphertext" must be a string with more than 48 characters.');
        $this->cryptoUtils->aesDecrypt(base64_decode($ciphertext), $secret);
    }


    /**
     * @return void
     * @deprecated
     */
    public function testAesDecryptWithWrongSecretThrowsException(): void
    {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('The openssl PHP module is not loaded.');
        }

        // This is the wrong secret!
        $secret = 'notsosecret';
        $plaintext = 'SUPER_SECRET_TEXT';
        $ciphertext = <<<CIPHER
uR2Yu0r4itInKx91D/l9y/08L5CIQyev9nAr27fh3Sshous4vbXRRcMcjqHDOrquD+2vqLyw7ygnbA9jA9TpB4hLZocvAWcTN8tyO82hiSY=
CIPHER;

        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage('Failed to decrypt ciphertext.');
        $this->cryptoUtils->aesDecrypt(base64_decode($ciphertext), $secret);
    }


    /**
     * Test that aesEncrypt() produces ciphertexts that aesDecrypt() can decrypt.
     *
     * @deprecated
     */
    public function testAesEncrypt(): void
    {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('The openssl PHP module is not loaded.');
        }

        $original_plaintext = 'SUPER_SECRET_TEXT';

        $ciphertext = $this->cryptoUtils->aesEncrypt($original_plaintext);
        $decrypted_plaintext = $this->cryptoUtils->aesDecrypt($ciphertext);

        $this->assertEquals($original_plaintext, $decrypted_plaintext);
    }


    /**
     * Test that the pem2der() and der2pem() methods work correctly.
     *
     */
    public function testFormatConversion(): void
    {
        $this->assertEquals(
            trim($this->pem),
            trim($this->cryptoUtils->der2pem($this->cryptoUtils->pem2der($this->pem))),
        );
    }


    /**
     * @return void
     */
    public function testFormatConversionThrowsExceptionWhenNotPEMStart(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('pem2der: input is not encoded in PEM format.');
        $this->cryptoUtils->pem2der(substr($this->pem, 6));
    }


    /**
     * @return void
     */
    public function testFormatConversionThrowsExceptionWhenNotPEMEnd(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('pem2der: input is not encoded in PEM format.');
        $this->cryptoUtils->pem2der(substr($this->pem, 0, -20));
    }


    /**
     * @deprecated
     */
    public function testGoodPwValid(): void
    {
        $pw = "password";

        $hash = $this->cryptoUtils->pwHash($pw);
        $res = $this->cryptoUtils->pwValid($hash, $pw);

        $this->assertTrue($res);
    }


    /**
     * @deprecated
     */
    public function testBadPwInvalid(): void
    {
        $pw = "password";
        $pw2 = "password2";

        $hash = $this->cryptoUtils->pwHash($pw);
        $res = $this->cryptoUtils->pwValid($hash, $pw2);

        $this->assertFalse($res);
    }

    /**
     * Check that hash cannot be used to authenticate ith.
     * @deprecated
     */
    public function testHashAsPwInvalid(): void
    {
        $pw = "password";

        $hash = $this->cryptoUtils->pwHash($pw);
        $this->expectException(Error\Exception::class);
        $this->cryptoUtils->pwValid($hash, $hash);
    }


    /**
     * @deprecated
     */
    public function testSecureCompareEqual(): void
    {
        $res = $this->cryptoUtils->secureCompare("string", "string");

        $this->assertTrue($res);
    }


    /**
     * @deprecated
     */
    public function testSecureCompareNotEqual(): void
    {
        $res = $this->cryptoUtils->secureCompare("string1", "string2");

        $this->assertFalse($res);
    }


    /**
     */
    public function testLoadPrivateKeyRequiredMetadataMissing(): void
    {
        $this->expectException(Error\Exception::class);
        $config = new Configuration([], 'test');
        $required = true;

        $this->cryptoUtils->loadPrivateKey($config, $required);
    }


    /**
     */
    public function testLoadPrivateKeyNotRequiredMetadataMissing(): void
    {
        $config = new Configuration([], 'test');
        $required = false;

        $res = $this->cryptoUtils->loadPrivateKey($config, $required);

        $this->assertNull($res);
    }


    /**
     */
    public function testLoadPrivateKeyMissingFile(): void
    {
        $this->expectException(Error\Exception::class);
        $config = new Configuration(['privatekey' => 'nonexistent'], 'test');

        $this->cryptoUtils->loadPrivateKey($config, false, '', true);
    }


    /**
     */
    public function testLoadPrivateKeyBasic(): void
    {
        $filename = $this->certdir . DIRECTORY_SEPARATOR . 'key';
        $data = 'data';
        $config = new Configuration(['privatekey' => $filename], 'test');
        $full_path = true;

        file_put_contents($filename, $data);

        $res = $this->cryptoUtils->loadPrivateKey($config, false, '', $full_path);
        $expected = ['PEM' => $data, 'password' => null];

        $this->assertEquals($expected, $res);
    }


    /**
     */
    public function testLoadPrivateKeyPassword(): void
    {
        $password = 'password';
        $filename = $this->certdir . DIRECTORY_SEPARATOR . 'key';
        $data = 'data';
        $config = new Configuration(
            [
                'privatekey' => $filename,
                'privatekey_pass' => $password,
            ],
            'test',
        );
        $full_path = true;

        file_put_contents($filename, $data);

        $res = $this->cryptoUtils->loadPrivateKey($config, false, '', $full_path);
        $expected = ['PEM' => $data, 'password' => $password];

        $this->assertEquals($expected, $res);
    }


    /**
     */
    public function testLoadPrivateKeyPrefix(): void
    {
        $prefix = 'prefix';
        $password = 'password';
        $filename = $this->certdir . DIRECTORY_SEPARATOR . 'key';
        $data = 'data';
        $config = new Configuration(
            [
                $prefix . 'privatekey' => $filename,
                $prefix . 'privatekey_pass' => $password,
            ],
            'test',
        );
        $full_path = true;

        file_put_contents($filename, $data);

        $res = $this->cryptoUtils->loadPrivateKey($config, false, $prefix, $full_path);
        $expected = ['PEM' => $data, 'password' => $password];

        $this->assertEquals($expected, $res);
    }


    /**
     */
    public function testLoadPublicKeyRequiredMetadataMissing(): void
    {
        $this->expectException(Error\Exception::class);
        $config = new Configuration([], 'test');
        $required = true;

        $this->cryptoUtils->loadPublicKey($config, $required);
    }


    /**
     */
    public function testLoadPublicKeyNotRequiredMetadataMissing(): void
    {
        $config = new Configuration([], 'test');
        $required = false;

        $res = $this->cryptoUtils->loadPublicKey($config, $required);

        $this->assertNull($res);
    }


    /**
     */
    public function testLoadPublicKeyNotX509Certificate(): void
    {
        $config = new Configuration(
            [
                'keys' => [
                    [
                        'X509Certificate' => '',
                        'type' => 'NotX509Certificate',
                        'signing' => true,
                    ],
                ],
            ],
            'test',
        );

        $res = $this->cryptoUtils->loadPublicKey($config);

        $this->assertNull($res);
    }


    /**
     */
    public function testLoadPublicKeyNotSigning(): void
    {
        $config = new Configuration(
            [
                'keys' => [
                    [
                        'X509Certificate' => '',
                        'type' => 'X509Certificate',
                        'signing' => false,
                    ],
                ],
            ],
            'test',
        );

        $res = $this->cryptoUtils->loadPublicKey($config);

        $this->assertNull($res);
    }


    /**
     */
    public function testLoadPublicKeyBasic(): void
    {
        $x509certificate = 'x509certificate';
        $config = new Configuration(
            [
                'keys' => [
                    [
                        'X509Certificate' => $x509certificate,
                        'type' => 'X509Certificate',
                        'signing' => true,
                    ],
                ],
            ],
            'test',
        );

        /** @var array $pubkey */
        $pubkey = $this->cryptoUtils->loadPublicKey($config);
        $res = $pubkey['certData'];
        $expected = $x509certificate;

        $this->assertEquals($expected, $res);
    }
}
