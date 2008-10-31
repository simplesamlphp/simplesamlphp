<?php
if (isset($this->data['header']) && $this->getTag($this->data['header']) !== NULL) {
	$this->data['header'] = $this->t($this->data['header']);
}

$this->includeAtTemplateBase('includes/header.php');
?>



		<?php if (isset($this->data['header'])) { echo '<h2>' . $this->data['header'] . '</h2>'; } ?>
		
		
		<p>[ <a href="/<?php echo $this->data['baseurlpath']; ?>/openid/provider/server.php/sites"><?php echo($this->t('{openid:list_trusted_sites}')); ?></a> |
		<a href="/<?php echo $this->data['baseurlpath']; ?>/openid/provider/server.php/about"><?php echo($this->t('{openid:about_link}')); ?></a> ]</p>
		
		<div class="form">
<?php
$params = array(
	'%OPENIDURL%' => '<code>' . htmlspecialchars($this->data['openidurl']) . '</code>',
	'%SITEURL%' => '<code>' . $this->data['siteurl'] . '</code>',
	);
echo('<p>' . $this->t('{openid:confirm_question}', $params) . '</p>');
?>
		  <form method="post" action="<?php echo $this->data['trusturl']; ?>">
			<input type="checkbox" name="remember" value="on" id="remember"><label
				for="remember"><?php echo($this->t('{openid:remember}')); ?></label>
			<br />
			<input type="submit" name="trust" value="<?php echo($this->t('{openid:confirm}')); ?>" />
			<input type="submit" value="<?php echo($this->t('{openid:notconfirm}')); ?>" />
		  </form>
		</div>

		
		<h2><?php echo $this->t('{frontpage:about_header}'); ?></h2>
		<p><?php echo $this->t('{frontpage:about_text}'); ?></p>
		
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>