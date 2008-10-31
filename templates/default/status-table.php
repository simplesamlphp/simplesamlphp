<?php
$this->includeAtTemplateBase('includes/header.php');

$title = $this->data['title'];
$table = $this->data['table'];

/* Identify column headings. */
$column_titles = array();
foreach($table as $row_title => $row_data) {
	foreach($row_data as $ct => $this->data) {
		if(!in_array($ct, $column_titles)) {
			$column_titles[] = $ct;
		}
	}
}

?>

<h2><?php echo htmlspecialchars($title); ?></h2>

<table>

<tr>
<th></th>
<?php
foreach($column_titles as $ct) {
	echo '<th>' . htmlspecialchars($ct) . '</th>' . "\n";
}
?>
</tr>

<?php
foreach($table as $row_title => $row_data) {
	echo '<tr>' . "\n";
	echo '<th class="rowtitle">' . htmlspecialchars($row_title) . '</th>' . "\n";

	foreach($column_titles as $ct) {
		echo '<td>';

		if(array_key_exists($ct, $row_data)) {
			echo htmlspecialchars($row_data[$ct]);
		}

		echo '</td>' . "\n";
	}

	echo '</tr>' . "\n";
}
?>

</table>

<?php
$this->includeAtTemplateBase('includes/footer.php');
?>