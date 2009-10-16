<?php
$this->includeAtTemplateBase('includes/header.php');
?>

<div class="form">
<?php
$params = array(
	'%SITEURL%' => '<code>' . htmlspecialchars($this->data['trustRoot']) . '</code>',
	);
echo('<p>' . $this->t('{openidProvider:op:confirm_question}', $params) . '</p>');
?>
<form method="post" action="?">
<input type="hidden" name="StateID" value="<?php echo $this->data['StateID']; ?>" />

<input type="checkbox" name="TrustRemember" value="on" id="remember" />
<label for="TrustRemember"><?php echo($this->t('{openidProvider:op:remember}')); ?></label>
<br />

<input type="submit" name="TrustYes" value="<?php echo($this->t('{openidProvider:op:confirm}')); ?>" />
<input type="submit" name="TrustNo" value="<?php echo($this->t('{openidProvider:op:notconfirm}')); ?>" />

</form>
</div>

<?php
$this->includeAtTemplateBase('includes/footer.php');
?>