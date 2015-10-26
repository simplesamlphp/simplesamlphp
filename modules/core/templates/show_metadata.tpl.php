<?php 

$this->includeAtTemplateBase('includes/header.php'); 
?>
<pre class="metadatabox">
$metadata['<?php echo $this->data['m']['metadata-index']; unset($this->data['m']['metadata-index']) ?>'] => <?php
    echo htmlspecialchars(var_export($this->data['m'], true));
?>
</pre>
<p>[ <a href="<?php echo $this->data['backlink']; ?>">back</a> ]</p>

<?php
$this->includeAtTemplateBase('includes/footer.php'); 

