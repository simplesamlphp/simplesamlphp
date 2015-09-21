<?php
$this->data['header'] = 'Log peek';
$this->includeAtTemplateBase('includes/header.php');
?>

<h2>SimpleSAMLphp logs (admin utility)</h2>

<form method="get" action="?">
	<table>
		<tr><th><label for="start">First entry in logfile</label></th><td id="star"><?php echo $this->data['timestart']; ?></td></tr>
		<tr><th><label for="end">Last entry in logfile</label></th><td id="end"><?php echo $this->data['endtime']; ?></td></tr>
		<tr><th><label for="size">Logfile size</label></th><td id="size"><?php echo $this->data['filesize']; ?></td></tr>
		<tr><th><label for="tag">Tag id for search</label></th><td><input type="text" name="tag" id="tag" value="<?php echo $this->data['trackid']; ?>" /></td></tr>
		<tr><th><input type="submit" value="Search log" /></th><td></td></tr>
	</table>
</form>

<pre style="background: #eee; border: 1px solid #666; padding: 1em; margin: .4em; overflow: scroll">
<?php
if (!empty($this->data['results'])) {
	foreach($this->data['results'] AS $line) {
		echo htmlspecialchars($line) . "\n";
	}
}
?>
</pre>
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>