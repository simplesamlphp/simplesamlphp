<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script type="text/javascript" src="/<?php echo $this->data['baseurlpath']; ?>resources/script.js"></script>
<title><?php
if(array_key_exists('header', $this->data)) {
	echo $this->data['header'];
} else {
	echo 'simpleSAMLphp';
}
?></title>

	<link rel="stylesheet" type="text/css" href="/<?php echo $this->data['baseurlpath']; ?>resources/default.css" />
	<link rel="icon" type="image/icon" href="/<?php echo $this->data['baseurlpath']; ?>resources/icons/favicon.ico" />
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW" />

<?php	
if(array_key_exists('head', $this->data)) {
	echo '<!-- head -->' . $this->data['head'] . '<!-- /head -->';
}
?>
</head>
<?php
$onLoad = '';
if(array_key_exists('autofocus', $this->data)) {
	$onLoad .= 'SimpleSAML_focus(\'' . $this->data['autofocus'] . '\');';
}
if (isset($this->data['onLoad'])) {
	$onLoad .= $this->data['onLoad']; 
}

if($onLoad !== '') {
	$onLoad = ' onload="' . $onLoad . '"';
}
?>
<body<?php echo $onLoad; ?>>

<div id="wrap">
	
	<div id="header">
		<h1><a style="text-decoration: none; color: white" href="/<?php echo $this->data['baseurlpath']; ?>"><?php 
			echo (isset($this->data['header']) ? $this->data['header'] : 'simpleSAMLphp'); 
		?></a></h1>
	</div>

	<div id="languagebar">
	<?php 
	
	$languages = $this->getLanguageList();
	$langnames = array(
		'no' => 'Bokmål',
		'nn' => 'Nynorsk',
		'se' => 'Sámi',
		'da' => 'Dansk',
		'en' => 'English',
		'de' => 'Deutsch',
		'sv' => 'Svenska',
		'fi' => 'Suomeksi',
		'es' => 'Español',
		'fr' => 'Français',
		'nl' => 'Nederlands',
		'lb' => 'Luxembourgish', 
		'sl' => 'Slovenščina', // Slovensk
		'hr' => 'Hrvatski', // Croatian
		'hu' => 'Magyar', // Hungarian
		'pt' => 'Português', // Portuguese
		'pt-BR' => 'Português brasileiro', // Portuguese
	);
	
	if (empty($_POST) ) {
		$textarray = array();
		foreach ($languages AS $lang => $current) {
			if ($current) {
				$textarray[] = $langnames[$lang];
			} else {
				$textarray[] = '<a href="' . htmlspecialchars(SimpleSAML_Utilities::addURLparameter(SimpleSAML_Utilities::selfURL(), array('language' => $lang))) . '">' .
					$langnames[$lang] . '</a>';
			}
		}
		echo join(' | ', $textarray);
	}
	
	?>
	</div>
	<div id="content">
