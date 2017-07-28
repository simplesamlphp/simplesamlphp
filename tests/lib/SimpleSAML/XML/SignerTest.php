<?php

namespace SimpleSAML\Test\Utils;

use \SimpleSAML_Configuration as Configuration;
use \SimpleSAML\XML\Signer;

use \org\bovigo\vfs\vfsStream;

/**
 * Tests for SimpleSAML\XML\Signer.
 */
class SignerTest extends \PHPUnit_Framework_TestCase
{
    // openssl genrsa -out private.pem 2048
    private $private_key = <<<'NOWDOC'
-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA5LoQYYPfKdHnSnuXI+SiHfUd648Ub0sn2YO81rmnwJ168Ol/
FZODrGpm8tsRUTz5R9uXXSnwhnWwVJW4ckiZORcp1bEUGI0zXYR387yF3Ih87UFV
KdqodrDXNN6Id7Xrw65AVa4gjwLN2DNBF3JnjbH7zKtnqhb7u2Qer7Lidhvw4WxY
lC9t8c+Kv3xoJOgDvlG1gRaYTZv7pxTpBA7W1YnJpOj3xiXetVmAxRcGyB0Jc8aB
nc1WoUBGudSvjvuc01kJ+rurjgklGEFjVP9AjPfcVkdcFTXc+ECets++AmZc/kk4
Y6RKCn3fOJlL5L0RxVSJ8obnBcS7H4rZYordfwIDAQABAoIBAH364cTkPompPIyw
0AmMB6MafFVfZHD8Y0GSJvPaJESaOLny0fWPX4oavQNsl/g37lGe6Jr+26Ujs3CT
WplP1V01new+cYQoWa9bpDoSj2RtpOmE/6Ri9EETnCVZoK7W+7m3A2Zt1y8N61T2
vhZtBA5uhvMvQZTUvehz99bsX4GPTUilYHCPEq4IPkfhCMGigv/c0lWtFQhOoNUF
BjZHezH4Z/qQolIaHpzFZT0K0e7VD4gomBegGsIqPuEJ0gProCjULqA0O5QT4gQX
IT52pUJuU0061d4JOfDcgDI3NT2SmBBMfig71n/R88eMn0azWKN4rn4/3QjxRW3q
tdjL0UECgYEA/ynTXtuL7G5zOezKirakuSlSbHu/3TJ+tdG5p7WOLqWADUzgqss+
k7rxxFUxw40dBpC0LfYP5YMhXi4cBiNoT5EWhT53x/UxCilXHuz5uYcrt/Wyaqa0
mZuyIPYuw/yTASEBUE/sE1DU82PD3IlkPmqfgEyW6j8CVyLqo/LxMWECgYEA5XoM
aVB5jhYk8jxy0APWn4jSTm2zpTBZpzHmqTPL19B4Es18XoU+ehWA8rWGQFFwbl1f
TTUBE1hlS9MgMMI8MK6S1Qrhi7mVrHuMaMbp0ilwDBjv+4DSqlDGDoCSLCLrDkkl
c0uDLLFGHkfDjNmk3uiSxPZvrUiVVuwJYLGNGt8CgYEAyvjWbsptz7E8b4Nwyk7n
UXMRYcI+qRIVwUQHTuUZKPn1lp7kyHfMW2+GCgtK/qctw58v9K+bjZJ15JkBKdDY
lRJwu6UpWyIr1E12Q9919qMTn84OEtBxMQ+s7pNmN/ieZ3N9vAkXXXYbL1DY6IFS
AGSIZGKIWeWtUusvgyMpwYECgYEArGDIHfxTs0YzLrv1ywh3GpQe1sdVYUs2rX+w
s32zLETvTcCKIj6ZNgAdQzTUyk/i0yTUyBx+2FdYkGLiFX5y1Gbu6ZYo41rfchfE
25hAYJy8DHpXG2gj18ihXpd6NilsxOhxd3BL8zCfaXOjE5USYlf2mHo+Xb7eX9Mj
ID1/r6UCgYBos8plM27v5BzI8gghUlkFAFLmmccJXQHCUlUhT1+d8FTMEhTZGjZk
94a7cc/ps+6UCp6hOqJ2d6w+cfteWZWP0zMcoxr2JAO9lYekIlUafoZ+mhJCCqoC
ENg4/K7BqpAlRzCf28gUiL53wOut2CadGIoSvj0UR/Mh2eM64jTgSQ==
-----END RSA PRIVATE KEY-----
NOWDOC;

