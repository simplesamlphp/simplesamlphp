<?php

/*
 * Frontend for login.php, validate.php and serviceValidate.php. It allows them to be called
 * as cas.php/login, cas.php/validate and cas.php/serviceValidate and is meant for clients
 * like phpCAS which expects one configured prefix which it appends login, validate and 
 * serviceValidate to.
 */
 
 
list($function) = preg_split('/[\/?]/', $_SERVER['PATH_INFO'], 0, PREG_SPLIT_NO_EMPTY);

include($function.".php");