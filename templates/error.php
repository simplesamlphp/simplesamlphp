<?php 
	$this->data['header'] = $this->t('error_header');
	$this->data['icon'] = 'bomb_l.png';
	
	$this->data['head'] = '
<meta name="robots" content="noindex, nofollow" />
<meta name="googlebot" content="noarchive, nofollow" />';
	
	$this->includeAtTemplateBase('includes/header.php'); 
?>


	<h2><?php 
		echo $this->t('title_' . $this->data['errorcode']); 
	?></h2>

<?php
$descr = $this->t('descr_' . $this->data['errorcode'], $this->data['parameters']);
if($descr) {
	echo htmlspecialchars($descr);
}
?>

<?php
/* Print out the track id if it exists. */
if(array_key_exists('trackid', $this->data)) {
?>
	<div class="trackidtext">
		<?php echo $this->t('report_trackid'); ?>
		<span class="trackid"><?php echo $this->data['trackid']; ?></span>
	</div>
<?php
}
?>
		

<?php
/* Print out exception only if the exception is available. */
if (array_key_exists('showerrors', $this->data) && $this->data['showerrors']) {
?>
		<h2><?php echo $this->t('debuginfo_header'); ?></h2>
		<p><?php echo $this->t('debuginfo_text'); ?></p>
		
		<div style="border: 1px solid #eee; padding: 1em; font-size: x-small">
			<p style="margin: 1px"><?php echo htmlentities($this->data['exceptionmsg']); ?></p>
			<pre style=" padding: 1em; font-family: monospace; "><?php echo htmlentities($this->data['exceptiontrace']); ?>
			</pre>
		</div>
<?php
}
?>

<?php
/* Add error report submit section if we have a valid technical contact. 'errorreportaddress' will only be set if
 * the technical contact email address has been set.
 */
if (!empty($this->data['errorreportaddress'])) {
?>

	<h2><?php echo $this->t('report_header'); ?></h2>
	<form action="<?php echo htmlspecialchars($this->data['errorreportaddress']); ?>" method="post">
	
		<p><?php echo $this->t('report_text'); ?></p>
			<p><?php echo $this->t('report_email'); ?> <input type="text" size="25" name="email" value="<?php echo($this->data['email']); ?>" />
	
		<p>
		<textarea style="width: 300px; height: 100px" name="text"><?php echo $this->t('report_explain'); ?></textarea>
		</p><p>
		<input type="hidden" name="version" value="<?php echo htmlspecialchars($this->data['version']); ?>" />
		<input type="hidden" name="trackid" value="<?php echo htmlspecialchars($this->data['trackid']); ?>" />
		<input type="hidden" name="exceptionmsg" value="<?php echo htmlspecialchars($this->data['exceptionmsg']); ?>" />
		<input type="hidden" name="exceptiontrace" value="<?php echo htmlspecialchars($this->data['exceptiontrace']); ?>" />
		<input type="hidden" name="errorcode" value="<?php echo htmlspecialchars($this->data['errorcode']); ?>" />
		<input type="hidden" name="parameters" value="<?php echo htmlspecialchars(var_export($this->data['parameters'], TRUE)); ?>" />
		<input type="hidden" name="url" value="<?php echo htmlspecialchars($this->data['url']); ?>" />

		<input type="submit" name="send" value="<?php echo $this->t('report_submit'); ?>" />
		</p>
	</form>
<?php
}
?>

<h2 style="clear: both"><?php echo $this->t('howto_header'); ?></h2>

<p><?php echo $this->t('howto_text'); ?></p>


<?php $this->includeAtTemplateBase('includes/footer.php'); ?>