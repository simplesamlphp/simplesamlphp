<?php

/*
 * Frontend for login.php, validate.php and serviceValidate.php. It allows them to be called
 * as cas.php/login, cas.php/validate and cas.php/serviceValidate and is meant for clients
 * like phpCAS which expects one configured prefix which it appends login, validate and 
 * serviceValidate to.
 */
 
 
$validFunctions = array('login', 'validate', 'serviceValidate');
$function = substr($_SERVER['PATH_INFO'], 1);
if (!in_array($function, $validFunctions, TRUE)) {
	throw new SimpleSAML_Error_NotFound('Not a valid function for cas.php.');
}

include($function.".php");
