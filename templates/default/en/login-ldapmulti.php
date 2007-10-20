<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php echo $data['header']; ?></title>
<script>
<!--
function sf(){document.f.username.focus();}
// -->
</script>
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
<body onload="sf();">

<div id="wrap">

	<div id="header">
		<h1>simpleSAMLphp authentication</h1>
		<div id="poweredby"><img src="/<?php echo $data['baseurlpath']; ?>resources/icons/lock.png" alt="Login screen" /></div>
	</div>
	
	<div id="content">
	
		<?php if (isset($data['error'])) { ?>
		<div style="border-left: 1px solid #e8e8e8; border-bottom: 1px solid #e8e8e8; background: #f5f5f5"
		<img src="/<?php echo $data['baseurlpath']; ?>resources/icons/bomb.png" style="float: left; margin: 15px " />
		<h2>What you entered was not accepted!</h2>
		
		<p><?php echo $data['error']; ?> </p>
		</div>
		<?php } ?>
	
		<h2 style="break: both">Enter your username and password</h2>
		
		<p>
			A service has requested you to authenticate your self. That means you need to enter your username and password in the form below.
		</p>
		
		<form action="?" method="post" name="f">

		<table>
			<tr>
				<td rowspan="3"><img src="/<?php echo $data['baseurlpath']; ?>resources/icons/pencil.png" /></td>
				<td style="padding: .3em;">Username</td>
				<td><input type="text" tabindex="1" name="username" 
					<?php if (isset($data['username'])) {
						echo 'value="' . $data['username'] . '"';
					} ?> /></td>

					
				<td style="padding: .4em; rowspan="3">
					<input type="submit" tabindex="3" value="Login" />
					<input type="hidden" name="RelayState" value="<?php echo $data['relaystate']; ?>" />
				</td>
			</tr>
			
			<tr>
				<td style="padding: .3em;">Organization</td>
				<td><select name="org" tabindex="2">
					<?php
					
					foreach ($data['ldapconfig'] AS $key => $entry) {
						echo '<option ' .
							($key == $data['org'] ? 'selected="selected" ' : '')
							. 'value="' . $key . '">' . $entry['description'] . '</option>';
					}
					
					?>
				</select></td>
			</tr>
			
			<tr>
				<td style="padding: .3em;">Password</td>
				<td><input type="password" tabindex="2" name="password" /></td>
			</tr>
		</table>
		
		
		</form>
		
		
		<h2>Help! I don't remember my password.</h2>
		
		
		<p>Too bad! - Without your username and password you cannot authenticate your self and access the service.
		There may be someone that can help you. Contact the help desk at your university!</p>
		
		<h2>About simpleSAMLphp</h2>
		<p>Hey! This simpleSAMLphp thing is pretty cool, where can I read more about it?
		You can find more information about simpleSAMLphp at <a href="http://rnd.feide.no">the Feide RnD blog</a> over at <a href="http://uninett.no">UNINETT</a>.</p>
		

		<hr />
		
		Copyright &copy; 2007 <a href="http://rnd.feide.no/">Feide RnD</a>
		
		<hr />
	
	</div>

</div>

</body>
</html>
