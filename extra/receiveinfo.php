<?php
/**
 * Copy and paste this file into a page in your drupal installation, or similar CMS system.
 * Make sure the Input mode in your new page is PHP Mode.
 *
 * Then when you click save, notice the URL of your new page. This URL should be entered in the
 * config.php of your simpleSAMLphp installation.
 * 
 */
?>
<p>Thanks for sending information to us from simpleSAMLphp.</p>

<p>If you sent us metadata and expect us to add the metadata to our test enviornment, you also would need to send us an email to moria-support@uninett.no to explain what is the purpose of the testing, and tell us who you are :).</p>


<?php

$to   = 'andreas.solberg@uninett.no, moria-support@uninett.no';
#$to   = 'andreas.solberg@uninett.no';

if (isset($_POST['action'])) {
	

	
	$to   = 'andreas.solberg@uninett.no';
	$from = (isset($_POST['email']) ? $_POST['email'] : 'Anonymous <simplesamlphp@example.org>');
	$headers = 'From: ' . $from . "\r\n" . 'X-Mailer: PHP/' . phpversion();
	
	if ($_POST['action'] == 'metadata') {

		echo '<p>We have received your metadata.';	
		$subject = 'SAML 2.0 Metadata from '. $_POST['email'];
		$message = 'Someone just used simpleSAMLphp to send metadata to Feide. Here is the metadata: 
------- BEGIN SAML 2.0 METADATA ----------
' . html_entity_decode(base64_decode(urldecode($_POST['metadata']))) . '
------- END SAML 2.0 METADATA ----------


Default IdP: ' . $_POST['defaultidp'] . '
simpleSAMLphp version: ' . $_POST['version'] . '
Technical contact at server: ' . $_POST['techemail'] . ' 

Sent using simpleSAMLphp';
	
} elseif($_POST['action'] == 'error') {
	
	echo '<p>We have received your error report.';	

	$subject = 'Error report from '. $_POST['email'];
	$message = 'Someone just used simpleSAMLphp to send an error message to Feide. Here is the exception: 
------------------
Exception message: ' . html_entity_decode(base64_decode(urldecode($_POST['exceptionmsg']))) . '
------------------
Exception stacktrace:
' . html_entity_decode(base64_decode(urldecode($_POST['exceptiontrace']))) . '
------------------
Description from user:
' .  $_POST['text'] . '

TrackID [' . $_POST['trackid'] . ']

simpleSAMLphp version: ' . $_POST['version'] . '
Technical contact at server: ' . $_POST['techemail'] . ' 

Sent using simpleSAMLphp';

}


mail($to, $subject, $message, $headers);
}

?>
