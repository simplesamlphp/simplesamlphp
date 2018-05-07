<!DOCTYPE html>
<html>
<body>
<h1>Pantheon Test</h1>
<p>Identify environmental variables available in this scripting context.</p>
<?php

$output = array();
$output[] = "Starting test.";
if (defined('PANTHEON_ENVIRONMENT')) {
  $output[] = "PANTHEON_ENVIRONMENT is defined";
  if (!empty($_SERVER['PRESSFLOW_SETTINGS'])) {
    $output[] = "PRESSFLOW_SETTINGS are available. ";
    $ps = json_decode($_SERVER['PRESSFLOW_SETTINGS'], TRUE);
    $binding = $ps['conf']['pantheon_binding'];
    $output[] = "binding: $binding";
  }
  else {
    $output[] = "_SERVER['PRESSFLOW_SETTINGS'] is empty.";
  }
  if (!empty($_ENV['PANTHEON_ENVIRONMENT'])) {
    $output[] = "_ENV['PANTHEON_ENVIRONMENT']: " . $_ENV['PANTHEON_ENVIRONMENT'];
  }
  else {
    $output = "_ENV['PANTHEON_ENVIRONMENT'] is empty.";
  }
}
else {
  $output[] = "PANTHEON_ENVIRONMENT is NOT defined";
}

$output[] = "Finishing test.";

$text = implode("<br />" . PHP_EOL, $output);
echo $text;

?>
</body>
</html>
