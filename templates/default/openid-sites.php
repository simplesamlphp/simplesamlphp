<?php
if (isset($this->data['header']) && $this->getTag($this->data['header']) !== NULL) {
	$this->data['header'] = $this->t($this->data['header']);
}

$this->includeAtTemplateBase('includes/header.php');
?>



		<?php if (isset($this->data['header'])) { echo '<h2>' . $this->data['header'] . '</h2>'; } ?>
		
		
		<p>[ <?php echo($this->t('{openid:list_trusted_sites}')); ?> |
		<a href="/<?php echo $this->data['baseurlpath']; ?>/openid/provider/server.php/about"><?php echo($this->t('{openid:about_link}')); ?></a> ]</p>

		
		<p><?php echo($this->t('{openid:trustlist_desc}')); ?></p>
		
		
		<?php if (isset($this->data['sites'])) { ?>
		
			<div class="form">
				<form method="post" action="<?php echo '/' . $this->data['baseurlpath'] . 'openid/provider/server.php/sites'; ?>">
					<table>
						<tbody>
							<?php 
							
							    $trusted_sites = array();
								$untrusted_sites = array();
								foreach ($this->data['sites'] as $site => $trusted) {
									if ($trusted) {
										$trusted_sites[] = $site;
									} else {
										$untrusted_sites[] = $site;
									}
								}
								
								$i = 0;
								foreach (array($this->t('{openid:trustlist_trustedsites}') => $trusted_sites,
											   $this->t('{openid:trustlist_untrustedsites}') => $untrusted_sites) as
										 $name => $sites) {
									if ($sites) {
										echo '<tr><th colspan="2">'. htmlspecialchars($name) . '</th></tr>';
										foreach ($sites as $site) {
											$siteid = 'site' . $i;
											echo '<tr>
													<td><input type="checkbox" name="' . $siteid . '" value="' . 
														htmlspecialchars($site, ENT_QUOTES) . '" id="' . $siteid . '" /></td>
													<td><label for="' . $siteid . '"><code>' . htmlspecialchars($site, ENT_QUOTES) . '</code></label></td>
												</tr>';
											$i += 1;
										}
									}
								}
							
							
							?>
						</tbody>
					</table>
					<input type="submit" name="remove" value="<?php echo($this->t('{openid:trustlist_remove}')); ?>" />
					<input type="submit" name="refresh" value="<?php echo($this->t('{openid:trustlist_refresh}')); ?>" />
					<input type="submit" name="forget" value="<?php echo($this->t('{openid:trustlist_forget}')); ?>" />
				</form>
			</div>

		<?php } else { ?>
		
			<p><?php echo($this->t('{openid:trustlist_nosites}')); ?></p>

		<?php } ?>


		<h2><?php echo $this->t('{frontpage:about_header}'); ?></h2>
		<p><?php echo $this->t('{frontpage:about_text}'); ?></p>
		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>

