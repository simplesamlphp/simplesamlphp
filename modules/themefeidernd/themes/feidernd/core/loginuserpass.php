<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
	<title><?php echo $this->t('{login:user_pass_header}'); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

	<link rel='stylesheet' href="/<?php echo $this->data['baseurlpath']; ?>resources/feidernd.css" type='text/css' />
	<!--[if IE]><style type="text/css">#login h1 a { margin-top: 35px; } #login #login_error { margin-bottom: 10px; }</style><![endif]--><!-- Curse you, IE! -->
	<style>
		input {
			border: 1px solid #005;
		}
	</style>
	<script type="text/javascript">
		function focusit() {
			document.getElementById('username').focus();
		}
		window.onload = focusit;
	</script>
</head>
<body class="login">
<div id="login">
	<h1>
		<a href="http://feide.no/" title="Go to Feide.no">Feide</a>
	</h1>

	<form name="loginform" id="loginform" action="?" method="post">
		<p>
			<label><?php echo $this->t('{login:username}'); ?><br />
			<input type="text" name="username" id="username" class="input" <?php if (isset($this->data['username'])) {
						echo 'value="' . htmlspecialchars($this->data['username']) . '"';
					} ?> size="20" tabindex="10" /></label>
		</p>
		<p>
			<label><?php echo $this->t('{login:password}'); ?><br />
			<input type="password" name="password" id="user_pass" class="input" value="" size="20" tabindex="20" /></label>
		</p>
		<!-- p><label><input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="90" /> Remember me</label></p -->
		<p class="submit">
			<input type="submit" name="wp-submit" id="wp-submit" value="<?php echo $this->t('{login:login_button}'); ?> &raquo;" tabindex="100" />
			<input type="hidden" name="RelayState" value="<?php echo htmlspecialchars($this->data['relaystate']); ?>" />
		</p>



<?php
if ($this->data['errorcode'] !== NULL) {
?>
	<div style="border: 1px solid #500;  background: #880b17; ">
		<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/bomb.png" style="float: left; margin: 15px " />
		<h2><?php echo $this->t('{login:error_header}'); ?></h2>
		<p><b><?php echo $this->t('{errors:title_' . $this->data['errorcode'] . '}'); ?></b></p>
		<p><?php echo $this->t('{errors:descr_' . $this->data['errorcode'] . '}'); ?></p>
	</div>
<?php
}
?>

<!--
	<?php if (isset($this->data['error'])) { ?>
		<div style="border: 1px solid #500;  background: #880b17; ">
		<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/bomb.png" style="float: left; margin: 15px " />
		<h2><?php echo $this->t('{error:error_header}'); ?></h2>
		
		<p style="padding: .2em"><?php echo $this->t($this->data['error']); ?> </p>
		</div>
	<?php } ?>
	
	
	
<?php
if ($this->data['errorcode'] !== NULL) {
?>
	<div style="border-left: 1px solid #e8e8e8; border-bottom: 1px solid #e8e8e8; background: #f5f5f5">
		<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/bomb.png" style="float: left; margin: 15px " />
		<h2><?php echo $this->t('{login:error_header}'); ?></h2>
		<p><b><?php echo $this->t('{errors:title_' . $this->data['errorcode'] . '}'); ?></b></p>
		<p><?php echo $this->t('{errors:descr_' . $this->data['errorcode'] . '}'); ?></p>
	</div>
<?php
}
?>
		-->
		
<?php
foreach ($this->data['stateparams'] as $name => $value) {
	echo('<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />');
}
?>
		
	</form>
	

	

	
</div>



<ul>
	<li><a href="http://rnd.feide.no/" title="Feide RnD">» Feide RnD</a></li>
</ul>
<!--
		<h2><?php echo $this->t('help_header'); ?></h2>
		
		
		<p><?php echo $this->t('help_text'); ?></p>
-->
</body>
</html>