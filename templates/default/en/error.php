<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php echo $data['header']; ?></title>

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
</head>
<body>

<div id="wrap">

	<div id="header">
		<h1>simpleSAMLphp error page</h1>
		<div id="poweredby"><img src="/<?php echo $data['baseurlpath']; ?>resources/icons/bomb_l.png" alt="Login screen" /></div>
	</div>
	
	<div id="content">
	



		<h2><?php if (isset($data['header'])) { echo $data['header']; } else { echo "Some error occured"; } ?></h2>
		
		<p>
			
			<?php echo $data['message']; ?>

		</p>

		
		<p>The debug information below may be interesting for the administrator / help desk:</p>
		
		<div style="border: 1px solid #eee; padding: 1em; font-size: x-small">
			<p style="margin: 1px"><?php echo htmlentities($data['e']->getMessage()); ?></p>
			<div style=" padding: 1em; font-family: monospace; ">
				<?php echo htmlentities($data['e']->getTraceAsString()); ?>
			</div>
		</div>
		
		<h2 style="clear: both">How to get help</h2>
		
		
		<p>This error probably is due to some unexpected behaviour or to misconfiguration of simpleSAMLphp. Contact the administrator of this login service, and send them the error message above.</p>
		


		<hr />
		
		Copyright &copy; 2007 <a href="http://rnd.feide.no/">Feide RnD</a>
		
		<hr />
	
	</div>

</div>

</body>
</html>
