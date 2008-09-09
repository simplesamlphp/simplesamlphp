<?php

header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

if(array_key_exists('delete', $_GET)) {
	$deleteId = $_GET['delete'];
	setcookie($deleteId, '', time() - 24*60*60);
	setcookie($deleteId, '', time() - 24*60*60, TRUE);
	header('Location: cookie.php');
	exit;
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Cookie debug</title>
</head>
<body>
<h1>Current cookies</h1>
<table>
<tr><th>Name</th><th>Value</th><th>Delete</th></tr>
<?php
foreach($_COOKIE as $name => $value) {
	echo '<tr>';
        echo '<td>' . htmlspecialchars($name) . '</td>';
	echo '<td>' . htmlspecialchars($value) . '</td>';
	echo '<td><a href="?delete=' . htmlspecialchars($name) . '">Delete</a></td>';
	echo '</tr>';
	echo "\n";
}
?>
</table>
</body>
</html>
