<?php

namespace SimpleSAML\XHTML;

/**
 * A minimalistic Emailer class. Creates and sends HTML emails.
 *
 * @author Andreas kre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */

class EMail
{
    private $to = null;
    private $cc = null;
    private $body = null;
    private $from = null;
    private $replyto = null;
    private $subject = null;
    private $headers = [];


    /**
     * Constructor
     */
    public function __construct($to, $subject, $from = null, $cc = null, $replyto = null)
    {
        $this->to = $to;
        $this->cc = $cc;
        $this->from = $from;
        $this->replyto = $replyto;
        $this->subject = $subject;
    }

    /*
     * @param string $body
     * @return void
     */
    public function setBody($body)
    {
        $this->body = $body;
    }


    /*
     * @param string $body
     * @return string
     */
    private function getHTML($body)
    {
        return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>SimpleSAMLphp Email report</title>
	<style type="text/css">
pre, div.box {
	margin: .4em 2em .4em 1em;
	padding: 4px;

}
pre {
	background: #eee;
	border: 1px solid #aaa;
}
	</style>
</head>
<body>
<div class="container" style="background: #fafafa; border: 1px solid #eee; margin: 2em; padding: .6em;">
'.$body.'
</div>
</body>
</html>';
    }


    /*
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
