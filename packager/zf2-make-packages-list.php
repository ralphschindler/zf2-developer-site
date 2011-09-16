#!/usr/bin/env php -dphar.readonly=0
<?php

// did you copy config.ini.orig to the proper place?
if (!file_exists(__DIR__ . '/config.ini')) {
    script_exit('config.ini is required');
}

// load the ini
$ini = parse_ini_file(__DIR__ . '/config.ini');

$package_list = $ini['package_list'];

$packages = explode(',', $package_list);

foreach ($packages as $package) {
    $return_val = 0;
    $php = '/usr/bin/env php';
    $command = $php . ' -dphar.readonly=0 ./zf2-make-package.php ' . $package;
    echo 'Running: ' . $command . PHP_EOL;
    passthru($command, &$return_val);
    if ($return_val === -1) {
        echo 'Something went terribly wrong' . "\n\n";
        exit -1;
    }
}
