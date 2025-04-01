<?php

declare(strict_types=1);

namespace SimpleSAML\Utils;

use Exception;
use InvalidArgumentException;
use PHPMailer\PHPMailer\PHPMailer;
use SimpleSAML\{Configuration, Logger};
use SimpleSAML\XHTML\Template;

use function array_map;
use function intval;
use function is_array;
use function preg_replace;
use function strtolower;

/**
 * E-mailer class that can generate a formatted e-mail from array input data.
 *
 * @package SimpleSAMLphp
 */

class EMail
{
    /** @var array Dictionary with multivalues */
    private array $data = [];

    /** @var string Introduction text */
    private string $text = '';

    /** @var \PHPMailer\PHPMailer\PHPMailer The mailer instance */
    private PHPMailer $mail;


    /**
     * Constructor
     *
     * If $from or $to is not provided, the <code>technicalcontact_email</code>
     * from the configuration is used.
     *
     * @param string $subject The subject of the e-mail
     * @param string|null $from The from-address (both envelope and header)
     * @param string|null $to The recipient
     * @param string $txt_template The template to use for plain text messages
     * @param string $html_template The template to use for html messages
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function __construct(
        string $subject,
        ?string $from = null,
        ?string $to = null,
        private string $txt_template = 'mailtxt.twig',
        private string $html_template = 'mailhtml.twig',
    ) {
        $this->mail = new PHPMailer(true);
        $this->mail->Subject = $subject;
        $this->mail->setFrom($from ?: $this->getDefaultMailAddress());
        $this->mail->addAddress($to ?: $this->getDefaultMailAddress());

        $this->initFromConfig($this);
    }


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
    public function getDefaultMailAddress(): string
    {
        $config = Configuration::getInstance();
        $address = $config->getOptionalString('technicalcontact_email', 'na@example.org');
        $address = preg_replace('/^mailto:/i', '', $address);
        if ('na@example.org' === $address) {
            throw new Exception('technicalcontact_email must be changed from the default value');
        }
        return $address;
    }


    /**
     * Set the data that should be embedded in the e-mail body
     *
     * @param array $data The data that should be embedded in the e-mail body
     */
    public function setData(array $data): void
    {
        /*
         * Convert every non-array value to an array with the original
         * as its only element. This guarantees that every value of $data
         * can be iterated over.
         */
        $this->data = array_map(
            /**
             * @param mixed $v
             * @return array
             */
            function ($v) {
                return is_array($v) ? $v : [$v];
            },
            $data,
        );
    }


    /**
     * Set an introduction text for the e-mail
     *
     * @param string $text Introduction text
     */
    public function setText(string $text): void
    {
        $this->text = $text;
    }


    /**
     * Add a Reply-To address to the mail
     *
     * @param string $address Reply-To e-mail address
     */
    public function addReplyTo(string $address): void
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
    public function send(bool $plainTextOnly = false): void
    {
        if ($plainTextOnly) {
            $this->mail->isHTML(false);
            $this->mail->Body = $this->generateBody($this->txt_template);
        } else {
            $this->mail->isHTML(true);
            $this->mail->Body = $this->generateBody($this->html_template);
            $this->mail->AltBody = $this->generateBody($this->txt_template);
        }

        $this->mail->send();
    }


    /**
     * Sets the method by which the email will be sent.  Currently supports what
     * PHPMailer supports: sendmail, mail and smtp.
     *
     * @param string $transportMethod the transport method
     * @param array $transportOptions options for the transport method
     *
     *
     * @throws \InvalidArgumentException
     */
    public function setTransportMethod(string $transportMethod, array $transportOptions = []): void
    {
        switch (strtolower($transportMethod)) {
            // smtp transport method
            case 'smtp':
                $this->mail->isSMTP();

                // set the host (required)
                if (isset($transportOptions['host'])) {
                    $this->mail->Host = $transportOptions['host'];
                } else {
                    // throw an exception otherwise
                    throw new InvalidArgumentException("Missing Required Email Transport Parameter 'host'");
                }

                // set the port (optional, assume standard SMTP port 25 if not provided)
                $this->mail->Port = (isset($transportOptions['port'])) ? intval($transportOptions['port']) : 25;

                // smtp auth: enabled if username or password is set
                if (isset($transportOptions['username']) || isset($transportOptions['password'])) {
                    $this->mail->SMTPAuth = true;
                }

                // smtp auth: username
                if (isset($transportOptions['username'])) {
                    $this->mail->Username = $transportOptions['username'];
                }

                // smtp auth: password
                if (isset($transportOptions['password'])) {
                    $this->mail->Password = $transportOptions['password'];
                }

                // smtp security: encryption type
                if (isset($transportOptions['secure'])) {
                    $this->mail->SMTPSecure = $transportOptions['secure'];
                }

                // smtp security: enable or disable smtp auto tls
                if (isset($transportOptions['autotls'])) {
                    $this->mail->SMTPAutoTLS = boolval($transportOptions['autotls']);
                }

                // socket options for smtp TLS connection
                if (isset($transportOptions['smtpOptions'])) {
                    $this->mail->SMTPOptions = $transportOptions['smtpOptions'];
                }
                break;
            //mail transport method
            case 'mail':
                $this->mail->isMail();
                break;
            // sendmail transport method
            case 'sendmail':
                $this->mail->isSendmail();

                // override the default path of the sendmail executable
                if (isset($transportOptions['path'])) {
                    $this->mail->Sendmail = $transportOptions['path'];
                }
                break;
            default:
                throw new \InvalidArgumentException(
                    "Invalid Mail Transport Method - Check 'mail.transport.method' Configuration Option",
                );
        }
    }


    /**
     * Initializes the provided EMail object with the configuration provided from the SimpleSAMLphp configuration.
     *
     * @param EMail $EMail
     * @return EMail
     * @throws \Exception
     */
    public function initFromConfig(EMail $EMail): EMail
    {
        $config = Configuration::getInstance();
        $EMail->setTransportMethod(
            $config->getOptionalString('mail.transport.method', 'mail'),
            $config->getOptionalArrayize('mail.transport.options', []),
        );

        return $EMail;
    }


    /**
     * Generate the body of the e-mail
     *
     * @param string $template The name of the template to use
     *
     * @return string The body of the e-mail
     */
    public function generateBody(string $template): string
    {
        $config = Configuration::getInstance();

        $t = new Template($config, $template);
        $t->data['data'] = $this->data;
        $t->data['subject'] = $this->mail->Subject;
        $t->data['text'] = $this->text;

        return $t->getContents();
    }
}
