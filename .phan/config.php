<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'][] = 'vendor/sendgrid';
$cfg['exclude_analysis_directory_list'][] = 'vendor/sendgrid';

return $cfg;
