<?php

require_once('_include.php');

$config = SimpleSAML_Configuration::getInstance();

/* This page will redirect to itself after processing a POST request and sending the email. */
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
	/* The message has been sent. Show error report page. */

	$t = new SimpleSAML_XHTML_Template($config, 'errorreport.php', 'errors');
	$t->show();
	exit;
}


/* Format of the email.
 * POST fields will be added to the email in the order they appear here, and with the description
 * from the value in the array.
 *
 * DEPRECATED. Included as reference of incoming parameters.
 */
$mailFormat = array(
	'email' => 'Email address of submitter',
	'url' => 'URL of page where the error occured',
	'errorcode' => 'Error code',
	'parameters' => 'Parameters for the error',
	'text' => 'Message from user',
	'trackid' => 'Track id for the user\' session',
	'exceptionmsg' => 'Exception message',
	'exceptiontrace' => 'Exception backtrace',
	'version' => 'simpleSAMLphp version',
	);

/* POST fields we can safely ignore. */
$ignoredFields = array(
	'send',
	);

/* Generate a error ID, and add it to both the log and the error message. This should make it
 * simple to find the error in the logs.
 */
$reportId = SimpleSAML_Utilities::stringToHex(SimpleSAML_Utilities::generateRandomBytes(4));
SimpleSAML_Logger::error('Error report with  id ' . $reportId . ' generated.');


function getPValue($key) {
	if (array_key_exists($key, $_POST)) {
		return strip_tags($_POST[$key]);
	}
	return 'not set';
}

/* Build the email message. */

$message = '<h1>SimpleSAMLphp Error Report</h1>

<p>Message from user:</p>
<div class="box" style="background: yellow; color: #888; border: 1px solid #999900; padding: .4em; margin: .5em">' . getPValue('text') . '</div>

<p>Exception: <strong>' . getPValue('exceptionmsg') . '</strong></p>
<pre>' . getPValue('exceptiontrace') . '</pre>

<p>URL:</p>
<pre><a href="' . getPValue('url') . '">' . getPValue('url') . '</a></pre>

<p>Directory:</p>
<pre>' . dirname(dirname(__FILE__)) . '</pre>

<p>Track ID:</p>
<pre>' . getPValue('trackid') . '</pre>

<p>Version: <tt>' . getPValue('version') . '</tt></p>

<p>Report ID: <tt>' . $reportId . '</tt></p>

<hr />
<div class="footer">This message was sent using simpleSAMLphp. Visit <a href="http://rnd.feide.no/simplesamlphp">simpleSAMLphp homepage</a>.</div>

';


/* Add the email address of the submitter as the Reply-To address. */
$replyto = NULL;
$from = 'no-reply@simplesamlphp.org';
if(array_key_exists('email', $_POST)) {
	$email = $_POST['email'];
	$email = trim($email);
	/* Check that it looks like a valid email address. */
	if(!preg_match('/\s/', $email) && strpos($email, '@') !== FALSE) {
		$replyto = $email;
		$from = $email;
	}
}

/* Send the email. */
$toaddress = $config->getString('technicalcontact_email', 'na@example.org');
if($email !== 'na@example.org') {
	
	$email = new SimpleSAML_XHTML_EMail($email, 'simpleSAMLphp error report', $from);
	$email->setBody($message);
	$email->send();
}



/* Redirect the user back to this page to clear the POST request. */
SimpleSAML_Utilities::redirect(SimpleSAML_Utilities::selfURLNoQuery());

?>