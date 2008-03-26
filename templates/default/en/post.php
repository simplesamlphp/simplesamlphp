<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>SAML 2.0 POST</title>
</head>
<body onload="document.forms[0].submit()">

	<noscript>
		<p><strong>Note:</strong> Since your browser does not support JavaScript, you must press the button below once to proceed.</p> 
	</noscript> 
	
	<form method="post" action="<?php echo htmlspecialchars($this->data['destination']); ?>">
		<input type="hidden" name="SAMLResponse" value="<?php echo htmlspecialchars($this->data['response']); ?>" />
		<input type="hidden" name="<?php echo htmlspecialchars($this->data['RelayStateName']); ?>" value="<?php echo htmlspecialchars($this->data['RelayState']); ?>" />
		
		<noscript>
			<input type="submit" value="Submit the response to the service" />
		</noscript>
	</form>

</body>
</html>