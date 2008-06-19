<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php
if(array_key_exists('header', $this->data)) {
	echo $this->data['header'];
} else {
	echo 'simpleSAMLphp';
}
?></title>

	<link rel="stylesheet" type="text/css" href="/<?php echo $this->data['baseurlpath']; ?>resources/default.css" />
	<link rel="icon" type="image/icon" href="/<?php echo $this->data['baseurlpath']; ?>resources/icons/favicon.ico" />
	<script type="text/javascript" src="/<?php echo $this->data['baseurlpath']; ?>resources/script.js"></script>
</head>
<?php
$onLoad = '';
if(array_key_exists('autofocus', $this->data)) {
	$onLoad .= 'SimpleSAML_focus(\'' . $this->data['autofocus'] . '\');';
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
		<div id="poweredby">
			<a href="/<?php echo $this->data['baseurlpath']; ?>">
			<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/<?php 
				echo (isset($this->data['icon']) ? $this->data['icon'] : 'compass_l.png'); 
			?>" alt="Header icon" /></a></div>
	</div>

<div style="padding-right: 40px">
<?php 

$languages = $this->getLanguageList();
$langnames = array(
	'no' => 'Bokmål',
	'nn' => 'Nynorsk',
	'se' => 'Sami',
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
);

if (empty($_POST) ) {
	$textarray = array();
	foreach ($languages AS $lang => $current) {
		if ($current) {
			$textarray[] = $langnames[$lang];
		} else {
			$textarray[] = '<a href="' . htmlspecialchars(SimpleSAML_Utilities::addURLparameter(SimpleSAML_Utilities::selfURL(), 'language=' . $lang)) . '">' . 
				$langnames[$lang] . '</a>';
		}
	}
	echo join(' | ', $textarray);
}

?>
</div>