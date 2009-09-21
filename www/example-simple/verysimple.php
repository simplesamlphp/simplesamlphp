<?php

/*
 * This script is meant as an example of how simpleSAMLphp can be
 * accessed from an existing application.
 *
 * As such, it does not use any of the simpleSAMLphp templates.
 */


/*
 * We need access to the various simpleSAMLphp classes. These are loaded
 * by the simpleSAMLphp autoloader.
 */
require_once('../../lib/_autoload.php');

/*
 * We use the default-sp authentication source.
 */
$as = new SimpleSAML_Auth_Simple('default-sp');

/* This handles logout requests. */
if (array_key_exists('logout', $_REQUEST)) {
	/*
	 * We redirect to the current URL _without_ the query parameter. This
	 * avoids a redirect loop, since otherwise it will access the logout
	 * endpoint again.
	 */
	$as->logout(SimpleSAML_Utilities::selfURLNoQuery());
	/* The previous function will never return. */
}

if (array_key_exists('login', $_REQUEST)) {
	/*
	 * If the login parameter is requested, it means that we should log
	 * the user in. We do that by requiring the user to be authenticated.
	 *
	 * Note that the requireAuth-function will preserve all GET-parameters
	 * and POST-parameters by default.
	 */
	$as->requireAuth();
	/* The previous function will only return if the user is authenticated. */
}

if (array_key_exists('message', $_POST)) {
	/*
	 * We require authentication while posting a message. If the user is
	 * authenticated, the message will be shown.
	 *
	 * Since POST parameters are preserved during requireAuth-processing,
	 * the message will be presented to the user after the authentication.
	 */
	$as->requireAuth();
	$message = $_POST['message'];
} else {
	$message = NULL;
}

/*
 * We set a variable depending on whether the user is authenticated or not.
 * This allows us to show the user a login link or a logout link depending
 * on the authentication state.
 */
$isAuth = $as->isAuthenticated();


/*
 * Retrieve the users attributes. We will list them if the user
 * is authenticated.
 */
$attributes = $as->getAttributes();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Simple test</title>
</head>
<body>

<h1>Simple auth test</h1>

<?php
/* Show a logout message if authenticated or a login message if not. */
if ($isAuth) {
	echo '<p>You are currently authenticated. <a href="?logout">Log out</a>.</p>';
} else {
	echo '<p>You are not authenticated. <a href="?login">Log in</a>.</p>';
}
?>

<p>The following form makes it possible to test requiering authentication
in a POST handler. Try to submit the message while unauthenticated.</p>
<form method="post" action="#">
<input type="text" name="message" id="msg" />
<input type="submit" value="Post message" />
</form>

<?php

/* Print out the message if it is present. */
if ($message !== NULL) {
	echo '<h2>Message</h2>';
	echo '<p>' . htmlspecialchars($message) . '</p>';
}

/* Print out the attributes if the user is authenticated. */
if ($isAuth) {
	echo '<h2>Attributes</h2>';
	echo '<dl>';

	foreach ($attributes as $name => $values) {
		echo '<dt>' . htmlspecialchars($name) . '</dt>';
		echo '<dd><ul>';
		foreach ($values as $value) {
			echo '<li>' . htmlspecialchars($value) . '</li>';
		}
		echo '</ul></dd>';
	}

	echo '</dl>';
}

?>

</body>
</html>