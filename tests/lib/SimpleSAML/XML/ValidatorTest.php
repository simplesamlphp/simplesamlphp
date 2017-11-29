<?php

namespace SimpleSAML\Test\XML;

use PHPUnit\Framework\TestCase;
use \SimpleSAML_Configuration as Configuration;
use \SimpleSAML\XML\Signer;
use \SimpleSAML\XML\Validator;

use \org\bovigo\vfs\vfsStream;

/**
 * Tests for SimpleSAML\XML\Validator.
 */
class ValidatorTest extends TestCase
{
    // openssl genrsa -out ca.key.pem 2048
    private $ca_private_key = <<<'NOWDOC'
-----BEGIN RSA PRIVATE KEY-----
MIIEpQIBAAKCAQEAtj5GuvnC5aCg8bhq2Yy4isp/uXtRRWKhbB5aYP7/1DwwwQ1Z
LtBosBAA5SMD4s4L9w/bbJVVVAzhc9cpe2vDYLe1faUZlvOzJv/JuH/ux5NRkgmx
2qBic1zEEu2KuCQRvNgu4kAbWRS6bxxQWJuhJy5ZJpXRDZOeb8t8JAn6LL6rfMfS
rwGP2ITaK2lrqvzOaoktHkstGVOg3yn5K15lCKSSBI3qmlYtcU5VnL/kSPY2Gda6
eF3gDSAflymNhHmaEx6LZM6HWNtJdUjGrcivjynpIdo7DMfL3OTXV8vM0Ad4A55e
x0020Cn/gXCShwIs9lUrmjUzX/DJ0Rc4vVzGVwIDAQABAoIBAQCOViGEE2KHWmeZ
o0HA3EmeDP6o7YnSOXB+M06/hypkpwYlIbnP+HJrYHRygmCcmfV6Z2YnbpMQbGcB
xMDfZpFYTuNvWK2d2oTIJut0MGdcdNE20F+as71xALkbV3AK1hEMf4ROrWcusiPS
eDjAm/zHz0lN+6Eli3ApPLKeqzQ8EPQhCVkcj2+3WoiL/lE5ImSVN2fiW0tZff/D
4T42teZWVihnrooovpZ+1/RiOc+rSMdStvIadr9TfUe0s74+3p2XmeKDd/0VgJI/
G2Lr6AFy5YUahNKfh3XlHwnn9eizl3oUU0wzC4OrvSuXrzAQalF6T8ULHgC5+JuC
IV+oE1fhAoGBAPCA8aS0lXOiwDUUpZB7fU/h1ZWVksvHs1TtoWooGcNxTqb3FbTE
seVURBIsrA+qYoz2YGCqoTsbz8743wmoN10b85uoWIjh2ZKPMPMumeMHTMbo7MJ+
m1bE+m1kQqioYvF86Dp39DjkvuAYDZEUA7SWOqLQ5XFuOgLC8e5jeDDJAoGBAMH8
VuJRZ0HqmKnRTT9n2vh/0bRkpPx/Umi40qiPWxeVDXHUjJ3d1xlxint7cxjc1OKv
0cBvRtz3fg0rgy+TA7BQ4oauQSgjiRzbuNmcrR3g4iAC9pZzp+8dJwZ6p1DwiWZU
Eh1GvJh0obshRetBTvuWj/Mca0ahb3NeYhke1O4fAoGBAOBw9REoFXDcqVLf+cJj
/AXYU2JNO7lAnHLdcI2I7sIds7DNVUxlYz8I7J2pYskb0OyL4FVV5zEqOzyDtGFm
woP52dWhvT3AxzKmvp+zFZlw7o2SQaEgVgcbvDjqH+sVeYCzeGVYHGobzqWCzOZf
LzYQHJhlKjo5C5oYI369BSVBAoGAb1II5h2C3Q/shd9nrhBCV8K6LARprcC/IPuX
YEXMJ49QxNcNzvZknuRKbSxd05G+1UvCWeVBzEJ24sXqpZ1/S2pPZKyRFaC26Ymp
3a+MpQ3NlkM9EP/UCmM3Zv9yDv/KSZ/LOWPDjNW5jjK89hFnavdvKjtP0JuJ4rHy
J6pK1U8CgYEAjDKRReVFJLaWfj8dLskOg4eCMcUj1H1+ADeeXa0B9e75//24Dyrq
kQRHYowCvEG+j71Fzw8AcC80VzEYMKU50lXOnCb3mHaCJhm2TOK0QYsm36jTVW2w
mHreqJFXp12lURaL+esz01oaH49ZUzVeZVGmVyOzoSDYEOq9K7L/j14=
-----END RSA PRIVATE KEY-----
NOWDOC;

