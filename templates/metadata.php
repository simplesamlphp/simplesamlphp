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
		<h1>simpleSAMLphp status page</h1>
		<div id="poweredby"><img src="/<?php echo $data['baseurlpath']; ?>resources/icons/bino.png" alt="Bino" /></div>
	</div>
	
	<div id="content">

		<h2><?php if (isset($data['header'])) { echo $data['header']; } else { echo "Some error occured"; } ?></h2>
		
		<p>Here is SAML 2.0 metadata that simpleSAMLphp has generated for you. You may send this SAML 2.0 Metadata document to trusted partners to setup a trusted federation.</p>
		
		<h2>Metadata</h2>
		
		<pre style="overflow: scroll; border: 1px solid #eee; padding: 2px"><?php echo $data['metadata']; ?></pre>

		
		<?php if($data['feide']) { ?>
		
		
			<div style="border: 1px solid #444; margin: 2em; padding: 1em; background: #eee">
			
				<img src="http://clippings.erlang.no/ZZ076BD170.jpg" style="float: right; " />
			
				<h2>Send your metadata to Feide</h2>
				
				<p>simpleSAMLphp has detected that you have configured Feide as your default IdP.</p>
				
				<p>Before you can connect to Feide, Feide needs to add your service in its trust configuration. When you
					contact Feide to add you as a new service, you will be asked to send your metadata. Here you can easily send
					the metadata to Feide by clicking the button below.</p>
					
				<form action="http://rnd.feide.no/post-metadata/index.php" method="post">

					<p>Feide needs to know how to get in contact with you, so you need to type in <strong>your email address</strong>:
						<input type="text" size="25" name="email" value="" />
					</p>
					
					<input type="hidden" name="metadata" value="<?php echo urlencode(base64_encode($data['metadata'])); ?>" />
					<input type="hidden" name="defaultidp" value="<?php echo $data['defaultidp']; ?>" />
					<input type="submit" name="send" value="Send my metadata to Feide" />
					
				</form>
				
			</div>
		
		<?php } ?>

		<hr />
		
		Copyright &copy; 2007 <a href="http://rnd.feide.no/">Feide RnD</a>
		
		<hr />
	
	</div>

</div>

</body>
</html>
