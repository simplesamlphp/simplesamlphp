<?php
/* A configuration desigend for tests that need no configuration options set yet the SSP requires a config.php to have
been loaded.
*/
$config = array(
    // We need to set at least one key=value pair to avoid validation issues loading the file
    'some_example_option' => 'a',
);