    // openssl req -key ca.key.pem -new -x509 -days 3650 -out ca.cert.pem
    private $ca_certificate = <<<'NOWDOC'
-----BEGIN CERTIFICATE-----
MIIDtjCCAp6gAwIBAgIJAII4rW68Q+IsMA0GCSqGSIb3DQEBCwUAMHAxCzAJBgNV
BAYTAkFVMRMwEQYDVQQIDApTb21lLVN0YXRlMSEwHwYDVQQKDBhJbnRlcm5ldCBX
aWRnaXRzIFB0eSBMdGQxKTAnBgNVBAMMIEludGVybmV0IFdpZGdpdHMgUHR5IEx0
ZCBSb290IENBMB4XDTE3MTAxMTIxMjIzOFoXDTI3MTAwOTIxMjIzOFowcDELMAkG
A1UEBhMCQVUxEzARBgNVBAgMClNvbWUtU3RhdGUxITAfBgNVBAoMGEludGVybmV0
IFdpZGdpdHMgUHR5IEx0ZDEpMCcGA1UEAwwgSW50ZXJuZXQgV2lkZ2l0cyBQdHkg
THRkIFJvb3QgQ0EwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQC2Pka6
+cLloKDxuGrZjLiKyn+5e1FFYqFsHlpg/v/UPDDBDVku0GiwEADlIwPizgv3D9ts
lVVUDOFz1yl7a8Ngt7V9pRmW87Mm/8m4f+7Hk1GSCbHaoGJzXMQS7Yq4JBG82C7i
QBtZFLpvHFBYm6EnLlkmldENk55vy3wkCfosvqt8x9KvAY/YhNoraWuq/M5qiS0e
Sy0ZU6DfKfkrXmUIpJIEjeqaVi1xTlWcv+RI9jYZ1rp4XeANIB+XKY2EeZoTHotk
zodY20l1SMatyK+PKekh2jsMx8vc5NdXy8zQB3gDnl7HTTbQKf+BcJKHAiz2VSua
NTNf8MnRFzi9XMZXAgMBAAGjUzBRMB0GA1UdDgQWBBQjqR1+FXBhfbKUUMfdjHp/
9fMvPTAfBgNVHSMEGDAWgBQjqR1+FXBhfbKUUMfdjHp/9fMvPTAPBgNVHRMBAf8E
BTADAQH/MA0GCSqGSIb3DQEBCwUAA4IBAQAuUyMn7wz8RUAjW5cbOTvLejYmaPKf
EzWMYhcRmCQcmqZJ3Sxy+VEBCZsHG+a5R0rXsQ1Iwrgpo7H4d5+CRS6rJcrKAKC+
1Izaolodnfbz1sQlmHxwkSwDqdb4pWujw7L0YBfvsUc5FGoKfdPUoa6qL/eP1pVH
0d9JC1ucX+0EmTX9a+3LH0t3evPP2yx53SjQiMoRf/ty7NwfIVxlqWyKFJnUYSF5
c2jGmls/F+PBVeW51bfK00DpdXLgbgWmNDdePf2fPvpkADGfo/DxLZOTtiY6ngtO
BdyrA5DmvSuL/Yfq03J9btXX4NnANQFVvfSbun7ts5F1qTkSe/vHCoke
-----END CERTIFICATE-----
NOWDOC;

