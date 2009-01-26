<?php
if (isset($this->data['header']) && $this->getTag($this->data['header']) !== NULL) {
	$this->data['header'] = $this->t($this->data['header']);
}

$this->includeAtTemplateBase('includes/header.php');
?>


		<?php if (isset($this->data['header'])) { echo '<h2>' . $this->data['header'] . '</h2>'; } ?>
		
		<p>[ <a href="/<?php echo $this->data['baseurlpath']; ?>/openid/provider/server.php/sites"><?php echo($this->t('{openid:list_trusted_sites}')); ?></a> |
		<?php echo($this->t('{openid:about_link}')); ?> ]</p>

		
		<p><?php echo($this->t('{openid:welcome}')); ?></p>


		<p><?php echo($this->t('{openid:howtouse}')); ?></p>
<pre>&lt;link rel="openid.server" href="<?php echo htmlspecialchars($this->data['openidserver']); ?>" /&gt;
&lt;link rel="openid.delegate" href="<?php echo htmlspecialchars($this->data['openiddelegation']); ?>" /&gt;
		
		</pre>
		
		
		<p><?php
			
			if (isset($this->data['userid'])) {
				echo($this->t('{openid:loggedinas}', array('%USERID%' => htmlspecialchars($this->data['userid']))));
			} else {
				echo('<a href="' . htmlspecialchars($this->data['initssourl']) . '">' . $this->t('{openid:login}') . '</a>');
			}
		
		?>
		
		<p>
<?php
$param = array(
	'%SITE%' => '<a href="http://www.openidenabled.com/">openidenabled.com</a>',
	'%TOOL%' => '<a href="http://www.openidenabled.com/resources/openid-test/checkup">' . $this->t('{openid:checkup_tool}') . '</a>',
	);
echo($this->t('{openid:howtouse_cont}', $param));
?>
		  <form method="post"
				action="http://www.openidenabled.com/resources/openid-test/checkup/start">
			<label for="checkup"><?php echo($this->t('{openid:openid_url}')); ?>
			</label><input id="checkup" type="text" name="openid_url" />
			<input type="submit" value="<?php echo($this->t('{openid:check}')); ?>" />
		  </form>
		</p>

		
		<h2><?php echo $this->t('{frontpage:about_header}'); ?></h2>
		<p><?php echo $this->t('{frontpage:about_text}'); ?></p>
		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>

