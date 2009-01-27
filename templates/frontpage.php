<?php 
#	$this->data['icon'] = 'compass_l.png';

	
$this->data['head']  = '<script type="text/javascript" src="/' . $this->data['baseurlpath'] . 'resources/jquery.js"></script>';
$this->data['head'] .= '<script type="text/javascript" src="/' . $this->data['baseurlpath'] . 'resources/jquery-ui.js"></script>';
$this->data['head'] .= '<link rel="stylesheet" media="screen" type="text/css" href="/' . $this->data['baseurlpath'] . 'resources/uitheme/jquery-ui-themeroller.css" />';

$this->data['head'] .= '<script type="text/javascript">

$(document).ready(function() {
	$("#tabdiv > ul").tabs();
});
</script>';



$this->includeAtTemplateBase('includes/header.php'); 
	
if ($this->data['isadmin']) {
	echo '<p style="float: right">' . $this->t('loggedin_as_admin') . '</p>';
} else {
	echo '<p style="float: right"><a href="' . $this->data['loginurl'] . '">' . $this->t('login_as_admin') . '</a></p>';
}

?>



<div id="tabdiv">
<ul>
	<li><a href="#welcome"><?php echo $this->t('welcome'); ?></a></li>
	<li><a href="#configuration"><?php echo $this->t('configuration'); ?></a></li>
	<li><a href="#metadata"><?php echo $this->t('metadata'); ?></a></li>
</ul>


<div id="welcome">


<div class="enablebox mini">
	<table>
	
	<?php
	$icon_enabled  = '<img src="/' . $this->data['baseurlpath'] . 'resources/icons/accept.png" alt="enabled" />';
	$icon_disabled = '<img src="/' . $this->data['baseurlpath'] . 'resources/icons/delete.png" alt="disabled" />';
	?>
	
		<tr class="<?php echo $this->data['enablematrix']['saml20-sp'] ? 'enabled' : 'disabled'; ?>"><td>SAML 2.0 SP</td>
			<td><?php echo $this->data['enablematrix']['saml20-sp'] ? $icon_enabled : $icon_disabled; ?></td></tr>
			
		<tr class="<?php echo $this->data['enablematrix']['saml20-idp'] ? 'enabled' : 'disabled'; ?>"><td>SAML 2.0 IdP</td>
			<td><?php echo $this->data['enablematrix']['saml20-idp'] ? $icon_enabled : $icon_disabled; ?></td></tr>
			
		<tr class="<?php echo $this->data['enablematrix']['shib13-sp'] ? 'enabled' : 'disabled'; ?>"><td>Shib 1.3 SP</td>
			<td><?php echo $this->data['enablematrix']['shib13-sp'] ? $icon_enabled : $icon_disabled; ?></td></tr>
			
		<tr class="<?php echo $this->data['enablematrix']['shib13-idp'] ? 'enabled' : 'disabled'; ?>"><td>Shib 1.3 IdP</td>
			<td><?php echo $this->data['enablematrix']['shib13-idp'] ? $icon_enabled : $icon_disabled; ?></td></tr>
		
	</table>
</div>


<p><?php echo $this->t('intro'); ?></p>


<h2><?php echo $this->t('useful_links_header'); ?></h2>
	<ul>
	<?php
	
		foreach ($this->data['links'] AS $link) {
			echo '<li><a href="' . htmlspecialchars($link['href']) . '">' . $this->t($link['text']) . '</a></li>';
		}
	?>
	</ul>


<h2><?php echo $this->t('doc_header'); ?></h2>
	<ul>
	<?php
	
		foreach ($this->data['links_doc'] AS $link) {
			echo '<li><a href="' . htmlspecialchars($link['href']) . '">' . $this->t($link['text']) . '</a></li>';
		}
	?>
	</ul>

<h2><?php echo $this->t('about_header'); ?></h2>
	<p><?php echo $this->t('about_text'); ?></p>

</div> <!-- #welcome -->


<div id="configuration">

<div>
	<code style="background: white; border: 1px solid #ccc; padding: 1em; color: #555" ><?php 
		echo $this->data['directory'] . ' (' . $this->data['version'] . ')'; 
	?></code>
</div>

<h2><?php echo $this->t('configuration'); ?></h2>
	<ul>
	<?php
	
		foreach ($this->data['links_conf'] AS $link) {
			echo '<li><a href="' . htmlspecialchars($link['href']) . '">' . $this->t($link['text']) . '</a></li>';
		}
	?>
	</ul>