    // openssl genrsa -out good.key.pem 2048
    private $good_private_key = <<<'NOWDOC'
-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEAqmNn4bt/jrMHgoWtwXLc2ok17BHh1O5ETbn9rK3KFjk3BXp5
3aGveill+KbW7SgriGZSa1KBE2uaQy2mZpiBQqFrLcgKhtzaCNLyBvKOozQhn/XN
6m2kN8EDZaGIGxtM/6ypUAnytscGo2bKzyHtOjYOPwEeALiq7+YrR1Bc2X05OyVu
dV8Wju8QUCm7No85/TOjxD6SrWUXuEPJm0RiyVMeZhuKmtxm0kB2ZtQ0lKViOxaL
iBRiW9TldY94NaHfgaZSpCmrikoiS4QJ4hTo4nEVpjx+1BDJIar3bfxH+vwuLlOo
Zg3KI9BYcWm5n+XKwTxnhaBWM8MH3PtmLNbrRwIDAQABAoIBAQCWNpbRogwdkOXR
AushPZNJMmgQW999aiVbZNooTvp3Ry+jq8seQjnCeBbG9MdMQvrGAHcu2Iikx1sz
WF532oEybb5gmRf2t2OdHeNzjomDhiUSsZA82TZKVOYaxiKIyzPBv8Vwo6SP0Lyg
d7HalC1hAWDj2KdLuYIxhvimoUmKDnPS80on0/vKxlevK/2er7nHCIIvHyCS0Q8G
5AB9LN1bSJIBKm71yHNAUxrv6MUNaLvyCKGv6dea/6ED+gusmcsb4TG7y2nbLOq0
CSx+YcNozrkAb21nJFKYWKmbMvHdtcBuU2dlgiUN3+rXSD45GlvDSPciqr8iCiUU
DjHUp8khAoGBAN6aNWAqUhpxMJ0ozJBDPZKnkFml4IjsqXFk5Z2nHwThHonKpIV2
KaXr4CYfsOQvddAPd9G+ziAaX5QcRBL/91tRGmJR8/kizPpTgxc3SwosJfIQnAkS
0GNnpM65DyDkvEXGsA7bhD8FXBDFFgB2Jd0tbTh30wyjgXlyrrWTDYx3AoGBAMPz
vZRQ/MdOY7DtFQ6Uz/GJ5xNojAE+9KYJskNBMAnQTkqUAqTQ8MNHvx1L9J9EhK1A
rVyrgXvANuIFhCEVMMIrZYweNFe0/nPjBRRcc0rhHw66VZWI4j5Q3tgvl2gwy+LN
zkvlTOexMu90V9idso+R76++mfK/LWVAf3e36duxAoGAKztX0m1ltKz2/A7Ia9wj
QTA54K9OhEkyP0uRLKUgaRovjCNHAISKYicFSWIuQKLXBql6Y8nizmlQ1rsGnYoI
yDtgHGg+McyIcrV1aDTc5gTc+b4wD7MPtb6TS3K1dXX2+rYzyy7m6DZqQveD5mML
x4DjDWx4GKRIqQWU2L7OitECgYAdEXlcGS+GeXB8fI8VHKpEUIrA7E9ol+g/AU06
gN8ZdZdHpPFHdd9heLE3LV9aiRWNhfyxtJd+viLmIJ9bMQOMqldkE877+9OLaXAF
dzl7MC4lRysPBcFaMTD9rQGu6R41xQYHaDqiXD0MHJwzfCFS/vkpfwLjaczYKls+
bT/54QKBgHsmjU3TqqFn5hTNTFcbwaRtuiSGSgX6Udgfmg2Vl+d0JsANgPd9X9s4
KXyBC+biIPnDkQEQ6GW+r1VkTl9KBvxqdaertwpErUF2/JkGMmuYQ1Lvsw/gXpvr
GcEpWSFVRCYKwN+P0FW0fgUaRAyFmoCIvQ3nGtJWH6I0KSS+76r0
-----END RSA PRIVATE KEY-----
NOWDOC;

