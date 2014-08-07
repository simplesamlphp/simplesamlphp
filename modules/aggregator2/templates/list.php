<?php
$this->includeAtTemplateBase('includes/header.php');
?>
    <h1><?php echo $this->t('{aggregator2:aggregator:header}'); ?></h1>

<?php
if (count($this->data['sources']) === 0) {
    echo "    <p>".$this->t('{aggregator2:aggregator:no_aggregators}')."</p>\n";
} else {
    echo "    <ul>";

    foreach ($this->data['sources'] as $id => $source) {
        $encId = urlencode($id);
        $params = array(
            'id' => $encId,
        );
        echo str_repeat(' ', 8)."<li>\n";
        echo str_repeat(' ', 12).'<a href="';
        echo SimpleSAML_Module::getModuleURL('aggregator2/get.php', $params).'">'.htmlspecialchars($id)."</a>\n";
        echo str_repeat(' ', 12).'<a href="';
        $params['mimetype'] = 'text/plain';
        echo SimpleSAML_Module::getModuleURL('aggregator2/get.php', $params).'">['.
            $this->t('{aggregator2:aggregator:text}')."]</a>\n";
        echo str_repeat(' ', 12).'<a href="';
        $params['mimetype'] = 'application/xml';
        echo SimpleSAML_Module::getModuleURL('aggregator2/get.php', $params)."\">[XML]</a>\n";
        echo str_repeat(' ', 8)."</li>\n";
    }

    echo "    </ul>\n";
}

$this->includeAtTemplateBase('includes/footer.php');
