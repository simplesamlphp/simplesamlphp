<?php
$this->data['header'] = 'Log peek';
$this->includeAtTemplateBase('includes/header.php');


?>

<h2>SimpleSAMLphp logs (admin utility)</h2>

<form method="get" action="?">
	<input type="text" name="tag" value="<?php echo $this->data['trackid']; ?>" />
	<input type="submit" value="Show logs" />
</form>


<pre style="background: #eee; border: 1px solid #666; padding: 1em; margin: .4em; overflow: scroll">
<?php

if (!empty($this->data['results'])) {
	foreach($this->data['results'] AS $line) {
		echo $line;
	}
}

?>
</pre>
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>