    // openssl req -key good.key.pem -new -out good.csr.pem
    // openssl x509 \
    //      -req \
    //      -CA ca.cert.pem \
    //      -CAkey ca.key.pem \
    //      -CAcreateserial \
    //      -days 3650 \
    //      -in good.csr.pem \
    //      -out good.cert.pem
    private $good_certificate = <<<'NOWDOC'
-----BEGIN CERTIFICATE-----
MIIDZTCCAk0CCQC+sxqJmyko6TANBgkqhkiG9w0BAQsFADBwMQswCQYDVQQGEwJB
VTETMBEGA1UECAwKU29tZS1TdGF0ZTEhMB8GA1UECgwYSW50ZXJuZXQgV2lkZ2l0
cyBQdHkgTHRkMSkwJwYDVQQDDCBJbnRlcm5ldCBXaWRnaXRzIFB0eSBMdGQgUm9v
dCBDQTAeFw0xNzEwMTEyMTIzMTRaFw0yNzEwMDkyMTIzMTRaMHkxCzAJBgNVBAYT
AkFVMRMwEQYDVQQIDApTb21lLVN0YXRlMSEwHwYDVQQKDBhJbnRlcm5ldCBXaWRn
aXRzIFB0eSBMdGQxMjAwBgNVBAMMKUludGVybmV0IFdpZGdpdHMgUHR5IEx0ZCBU
ZXN0IENlcnRpZmljYXRlMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA
qmNn4bt/jrMHgoWtwXLc2ok17BHh1O5ETbn9rK3KFjk3BXp53aGveill+KbW7Sgr
iGZSa1KBE2uaQy2mZpiBQqFrLcgKhtzaCNLyBvKOozQhn/XN6m2kN8EDZaGIGxtM
/6ypUAnytscGo2bKzyHtOjYOPwEeALiq7+YrR1Bc2X05OyVudV8Wju8QUCm7No85
/TOjxD6SrWUXuEPJm0RiyVMeZhuKmtxm0kB2ZtQ0lKViOxaLiBRiW9TldY94NaHf
gaZSpCmrikoiS4QJ4hTo4nEVpjx+1BDJIar3bfxH+vwuLlOoZg3KI9BYcWm5n+XK
wTxnhaBWM8MH3PtmLNbrRwIDAQABMA0GCSqGSIb3DQEBCwUAA4IBAQAyWgO1+gyu
3ao9Om0/TaAgJzsb2dnrb91P4eLo285bPToOGekaJyP5up6xP6DsOnvPCkXIglld
PR8LyCWjHhIFL7bZod7cmXvBhedX7yxP9nwDwOvz9e9M117cVXfUQqZVktLiDxmg
FxNHi6lMlYtvvnHnjnjYtA2w7c0u0SBeqhXfctZxrzqP97BzUAQkk75ElDJM6lNw
FTVvRw8z7um+jeruCa6FcUVBxkKcUNvo3p6C2m+bntkqmMZji1YZ7j0kC/tnjr95
hQc0xnrLQ255SjMn+nQtMkVSuKwAUqaAP1ByyiVbN1cBlHnMiJCjvBI58bSTdlVK
0ZppWlc39T6m
-----END CERTIFICATE-----
NOWDOC;

    const ROOTDIRNAME = 'testdir';
    const DEFAULTCERTDIR = 'certdir';
    const CA_PRIVATE_KEY = 'ca.key.pem';
    const CA_CERTIFICATE = 'ca.cert.pem';
    const GOOD_PRIVATE_KEY = 'good.key.pem';
    const GOOD_CERTIFICATE = 'good.cert.pem';

    public function setUp()
    {
        $this->root = vfsStream::setup(
            self::ROOTDIRNAME,
            null,
            array(
                self::DEFAULTCERTDIR => array(
                    self::CA_PRIVATE_KEY => $this->ca_private_key,
                    self::CA_CERTIFICATE => $this->ca_certificate,
                    self::GOOD_PRIVATE_KEY => $this->good_private_key,
                    self::GOOD_CERTIFICATE => $this->good_certificate,
                ),
            )
        );
        $this->root_directory = vfsStream::url(self::ROOTDIRNAME);

        $this->certdir = $this->root_directory.DIRECTORY_SEPARATOR.self::DEFAULTCERTDIR;
        $this->ca_private_key_file = $this->certdir.DIRECTORY_SEPARATOR.self::CA_PRIVATE_KEY;
        $this->ca_certificate_file = $this->certdir.DIRECTORY_SEPARATOR.self::CA_CERTIFICATE;
        $this->good_private_key_file = $this->certdir.DIRECTORY_SEPARATOR.self::GOOD_PRIVATE_KEY;
        $this->good_certificate_file = $this->certdir.DIRECTORY_SEPARATOR.self::GOOD_CERTIFICATE;

        $this->config = Configuration::loadFromArray(array(
            'certdir' => $this->certdir,
        ), '[ARRAY]', 'simplesaml');
    }

    public function tearDown()
    {
        $this->clearInstance($this->config, '\SimpleSAML_Configuration', array());
    }

    public function testValidatorMissingSignature()
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<?xml version="1.0"?><node>value</node>');

