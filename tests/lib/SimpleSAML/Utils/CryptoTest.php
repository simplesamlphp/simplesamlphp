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
    public function testAesDecryptBadInput() {
        SimpleSAML\Utils\Crypto::aesDecrypt(array());
    }


    /**
     * Test that aesDecrypt() works properly, being able to decrypt some previously known (and correct)
     * ciphertext.
     */
    public function testAesDecrypt() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'secretsalt' => 'SUPER_SECRET_SALT',
        ));

        $plaintext  = 'SUPER_SECRET_TEXT';
        $ciphertext = 'GA5lvW9TbAErqGvHFfZMUkdIg6zCJYmjFSGERAhByYNcMts70N4fWLjUm7/aVygPm55GbGhEG2himXJUHR1Ibg==';
        $this->assertEquals($plaintext, SimpleSAML\Utils\Crypto::aesDecrypt(base64_decode($ciphertext)));
    }


    /**
     * Test that aesEncrypt() produces ciphertexts that aesDecrypt() can decrypt.
     */
    public function testAesEncrypt() {
        $c = SimpleSAML_Configuration::loadFromArray(array(
            'secretsalt' => 'SUPER_SECRET_SALT',
        ));

        $original_plaintext = 'SUPER_SECRET_TEXT';
        $ciphertext = SimpleSAML\Utils\Crypto::aesEncrypt($original_plaintext);
        $decrypted_plaintext = SimpleSAML\Utils\Crypto::aesDecrypt($ciphertext);
        $this->assertEquals($original_plaintext, $decrypted_plaintext);
    }
}
