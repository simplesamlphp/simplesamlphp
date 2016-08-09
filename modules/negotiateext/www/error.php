<?php

if (empty($_SERVER['REDIRECT_QUERY_STRING'])) {
	throw new SimpleSAML_Error_BadRequest('Missing "AuthState" parameter.');
}

if (empty($_SERVER['REDIRECT_URL'])) {
	throw new SimpleSAML_Error_NoState();
}

$url = str_replace( '/auth.php', '/backend.php', $_SERVER['REDIRECT_URL'] ) . '?' . $_SERVER['REDIRECT_QUERY_STRING'];

header("Refresh: 0;url='$url'");

?>

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title>Redirect to login</title>
  </head>
  <body>
    <h1>Redirect</h1>
      <p>Please <a id="redirlink" href="<?php echo htmlspecialchars($url); ?>">click here</a> if you are not redirected automatically.
        <script type="text/javascript">document.getElementById("redirlink").focus();</script>
      </p>
  </body>
</html>

<?php

exit;
