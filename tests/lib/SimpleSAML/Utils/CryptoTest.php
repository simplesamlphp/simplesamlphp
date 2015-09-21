<?php


/**
 * Tests for SimpleSAML\Utils\Crypto.
 */
class CryptoTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test invalid input provided to the aesDecrypt() method.
     *
     * @expectedException InvalidArgumentException
     */
    public function testAesDecryptBadInput()
    {
        $m = new ReflectionMethod('\SimpleSAML\Utils\Crypto', '_aesDecrypt');
        $m->setAccessible(true);

        $m->invokeArgs(null, array(array(), 'SECRET'));
    }


    /**
     * Test invalid input provided to the aesEncrypt() method.
     *
     * @expectedException InvalidArgumentException
     */
    public function testAesEncryptBadInput()
    {
        $m = new ReflectionMethod('\SimpleSAML\Utils\Crypto', '_aesEncrypt');
        $m->setAccessible(true);

        $m->invokeArgs(null, array(array(), 'SECRET'));
    }


    /**
     * Test that aesDecrypt() works properly, being able to decrypt some previously known (and correct)
     * ciphertext.
     */
    public function testAesDecrypt()
    {
        if (!extension_loaded('openssl')) {
            $this->setExpectedException('\SimpleSAML_Error_Exception');
        }

        $secret = 'SUPER_SECRET_SALT';
        $m = new ReflectionMethod('\SimpleSAML\Utils\Crypto', '_aesDecrypt');
        $m->setAccessible(true);

        $plaintext = 'SUPER_SECRET_TEXT';
        $ciphertext = 'NmRkODJlZGE2OTA3YTYwMm9En+KAReUk2z7Xi/b3c39kF/c1n6Vdj/zNARQt+UHU';
        $this->assertEquals($plaintext, $m->invokeArgs(null, array(base64_decode($ciphertext), $secret)));
    }


    /**
     * Test that aesEncrypt() produces ciphertexts that aesDecrypt() can decrypt.
     */
    public function testAesEncrypt()
    {
        if (!extension_loaded('openssl')) {
            $this->setExpectedException('\SimpleSAML_Error_Exception');
        }

        $secret = 'SUPER_SECRET_SALT';
        $e = new ReflectionMethod('\SimpleSAML\Utils\Crypto', '_aesEncrypt');
        $d = new ReflectionMethod('\SimpleSAML\Utils\Crypto', '_aesDecrypt');
        $e->setAccessible(true);
        $d->setAccessible(true);

        $original_plaintext = 'SUPER_SECRET_TEXT';
        $ciphertext = $e->invokeArgs(null, array($original_plaintext, $secret));
        $decrypted_plaintext = $d->invokeArgs(null, array($ciphertext, $secret));
        $this->assertEquals($original_plaintext, $decrypted_plaintext);
    }
}