<?php
	if (array_key_exists('warnings', $this->data) && is_array($this->data['warnings']) && !empty($this->data['warnings'])) {

		echo '<h2>' . $this->t('warnings') . '</h2>';

		foreach($this->data['warnings'] AS $warning) {
			echo '<div class="caution">' . $this->t($warning) . '</div>';
		}
	}
?>
<?php 
if ($this->data['isadmin']) {

	echo '<h2>'. $this->t('checkphp') . '</h2>';
	echo '<div class="enablebox"><table>';
	
	
	$icon_enabled  = '<img src="/' . $this->data['baseurlpath'] . 'resources/icons/accept.png" alt="enabled" />';
	$icon_disabled = '<img src="/' . $this->data['baseurlpath'] . 'resources/icons/delete.png" alt="disabled" />';
	
	
	foreach ($this->data['funcmatrix'] AS $func) {
		echo '<tr class="' . ($func['enabled'] ? 'enabled' : 'disabled') . '"><td>' . ($func['enabled'] ? $icon_enabled : $icon_disabled) . '</td>
		<td>' . $this->t($func['required']) . '</td><td>' . $func['descr'] . '</td></tr>';
	}

}

?>
</table>
</div>



</div> <!-- #configuration -->

<div id="metadata">


<?php


function mtype($set) {
	switch($set) {
		case 'saml20-sp-remote': return '{admin:metadata_saml20-sp}';
		case 'saml20-sp-hosted': return '{admin:metadata_saml20-sp}';
		case 'saml20-idp-remote': return '{admin:metadata_saml20-idp}';
		case 'saml20-idp-hosted': return '{admin:metadata_saml20-idp}';
		case 'shib13-sp-remote': return '{admin:metadata_shib13-sp}';
		case 'shib13-sp-hosted': return '{admin:metadata_shib13-sp}';
		case 'shib13-idp-remote': return '{admin:metadata_shib13-idp}';
		case 'shib13-idp-hosted': return '{admin:metadata_shib13-idp}';
	}
}

echo '<dl>';
if (is_array($this->data['metaentries']['hosted']) && count($this->data['metaentries']['hosted']) > 0)
foreach ($this->data['metaentries']['hosted'] AS $hm) {
	echo '<dt>' . $this->t(mtype($hm['metadata-set'])) . '</tt></dt>';
	echo '<dd>';
	echo '<p>Entity ID: ' . $hm['entityid'];
	if ($hm['entityid'] !== $hm['metadata-index']) 
		echo '<br />Index: ' . $hm['metadata-index'];
	if (array_key_exists('name', $hm))
		echo '<br /><strong>' . $this->getTranslation(SimpleSAML_Utilities::arrayize($hm['name'], 'en')) . '</strong>';
	if (array_key_exists('descr', $hm))
		echo '<br /><strong>' . $this->getTranslation(SimpleSAML_Utilities::arrayize($hm['descr'], 'en')) . '</strong>';
	
	echo '<br  />[ <a href="' . $hm['metadata-url'] . '">' . $this->t('show_metadata') . '</a> ]';
	
	echo '</dd>';
}
echo '</dl>';

if (is_array($this->data['metaentries']['remote']) && count($this->data['metaentries']['remote']) > 0)
foreach($this->data['metaentries']['remote'] AS $setkey => $set) {
	
	echo '<fieldset><legend>' . $this->t(mtype($setkey)) . ' (Trusted)</legend>';
	echo '<ul>';
	foreach($set AS $entry) {
		echo '<li>';
		if (array_key_exists('name', $entry)) {
			echo $this->getTranslation(SimpleSAML_Utilities::arrayize($entry['name'], 'en'));
		} else {
			echo $entry['entityid'];
		}
		echo '</li>';
	}
	echo '</ul>';
	echo '</fieldset>';
}




?>





<h2><?php echo $this->t('tools'); ?></h2>
	<ul>
	<?php
	
		foreach ($this->data['links_meta'] AS $link) {
			echo '<li><a href="' . htmlspecialchars($link['href']) . '">' . $this->t($link['text']) . '</a></li>';
		}
	?>
	</ul>



		




</div> <!-- #metadata -->

</div> <!-- #tabdiv -->

		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>