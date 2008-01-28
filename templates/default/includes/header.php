<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php
if(array_key_exists('header', $data)) {
	echo $data['header'];
} else {
	echo 'simpleSAMLphp';
}
?></title>

<style type="text/css">

/* these styles are in the head of this page because this is a unique page */

/* THE BIG GUYS */
* {margin:0;padding:0}
body {text-align:center;padding: 20px 0;background: #222;color:#333;font:83%/1.5 arial,tahoma,verdana,sans-serif}
img {border:none;display:block}
hr {margin: 1em 0;background:#eee;height:1px;color:#eee;border:none;clear:both}

/* LINKS */
a,a:link,a:link,a:link,a:hover {font-weight:bold;background:transparent;text-decoration:underline;cursor:pointer} 
a:link {color:#c00} 
a:visited {color:#999} 
a:hover,a:active {color:#069} 

/* LISTS */
ul {margin: .3em 0 1.5em 2em}
	ul.related {margin-top:-1em}
li {margin-left:2em}
dt {font-weight:bold}
#wrap {border: 1px solid #fff;position:relative;background:#fff;width:600px;margin: 0 auto;text-align:left}
#header {background: #666 url("/<?php echo $data['baseurlpath']; ?>resources/sprites.gif") repeat-x 0 100%;margin: 0 0 25px;padding: 0 0 8px}
#header h1 {color:#fff;font-size: 145%;padding:20px 20px 12px}
#poweredby {width:96px;height:63px;position:absolute;top:0;right:0}
#content {padding: 0 20px}

/* TYPOGRAPHY */
p, ul, ol {margin: 0 0 1.5em}
h1, h2, h3, h4, h5, h6 {letter-spacing: -1px;font-family: arial,verdana,sans-serif;margin: 1.2em 0 .3em;color:#000;border-bottom: 1px solid #eee;padding-bottom: .1em}
h1 {font-size: 196%;margin-top:0;border:none}
h2 {font-size: 136%}
h3 {font-size: 126%}
h4 {font-size: 116%}
h5 {font-size: 106%}
h6 {font-size: 96%}

.old {text-decoration:line-through}
</style>
	<link rel="icon" type="image/icon" href="/<?php echo $data['baseurlpath']; ?>resources/icons/favicon.ico" />
</head>
<body>

<div id="wrap">


<?php 

$languages = $this->getLanguageList();
$langnames = array(
	'no'	=>	'Norsk',
	'en'	=>	'English',
	'de'	=>	'Deutch',
	'dk'	=>	'Dansk'
);


foreach ($languages AS $lang => $current) {
	if ($current) {
		echo $langnames[$lang] . ' | ';
	} else {
		echo '<a href="' . htmlspecialchars(SimpleSAML_Utilities::addURLparameter(SimpleSAML_Utilities::selfURL(), 'language=' . $lang)) . '">' . 
			$langnames[$lang] . '</a> | ';
	}
}

?>