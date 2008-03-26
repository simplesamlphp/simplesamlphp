<?php 
	$this->data['icon'] = 'compass_l.png';
	$this->includeAtTemplateBase('includes/header.php'); 
?>

	<div id="content">

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

		
		<h2><?php echo $this->t('metadata_header'); ?></h2>
			<ul>
			<?php
			
				foreach ($this->data['links_meta'] AS $link) {
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
		
		<?php
			if (array_key_exists('warnings', $this->data) && is_array($this->data['warnings']) && !empty($this->data['warnings'])) {

				echo '<h2>' . $this->t('warnings') . '</h2>';
		
				foreach($this->data['warnings'] AS $warning) {
					echo '<div class="caution">' . $this->t($warning) . '</div>';
				}
			}
		?>
		
		<h2><?php echo $this->t('checkphp'); ?></h2>
		
		
		<div class="enablebox">
		<table>
		
		<?php
		
		$icon_enabled  = '<img src="/' . $this->data['baseurlpath'] . 'resources/icons/accept.png" alt="enabled" />';
		$icon_disabled = '<img src="/' . $this->data['baseurlpath'] . 'resources/icons/delete.png" alt="disabled" />';
		
		
		foreach ($this->data['funcmatrix'] AS $func) {
			echo '<tr class="' . ($func['enabled'] ? 'enabled' : 'disabled') . '"><td>' . ($func['enabled'] ? $icon_enabled : $icon_disabled) . '</td>
			<td>' . $this->t($func['required']) . '</td><td>' . $func['descr'] . '</td></tr>';
		}
		
		?>

		</table>
		</div>
		

	<h2><?php echo $this->t('about_header'); ?></h2>
		<p><?php echo $this->t('about_text'); ?></p>
		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>