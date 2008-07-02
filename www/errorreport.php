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
 */
$mailFormat = array(
	'email' => 'Email address of submitter',
	'url' => 'URL of page where the error occured',
	'errorcode' => 'Error code',
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

/* Build the email message. */

$message = '';

/**
 * Format and add a section to the email message.
 *
 * @param $title  The title of the section.
 * @param $content  The content of the section.
 */
function addMessageSection($title, $content) {
	global $message;

	$message .= $title . "\n";
	$message .= "===============================================================\n";

	foreach(split("\n", $content) as $line) {
		$message .= ' ' . $line . "\n";
	}

	$message .= "\n";
}

/* Add the default fields to the message. */
foreach($mailFormat as $key => $desc) {
	if(!array_key_exists($key, $_POST)) {
		/* Not included in the POST message, skip. */
		continue;
	}

	$data = $_POST[$key];

	addMessageSection($desc, $data);
}

/* Add any unknown fields to the message. */
foreach($_POST as $key => $data) {

	/* Skip known fields. */
	if(array_key_exists($key, $mailFormat)) {
		continue;
	}

	/* Skip ignored fields. */
	if(in_array($key, $ignoredFields, TRUE)) {
		continue;
	}

	$title = 'Unknown field: ' . $key;
	addMessageSection($title, $data);
}


/* Add footer to message. */
$message .= 'Error report id: ' . $reportId . "\n";
$message .= "You may search the logs for this id to find the location\n";
$message .= "where this report was sent.\n";

/* We want to use UTF-8 encoding of the email message. */
$headers = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-Type: text/plain; charset="UTF-8"' . "\r\n";

/* Send the email. */
$email = $config->getValue('technicalcontact_email', 'na@example.org');
if($email !== 'na@example.org') {
	/* This should always be TRUE, as the error report button should not appear unless
	 * the email is set.
	 */
	mail($email, 'simpleSAMLphp error report', $message, $headers);
}

/* Redirect the user back to this page to clear the POST request. */
SimpleSAML_Utilities::redirect(SimpleSAML_Utilities::selfURLNoQuery());

?>