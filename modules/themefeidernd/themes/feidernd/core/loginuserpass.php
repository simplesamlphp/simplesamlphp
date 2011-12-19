<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
	<title><?php echo $this->t('{login:user_pass_header}'); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

	<link rel='stylesheet' href="<?php echo SimpleSAML_Module::getModuleURL('themefeidernd/feidernd.css'); ?>" type='text/css' />
	<!--[if IE]><style type="text/css">#login h1 a { margin-top: 35px; } #login #login_error { margin-bottom: 10px; }</style><![endif]--><!-- Curse you, IE! -->

	<script type="text/javascript">
		function focusit() {
			document.getElementById('username').focus();
		}
		window.onload = focusit;
	</script>
</head>
<body class="login">

<div id="login">

	<form name="loginform" id="loginform" action="?" method="post">
		
			<img alt="logo" src="<?php echo SimpleSAML_Module::getModuleURL('themefeidernd/ssplogo-fish-only-s.png') ?>" style="float: right" />
		
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
		</p>



<?php
if ($this->data['errorcode'] !== NULL) {
?>
	<div id="error">
		<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/experience/gtk-dialog-error.48x48.png" style="float: right; margin: 15px " />
		<h2><?php echo $this->t('{login:error_header}'); ?></h2>
		<p style="clear: both"><b><?php echo $this->t('{errors:title_' . $this->data['errorcode'] . '}'); ?></b></p>
		<p><?php echo $this->t('{errors:descr_' . $this->data['errorcode'] . '}'); ?></p>
	</div>
<?php
}



if(!empty($this->data['links'])) {
	echo '<ul class="links" style="margin-top: 2em">';
	foreach($this->data['links'] AS $l) {
		echo '<li><a href="' . htmlspecialchars($l['href']) . '">' . htmlspecialchars($this->t($l['text'])) . '</a></li>';
	}
	echo '</ul>';
}



?>


<!--
	<?php if (isset($this->data['error'])) { ?>
		<div id="error">
		<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/experience/gtk-dialog-error.48x48.png" style="float: left; margin: 15px " />
		<h2><?php echo $this->t('{error:error_header}'); ?></h2>
		
		<p style="padding: .2em"><?php echo $this->t($this->data['error']); ?> </p>
		</div>
	<?php } ?>
	
	
	
<?php
if ($this->data['errorcode'] !== NULL) {
?>
	<div id="error">
		<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/experience/gtk-dialog-error.48x48.png" style="float: left; margin: 15px " />
		<h2><?php echo $this->t('{login:error_header}'); ?>sdfsdf</h2>
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


<?php


	$includeLanguageBar = TRUE;
	if (!empty($_POST)) 
		$includeLanguageBar = FALSE;
	if (isset($this->data['hideLanguageBar']) && $this->data['hideLanguageBar'] === TRUE) 
		$includeLanguageBar = FALSE;
	
	if ($includeLanguageBar) {
		

		echo '<div id="languagebar">';		
		
		// echo '<form action="' . SimpleSAML_Utilities::selfURL() . '" method="get">';
		// echo '<select name="language">';
		// echo '</select>';
		// echo '</form>';
		
		

		$languages = $this->getLanguageList();
		$langnames = array(
			'no' => 'Bokmål',
			'nn' => 'Nynorsk',
			'se' => 'Sámegiella',
			'sam' => 'Åarjelh-saemien giele',
			'da' => 'Dansk',
			'en' => 'English',
			'de' => 'Deutsch',
			'sv' => 'Svenska',
			'fi' => 'Suomeksi',
			'es' => 'Español',
			'eu' => 'Euskara',
			'fr' => 'Français',
			'nl' => 'Nederlands',
			'lb' => 'Luxembourgish', 
			'cs' => 'Czech',
			'sl' => 'Slovenščina', // Slovensk
			'hr' => 'Hrvatski', // Croatian
			'hu' => 'Magyar', // Hungarian
			'pl' => 'Język polski', // Polish
			'pt' => 'Português', // Portuguese
			'pt-br' => 'Português brasileiro', // Portuguese
			'tr' => 'Türkçe',
		);
		
		$textarray = array();
		foreach ($languages AS $lang => $current) {
			if ($current) {
				$textarray[] = $langnames[$lang];
			} else {
				$textarray[] = '<a href="' . htmlspecialchars(
						SimpleSAML_Utilities::addURLparameter(
							SimpleSAML_Utilities::selfURL(), array('language' => $lang)
						)
				) . '">' . $langnames[$lang] . '</a>';
			}
		}
		echo join(' | ', $textarray);
		echo '</div>';
	}

?>

</body>
</html>