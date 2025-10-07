<?php

include('vendor/autoload.php');

$domain = 'messages';
$finder = new Symfony\Component\Finder\Finder();
$moduleLocalesDir = './modules/admin/locales/';
$finder->files()->in($moduleLocalesDir . '**/LC_MESSAGES/')->name("{$domain}.po");