    // openssl req -new -x509 -key private.pem -out public1.pem -days 3650
    private $certificate1 = <<<'NOWDOC'
-----BEGIN CERTIFICATE-----
MIIDXTCCAkWgAwIBAgIJAIonjtIRUcfJMA0GCSqGSIb3DQEBCwUAMEUxCzAJBgNV
BAYTAkFVMRMwEQYDVQQIDApTb21lLVN0YXRlMSEwHwYDVQQKDBhJbnRlcm5ldCBX
aWRnaXRzIFB0eSBMdGQwHhcNMTcwNjE1MTcyMTI4WhcNMjcwNjEzMTcyMTI4WjBF
MQswCQYDVQQGEwJBVTETMBEGA1UECAwKU29tZS1TdGF0ZTEhMB8GA1UECgwYSW50
ZXJuZXQgV2lkZ2l0cyBQdHkgTHRkMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIB
CgKCAQEA5LoQYYPfKdHnSnuXI+SiHfUd648Ub0sn2YO81rmnwJ168Ol/FZODrGpm
8tsRUTz5R9uXXSnwhnWwVJW4ckiZORcp1bEUGI0zXYR387yF3Ih87UFVKdqodrDX
NN6Id7Xrw65AVa4gjwLN2DNBF3JnjbH7zKtnqhb7u2Qer7Lidhvw4WxYlC9t8c+K
v3xoJOgDvlG1gRaYTZv7pxTpBA7W1YnJpOj3xiXetVmAxRcGyB0Jc8aBnc1WoUBG
udSvjvuc01kJ+rurjgklGEFjVP9AjPfcVkdcFTXc+ECets++AmZc/kk4Y6RKCn3f
OJlL5L0RxVSJ8obnBcS7H4rZYordfwIDAQABo1AwTjAdBgNVHQ4EFgQUZHjC+k2X
pMchyKojQngj5zOsZacwHwYDVR0jBBgwFoAUZHjC+k2XpMchyKojQngj5zOsZacw
DAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQsFAAOCAQEAETjO0RltSYxFdxmIqVIg
7N6yKptUr46YkWY877HWmCLExHwFLTvewUvbgx7ASYA0YMErnAaVrT9IqCDbOUF+
RCBovVuiAwwKcvag0C8nKg7rfx7KDr2E8vVV+2WzSpDECtLrpTmrPaje8TlFv8NW
hMk80osVxnGmI7UewiMzfpRuA4tEKFxHhoQG5LVinWRTMKw6EYmrSKGLdQt/27zj
xDe0oOS2DDIYbU/oWCqLtlTlzVqrNM7ig9HKcT0Xxgf5rwTDDzNf/dpM/Nt8DWFY
YmLDnUolf8d/M/kglX1x5IRSN+GxTCgV8i6dIF9EPtBW/AfMz99ojmW+WOgfOLnm
vg==
-----END CERTIFICATE-----
NOWDOC;

    // openssl req -new -x509 -key private.pem -out public2.pem -days 3650
    private $certificate2 = <<<'NOWDOC'
-----BEGIN CERTIFICATE-----
MIIDXTCCAkWgAwIBAgIJAJ6gIIeYjdQSMA0GCSqGSIb3DQEBCwUAMEUxCzAJBgNV
BAYTAkFVMRMwEQYDVQQIDApTb21lLVN0YXRlMSEwHwYDVQQKDBhJbnRlcm5ldCBX
aWRnaXRzIFB0eSBMdGQwHhcNMTcwNjE1MTcyMTM0WhcNMjcwNjEzMTcyMTM0WjBF
MQswCQYDVQQGEwJBVTETMBEGA1UECAwKU29tZS1TdGF0ZTEhMB8GA1UECgwYSW50
ZXJuZXQgV2lkZ2l0cyBQdHkgTHRkMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIB
CgKCAQEA5LoQYYPfKdHnSnuXI+SiHfUd648Ub0sn2YO81rmnwJ168Ol/FZODrGpm
8tsRUTz5R9uXXSnwhnWwVJW4ckiZORcp1bEUGI0zXYR387yF3Ih87UFVKdqodrDX
NN6Id7Xrw65AVa4gjwLN2DNBF3JnjbH7zKtnqhb7u2Qer7Lidhvw4WxYlC9t8c+K
v3xoJOgDvlG1gRaYTZv7pxTpBA7W1YnJpOj3xiXetVmAxRcGyB0Jc8aBnc1WoUBG
udSvjvuc01kJ+rurjgklGEFjVP9AjPfcVkdcFTXc+ECets++AmZc/kk4Y6RKCn3f
OJlL5L0RxVSJ8obnBcS7H4rZYordfwIDAQABo1AwTjAdBgNVHQ4EFgQUZHjC+k2X
pMchyKojQngj5zOsZacwHwYDVR0jBBgwFoAUZHjC+k2XpMchyKojQngj5zOsZacw
DAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQsFAAOCAQEA1CqpKLeYLkgRym2qeMhU
5lKlXAYX5b0eM2SOCCjfpEnRqp2PTU/E83H0MOY6i47OfHp3LKNUj4Kze2DD+S6A
llpmLfuLXZ/CB19sByzMrcEyUQo4mfqvKyzLhUTgygGczyocwRRZgnw1e+VwMtpf
mgXnldomDT8CUsM2v3Xb52+JPGSCs16lRYZkgDCQEpHU4+VQxwGAGpj13NM+sidR
ymj443jgpF6XUviaGiaS292rXMO/tW7veA1UZ2/eTKu5PF9RqDmYLiGatY1qp4tr
QjBeEjMtDCs9Rqaety/UIaL4ZfOKffLKsKb2mjM/ew+QTwTLDg9RVv5vv2jbZrw7
Nw==
-----END CERTIFICATE-----
NOWDOC;

