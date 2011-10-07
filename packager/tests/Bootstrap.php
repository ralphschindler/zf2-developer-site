<?php
/*
 * E_STRICT compliance
 */
error_reporting( E_ALL | E_STRICT );

/*
 * Setup the include path
 */
set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__DIR__) . '/library',
    dirname(__DIR__) . '/tests',
    get_include_path(),
)));

/**
 * Setup autoloading
 */
spl_autoload_register(function($class) {
    $class = ltrim($class, '\\');
    $file = str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, $class) . '.php';
    if (false === ($realpath = stream_resolve_include_path($file))) {
        return false;
    }
    include_once $realpath;
});