        $this->setExpectedException('\Exception');
        new Validator($doc);
    }

    public function testGetX509Certificate()
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<?xml version="1.0"?><node>value</node>');

        $node = $doc->getElementsByTagName('node')->item(0);

        $signature_parent = $doc->appendChild(new \DOMElement('signature_parent'));

        $signer = new Signer(array());
        $signer->loadPrivateKey($this->good_private_key_file, null, true);
        $signer->loadCertificate($this->good_certificate_file, true);
        $signer->sign($node, $signature_parent);

        $validator = new Validator($doc, 'node');

        $result = $validator->getX509Certificate();

        // getX509Certificate returns a certificate with a newline
        $expected = $this->good_certificate . "\n";

        $this->assertEquals($result, $expected);
    }

    public function testCertFingerprintSuccess()
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<?xml version="1.0"?><node>value</node>');

        $node = $doc->getElementsByTagName('node')->item(0);

        $signature_parent = $doc->appendChild(new \DOMElement('signature_parent'));

        $signer = new Signer(array());
        $signer->loadPrivateKey($this->good_private_key_file, null, true);
        $signer->loadCertificate($this->good_certificate_file, true);
        $signer->sign($node, $signature_parent);

        // openssl x509 -in good.cert.pem -noout -sha1 -fingerprint
        // Avoiding openssl_x509_fingerprint because it's >= PHP 5.6 only
        $fingerprint = 'a7fb75225788a1b0d0290a4bd1ea0c01f89844a0';

        $validator = new Validator(
            $doc,
            'node',
            array('certFingerprint' => array($fingerprint))
        );

        // Avoiding Validator::class because it's >= PHP 5.5 only
        $this->assertInstanceOf('\SimpleSAML\XML\Validator', $validator);
    }

    public function testCertFingerprintFailure()
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<?xml version="1.0"?><node>value</node>');

        $node = $doc->getElementsByTagName('node')->item(0);

        $signature_parent = $doc->appendChild(new \DOMElement('signature_parent'));

        $signer = new Signer(array());
        $signer->loadPrivateKey($this->good_private_key_file, null, true);
        $signer->loadCertificate($this->good_certificate_file, true);
        $signer->sign($node, $signature_parent);

        $this->setExpectedException('\Exception');
        new Validator($doc, 'node', array('certFingerprint' => array()));
    }

    public function testValidateFingerprintSuccess()
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<?xml version="1.0"?><node>value</node>');

        $node = $doc->getElementsByTagName('node')->item(0);

        $signature_parent = $doc->appendChild(new \DOMElement('signature_parent'));

        $signer = new Signer(array());
        $signer->loadPrivateKey($this->good_private_key_file, null, true);
        $signer->loadCertificate($this->good_certificate_file, true);
        $signer->sign($node, $signature_parent);

        // openssl x509 -in good.cert.pem -noout -sha1 -fingerprint
        // Avoiding openssl_x509_fingerprint because it's >= PHP 5.6 only
        $fingerprint = 'a7fb75225788a1b0d0290a4bd1ea0c01f89844a0';

        $validator = new Validator($doc, 'node');
        $validator->validateFingerprint($fingerprint);

        // Avoiding Validator::class because it's >= PHP 5.5 only
        $this->assertInstanceOf('\SimpleSAML\XML\Validator', $validator);
    }

    public function testValidateFingerprintFailure()
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<?xml version="1.0"?><node>value</node>');

        $node = $doc->getElementsByTagName('node')->item(0);

        $signature_parent = $doc->appendChild(new \DOMElement('signature_parent'));

        $signer = new Signer(array());
        $signer->loadPrivateKey($this->good_private_key_file, null, true);
        $signer->loadCertificate($this->good_certificate_file, true);
        $signer->sign($node, $signature_parent);

        $fingerprint = 'BAD FINGERPRINT';

        $validator = new Validator($doc, 'node');

        $this->setExpectedException('\Exception');
        $validator->validateFingerprint($fingerprint);
    }

    public function testIsNodeValidatedSuccess()
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<?xml version="1.0"?><node>value</node>');

        $node = $doc->getElementsByTagName('node')->item(0);

        $signature_parent = $doc->appendChild(new \DOMElement('signature_parent'));

        $signer = new Signer(array());
        $signer->loadPrivateKey($this->good_private_key_file, null, true);
        $signer->sign($node, $signature_parent);

        $validator = new Validator(
            $doc,
            'node',
            array('PEM' => $this->good_certificate)
        );

        $result = $validator->isNodeValidated($node);

        $this->assertTrue($result);
    }

    public function testIsNodeValidatedFailure()
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<?xml version="1.0"?><parent><node1>value1</node1><node2>value2</node2></parent>');

        $node1 = $doc->getElementsByTagName('node1')->item(0);
        $node2 = $doc->getElementsByTagName('node2')->item(0);

        $signature_parent = $doc->appendChild(new \DOMElement('signature_parent'));

        $signer = new Signer(array());
        $signer->loadPrivateKey($this->good_private_key_file, null, true);
        $signer->sign($node1, $signature_parent);

        $validator = new Validator(
            $doc,
            'node1',
            array('PEM' => $this->good_certificate)
        );

        $result = $validator->isNodeValidated($node2);

        $this->assertFalse($result);
    }

    public function testValidateCertificateMissingCAFile()
    {
        $ca_file = $this->ca_certificate_file.'NOT';

        $this->setExpectedException('\Exception');
        Validator::validateCertificate($this->good_certificate, $ca_file);
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
