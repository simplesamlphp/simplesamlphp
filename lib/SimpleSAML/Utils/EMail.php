<?php

namespace SimpleSAML\Utils;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\XHTML\Template;

/**
 * E-mailer class that can generate a formatted e-mail from array
 * input data.
 *
 * @author Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * @package SimpleSAMLphp
 */

class EMail
{

    /**
     * Get the default e-mail address from the configuration
     * This is used both as source and destination address
     * unless something else is provided at the constructor.
     *
     * It will refuse to return the SimpleSAMLphp default address,
     * which is na@example.org.
     *
     * @return string Default mail address
     */
    public static function getDefaultMailAddress()
    {
        $config = Configuration::getInstance();
        $address = $config->getString('technicalcontact_email', 'na@example.org');
        if ('na@example.org' === $address) {
            throw new \Exception('technicalcontact_email must be changed from the default value');
        }
        return $address;
    }

    /** @var array Dictionary with multivalues */
    private $data;
    /** @var string Introduction text */
    private $text;
    /** @var PHPMailer The mailer instance */
    private $mail;

    /**
     * Constructor
     *
     * If $from or $to is not provided, the <code>technicalcontact_email</code>
     * from the configuration is used.
     *
     * @param string $subject The subject of the e-mail
     * @param string $from The from-address (both envelope and header)
     * @param string $to The recipient
     *
     * @throws PHPMailer\PHPMailer\Exception
     */
    public function __construct($subject, $from = null, $to = null)
    {
        $this->mail = new PHPMailer(true);
        $this->mail->Subject = $subject;
        $this->mail->setFrom($from ?: static::getDefaultMailAddress());
        $this->mail->addAddress($to ?: static::getDefaultMailAddress());
    }

    /**
     * Set the data that should be embedded in the e-mail body
     *
     * @param array $data The data that should be embedded in the e-mail body
     */
    public function setData(array $data)
    {
        /*
         * Convert every non-array value to an array with the original
         * as its only element. This guarantees that every value of $data
         * can be iterated over.
         */
        $this->data = array_map(function($v){return is_array($v) ? $v : [$v];}, $data);
    }

    /**
     * Set an introduction text for the e-mail
     *
     * @param string $text Introduction text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * Add a Reply-To address to the mail
     *
     * @param string $address Reply-To e-mail address
     */
    public function addReplyTo($address)
    {
        $this->mail->addReplyTo($address);
    }

    /**
     * Send the mail
     *
     * @param bool $plainTextOnly Do not send HTML payload
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function send($plainTextOnly = false)
    {
        if ($plainTextOnly) {
            $this->mail->isHTML(false);
            $this->mail->Body = $this->generateBody('mailtxt.twig');
        } else {
            $this->mail->isHTML(true);
            $this->mail->Body = $this->generateBody('mailhtml.twig');
            $this->mail->AltBody = $this->generateBody('mailtxt.twig');
        }

        $this->mail->send();
    }

    /**
     * Generate the body of the e-mail
     *
     * @param string $template The name of the template to use
     *
     * @return string The body of the e-mail
     */
    public function generateBody($template)
    {
        $config = Configuration::loadFromArray([
            'usenewui' => true,
        ]);
        $t = new Template($config, $template);
        $twig = $t->getTwig();
        if (is_bool($twig)) {
            throw new \Exception('Even though we explicitly configure that we want Twig, the Template class does not give us Twig. This is a bug.');
        }
        $result = $twig->render($template, [
                'subject' => $this->mail->Subject,
                'text' => $this->text,
                'data' => $this->data
            ]);
        return $result;
    }

}
