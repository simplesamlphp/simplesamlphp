<?php $this->includeAtTemplateBase('includes/header.php'); ?>

	
	<div id="content">

		<?php if (isset($this->data['header'])) { echo '<h2>' . $this->data['header'] . '</h2>'; } ?>
		
		
		<p>[ List of trusted sites |
		<a href="/<?php echo $this->data['baseurlpath']; ?>/openid/provider/server.php/about">About simpleSAMLphp OpenID</a> ]</p>

		
		<p>These decisions have been remembered for this session. All decisions will be forgotten when the session ends.</p>
		
		
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
								foreach (array('Trusted Sites' => $trusted_sites,
											   'Untrusted Sites' => $untrusted_sites) as
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
					<input type="submit" name="remove" value="Remove Selected" />
					<input type="submit" name="refresh" value="Refresh List" />
					<input type="submit" name="forget" value="Forget All" />
				</form>
			</div>

		<?php } else { ?>
		
			<p>No sites are remembered for this session. When you authenticate with a site,
				you can choose to add it to this list by choosing <q>Remember this decision</q>.
			</p>

		<?php } ?>


		<h2>About simpleSAMLphp</h2>

			<p>Hey! This simpleSAMLphp thing is pretty cool, where can I read more about it?
		You can find more information about simpleSAMLphp at <a href="http://rnd.feide.no">the Feide RnD blog</a> over at <a href="http://uninett.no">UNINETT</a>.</p>
		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>

