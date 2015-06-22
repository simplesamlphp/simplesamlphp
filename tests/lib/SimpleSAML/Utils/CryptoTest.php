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
        if (!extension_loaded('mcrypt')) {
            $this->setExpectedException('\SimpleSAML_Error_Exception');
        }

        $secret = 'SUPER_SECRET_SALT';
        $m = new ReflectionMethod('\SimpleSAML\Utils\Crypto', '_aesDecrypt');
        $m->setAccessible(true);

        $plaintext = 'SUPER_SECRET_TEXT';
        $ciphertext = 'J5/rmhc54DpEbnP4rLD3IUUiSOE28165Gpr8BzNF4bFHjjesCe6mnHRZ6EiRbQE41ZDB/qg3ilWlw1gWzlKKww==';
        $this->assertEquals($plaintext, $m->invokeArgs(null, array(base64_decode($ciphertext), $secret)));
    }


    /**
     * Test that aesEncrypt() produces ciphertexts that aesDecrypt() can decrypt.
     */
    public function testAesEncrypt()
    {
        if (!extension_loaded('mcrypt')) {
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
