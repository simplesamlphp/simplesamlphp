<?php 



$this->includeAtTemplateBase('includes/header.php'); 
	


?>


<!-- 
<div id="tabdiv">
<ul>
	<li><a href="#welcome"><?php echo $this->t('{core:frontpage:welcome}'); ?></a></li>
	<li><a href="#configuration"><?php echo $this->t('{core:frontpage:configuration}'); ?></a></li>
	<li><a href="#metadata"><?php echo $this->t('{core:frontpage:metadata}'); ?></a></li>
</ul> -->
<?php
if ($this->data['isadmin']) {
	echo '<p style="float: right">' . $this->t('{core:frontpage:loggedin_as_admin}') . '</p>';
} else {
	echo '<p style="float: right"><a href="' . $this->data['loginurl'] . '">' . $this->t('{core:frontpage:login_as_admin}') . '</a></p>';
}
?>



	





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

$now = time();
echo '<dl>';
if (is_array($this->data['metaentries']['hosted']) && count($this->data['metaentries']['hosted']) > 0)
foreach ($this->data['metaentries']['hosted'] AS $hm) {
	echo '<dt>' . $this->t(mtype($hm['metadata-set'])) . '</dt>';
	echo '<dd>';
	echo '<p>Entity ID: ' . $hm['entityid'];
	if ($hm['entityid'] !== $hm['metadata-index']) 
		echo '<br />Index: ' . $hm['metadata-index'];
	if (array_key_exists('name', $hm))
		echo '<br /><strong>' . $this->getTranslation(SimpleSAML_Utilities::arrayize($hm['name'], 'en')) . '</strong>';
	if (array_key_exists('descr', $hm))
		echo '<br /><strong>' . $this->getTranslation(SimpleSAML_Utilities::arrayize($hm['descr'], 'en')) . '</strong>';

	echo '<br  />[ <a href="' . $hm['metadata-url'] . '">' . $this->t('{core:frontpage:show_metadata}') . '</a> ]';
	
	echo '</dd>';
}
echo '</dl>';

if (is_array($this->data['metaentries']['remote']) && count($this->data['metaentries']['remote']) > 0)
foreach($this->data['metaentries']['remote'] AS $setkey => $set) {
	
	echo '<fieldset class="fancyfieldset"><legend>' . $this->t(mtype($setkey)) . ' (Trusted)</legend>';
	echo '<ul>';
	foreach($set AS $entry) {
		echo '<li>';
		if (array_key_exists('name', $entry)) {
			echo $this->getTranslation(SimpleSAML_Utilities::arrayize($entry['name'], 'en'));
		} else {
			echo $entry['entityid'];
		}
		
		if (array_key_exists('expire', $entry)) {
			if ($entry['expire'] < $now) {
				echo('<span style="color: #500; font-weight: bold"> (expired ' . number_format(($now - $entry['expire'])/3600, 1) . ' hours ago)</span>');
			} else {
				echo(' (expires in ' . number_format(($entry['expire'] - $now)/3600, 1) . ' hours)');
			}
		}
		echo '</li>';
	}
	echo '</ul>';
	echo '</fieldset>';
}




?>





<h2><?php echo $this->t('{core:frontpage:tools}'); ?></h2>
<ul>
<?php
	foreach ($this->data['links_federation'] AS $link) {
		echo '<li><a href="' . htmlspecialchars($link['href']) . '">' . $this->t($link['text']) . '</a></li>';
	}
?>
</ul>















	

		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>