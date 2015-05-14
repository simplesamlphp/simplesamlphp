<?php
$this->includeAtTemplateBase('includes/header.php');

echo('<h1>Import SAML 2.0 XML Metadata</h1>');

echo('<form method="post" action="edit.php">');
echo('<p>Paste in SAML 2.0 XML Metadata for the entity that you would like to add.</p>');
echo('<textarea style="height: 200px; width: 90%; border: 1px solid #aaa;" cols="50" rows="5" name="xmlmetadata"></textarea>');
echo('<input type="submit" style="margin-top: .5em" name="metasubmit" value="Import metadata" />');
echo('</form>');


echo('<p style="float: right"><a href="index.php">Return to entity listing</a></p>');

$this->includeAtTemplateBase('includes/footer.php');

