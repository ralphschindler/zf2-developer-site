#!/usr/bin/env php -dphar.readonly=0
<?php

define('DS', DIRECTORY_SEPARATOR);

// check that we have a package name
if (!isset($_SERVER['argv'][1]) || !preg_match('#\w+_\w+#', $_SERVER['argv'][1])) {
    script_exit(__FILE__ . ' expects a package name as the only argument');
} else {
    $package_name = $_SERVER['argv'][1];
}

// did you copy config.ini.orig to the proper place?
if (!file_exists(__DIR__ . '/config.ini')) {
    script_exit('config.ini is required');
}

// load the ini
$ini = parse_ini_file(__DIR__ . '/config.ini');

// does it look ok?
if (!file_exists($ini['zf2_path'] . '/library')) {
    script_exit('The path to the ZF2 repo does not look correct.');
}

$zf2_path = rtrim($ini['zf2_path'], '\\/');
$zf2_library_path = $zf2_path . DS . 'library';

// got milk? i mean - pyrus?
if (!file_exists($ini['pyrus_path'])) {
    script_exit('The path to pyrus does not look correct.');
}

$pyrus_path = $ini['pyrus_path'];

// got milk? i mean - pyrus?
if (!isset($ini['channel'])) {
    script_exit('The proper channel is required.');
}

$channel = $ini['channel'];

if (!isset($ini['release'])) {
    script_exit('The proper release is required.');
}

$release = $ini['release'];

if (strpos(shell_exec($pyrus_path . ' list-channels'), $channel) === false) {
    script_exit('The channel ' . $channel . ' is not in your channel list');
}

if (isset($ini['with_phar']) && $ini['with_phar'] == true && ini_get('phar.readonly') == 'On') {
    script_exit('To build with phar, PHP must have phar.readonly=0');
}

$library_component_path = str_replace('_', DS, $package_name);

if (!file_exists($zf2_library_path . DS . $library_component_path)) {
    script_exit($package_name . ' is not a valid package name, or cannot be found in the ZF2 library');
}

// set cwd, just in case it was called from elsewhere
chdir(__DIR__ . DS . 'working');

$command = $pyrus_path . ' generate-pear2 ' . $package_name . ' ' . $channel;
$output  = script_run_command($command);

$command = 'rm ' . $package_name . DS . 'src' . DS . $library_component_path . DS . 'Main.php';
$output = script_run_command($command);

$command = 'cp -R ' . $zf2_library_path . DS . $library_component_path . DS . '* ' 
    . $package_name . DS . 'src' . DS . $library_component_path . DS;
$output = script_run_command($command);

chdir($package_name);

$command = 'mv RELEASE-0.1.0 RELEASE-' . $release;
$output = script_run_command($command);

$command = 'mv API-0.1.0 API-' . $release;
$output = script_run_command($command);

$command = 'rm package_compatible.xml';
$output = script_run_command($command);

$command = 'rm stub.php';
$output = script_run_command($command);


$file_replacements = array();
$file_replacements['{PACKAGE_NAME}'] = $package_name;
$file_replacements['{PACKAGE_RELEASE}'] = $release;
$file_replacements['{PACKAGE_REQUIRE_DEPENDENCIES}'] = null;

if (file_exists(__DIR__ . DS . 'data' . DS . $package_name . '-info.php')) {
    $package_info = include __DIR__ . DS . 'data' . DS . $package_name . '-info.php';
    if (isset($package_info['dependencies'])) {
        $packagexmlsetup_content = '<?php' . PHP_EOL;
        foreach ($package_info['dependencies'] as $dependency) {
            $file_replacements['{PACKAGE_REQUIRE_DEPENDENCIES}'] .= 'require_once \'' . $dependency . '-' . trim($release) . '.phar\';' . "\n";
            $file_replacements['{PACKAGE_DEPENDENCY}'] = trim($dependency);
            $packagexmlsetup_content .= apply_replacements(file_get_contents(__DIR__ . DS . 'data' . DS . 'packagexmlsetup.php.template'), $file_replacements);
        }
        echo 'Writing: packagexmlsetup.php' . PHP_EOL;
        file_put_contents('packagexmlsetup.php', $packagexmlsetup_content);
    }

}

echo 'Writing: stub.php' . PHP_EOL;
file_put_contents('stub.php', '<?php' . "\n" . trim(apply_replacements(file_get_contents(__DIR__ . DS . 'data' . DS . 'stub.php.template'), $file_replacements)));

$command = $pyrus_path . ' make';
$output = script_run_command($command);
 
$command = $pyrus_path . ' package -g';
$output = script_run_command($command);

$command = $pyrus_path . ' package -z';
$output = script_run_command($command);

if ($ini['with_phar']) {
    $command = $pyrus_path . ' package -p';
    $output = script_run_command($command);
}


function apply_replacements($source, $replacements) {
    foreach ($replacements as $var => $value) {
        $source = str_replace($var, $value, $source);
    }
    return $source;
}

function script_run_command($command) {
    echo 'Running: ' . $command . PHP_EOL;
    return shell_exec($command);
}

function script_exit($reason) {
    echo $reason;
    echo PHP_EOL . PHP_EOL;
    exit -1;
}