    const ROOTDIRNAME = 'testdir';
    const DEFAULTCERTDIR = 'certdir';
    const PRIVATEKEY = 'privatekey.pem';
    const CERTIFICATE1 = 'certificate1.pem';
    const CERTIFICATE2 = 'certificate2.pem';

    public function setUp()
    {
        $this->root = vfsStream::setup(
            self::ROOTDIRNAME,
            null,
            array(
                self::DEFAULTCERTDIR => array(
                    self::PRIVATEKEY => $this->private_key,
                    self::CERTIFICATE1 => $this->certificate1,
                    self::CERTIFICATE2 => $this->certificate2,
                ),
            )
        );
        $this->root_directory = vfsStream::url(self::ROOTDIRNAME);

        $this->certdir = $this->root_directory.DIRECTORY_SEPARATOR.self::DEFAULTCERTDIR;
        $this->privatekey_file = $this->certdir.DIRECTORY_SEPARATOR.self::PRIVATEKEY;
        $this->certificate_file1 = $this->certdir.DIRECTORY_SEPARATOR.self::CERTIFICATE1;
        $this->certificate_file2 = $this->certdir.DIRECTORY_SEPARATOR.self::CERTIFICATE2;

        $this->config = Configuration::loadFromArray(array(
            'certdir' => $this->certdir,
        ), '[ARRAY]', 'simplesaml');
    }

    public function tearDown()
    {
        $this->clearInstance($this->config, '\SimpleSAML_Configuration', array());
    }

    public function testSignerBasic()
    {
        $res = new Signer(array());

        $this->assertNotNull($res);
    }

    public function testSignBasic()
    {
        $node = new \DOMDocument();
        $node->loadXML('<?xml version="1.0"?><node>value</node>');
        $element = $node->getElementsByTagName("node")->item(0);

        $doc = new \DOMDocument();
        $insertInto = $doc->appendChild(new \DOMElement('insert'));

        $signer = new Signer(array());
        $signer->loadPrivateKey($this->privatekey_file, null, true);
        $signer->sign($element, $insertInto);

        $res = $doc->saveXML();

        $this->assertContains('DigestValue', $res);
        $this->assertContains('SignatureValue', $res);
    }

    private static function getCertificateValue($certificate)
    {
        $replacements = array(
            "-----BEGIN CERTIFICATE-----",
            "-----END CERTIFICATE-----",
            "\n",
        );

        return str_replace($replacements, "", $certificate);
    }

    public function testSignWithCertificate()
    {
        $node = new \DOMDocument();
        $node->loadXML('<?xml version="1.0"?><node>value</node>');
        $element = $node->getElementsByTagName("node")->item(0);

        $doc = new \DOMDocument();
        $insertInto = $doc->appendChild(new \DOMElement('insert'));

        $signer = new Signer(array());
        $signer->loadPrivateKey($this->privatekey_file, null, true);
        $signer->loadCertificate($this->certificate_file1, true);
        $signer->sign($element, $insertInto);

        $res = $doc->saveXML();

        $expected = self::getCertificateValue($this->certificate1);

        $this->assertContains('X509Certificate', $res);
        $this->assertContains($expected, $res);
    }

    public function testSignWithMultiCertificate()
    {
        $node = new \DOMDocument();
        $node->loadXML('<?xml version="1.0"?><node>value</node>');
        $element = $node->getElementsByTagName("node")->item(0);

        $doc = new \DOMDocument();
        $insertInto = $doc->appendChild(new \DOMElement('insert'));

        $signer = new Signer(array());
        $signer->loadPrivateKey($this->privatekey_file, null, true);
        $signer->loadCertificate($this->certificate_file1, true);
        $signer->addCertificate($this->certificate_file2, true);
        $signer->sign($element, $insertInto);

        $res = $doc->saveXML();

        $expected1 = self::getCertificateValue($this->certificate1);
        $expected2 = self::getCertificateValue($this->certificate2);

        $this->assertContains('X509Certificate', $res);
        $this->assertContains($expected1, $res);
        $this->assertContains($expected2, $res);
    }

    public function testSignMissingPrivateKey()
    {
        $node = new \DOMDocument();
        $node->loadXML('<?xml version="1.0"?><node>value</node>');
        $element = $node->getElementsByTagName("node")->item(0);

        $doc = new \DOMDocument();
        $insertInto = $doc->appendChild(new \DOMElement('insert'));

        $signer = new Signer(array());

        $this->setExpectedException('\Exception');
        $signer->sign($element, $insertInto);
    }

    protected function clearInstance($service, $className, $value = null)
    {
        $reflectedClass = new \ReflectionClass($className);
        $reflectedInstance = $reflectedClass->getProperty('instance');
        $reflectedInstance->setAccessible(true);
        $reflectedInstance->setValue($service, $value);
        $reflectedInstance->setAccessible(false);
    }
}
