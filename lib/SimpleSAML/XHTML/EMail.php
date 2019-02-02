<?php

namespace SimpleSAML\XHTML;

/**
 * A minimalistic Emailer class. Creates and sends HTML emails.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */

class EMail
{
    /** @var string|null */
    private $to = null;

    /** @var string|null */
    private $cc = null;

    /** @var string|null */
    private $body = null;

    /** @var string|null */
    private $from = null;

    /** @var string|null */
    private $replyto = null;

    /** @var string|null */
    private $subject = null;

    /** @var array */
    private $headers = [];


    /**
     * Constructor
     *
     * @param string $to
     * @param string $subject
     * @param string|null $from
     * @param string|null $cc
     * @param string|null $replyto
     */
    public function __construct($to, $subject, $from = null, $cc = null, $replyto = null)
    {
        $this->to = $to;
        $this->cc = $cc;
        $this->from = $from;
        $this->replyto = $replyto;
        $this->subject = $subject;
    }

    /**
     * @param string $body
     * @return void
     */
    public function setBody($body)
    {
        $this->body = $body;
    }


    /**
     * @param string $body
     * @return string
     */
    private function getHTML($body)
    {
        $config = \SimpleSAML\Configuration::getInstance();
        $t = new \SimpleSAML\XHTML\Template($config, 'errorreport_mail.twig');
        $twig = $t->getTwig();
        return $twig->render('errorreport_mail.twig', ['body' => $body]);
    }


    /**
     * @return void
     */
    public function send()
    {
        if ($this->to === null) {
            throw new \Exception('EMail field [to] is required and not set.');
        } elseif ($this->subject === null) {
            throw new \Exception('EMail field [subject] is required and not set.');
        } elseif ($this->body === null) {
            throw new \Exception('EMail field [body] is required and not set.');
        }

        $random_hash = bin2hex(openssl_random_pseudo_bytes(16));

        if (isset($this->from)) {
            $this->headers[] = 'From: '.$this->from;
        }
        if (isset($this->replyto)) {
            $this->headers[] = 'Reply-To: '.$this->replyto;
        }

        $this->headers[] = 'Content-Type: multipart/alternative; boundary="simplesamlphp-'.$random_hash.'"';

        $message = '
--simplesamlphp-'.$random_hash.'
Content-Type: text/plain; charset="utf-8" 
Content-Transfer-Encoding: 8bit

'.strip_tags(html_entity_decode($this->body)).'

--simplesamlphp-'.$random_hash.'
Content-Type: text/html; charset="utf-8" 
Content-Transfer-Encoding: 8bit

'.$this->getHTML($this->body).'

--simplesamlphp-'.$random_hash.'--
';
        $headers = implode("\n", $this->headers);

        $mail_sent = @mail($this->to, $this->subject, $message, $headers);
        \SimpleSAML\Logger::debug('Email: Sending e-mail to ['.$this->to.'] : '.($mail_sent ? 'OK' : 'Failed'));
        if (!$mail_sent) {
            throw new \Exception('Error when sending e-mail');
        }
    }
}
