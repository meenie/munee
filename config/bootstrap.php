<?php

use \munee\asset\Registry;

// DIRECTORY_SEPARATOR alias
define('DS' , DIRECTORY_SEPARATOR);
// Config Folder
define ('CONFIG', dirname(__DIR__) . DS . 'config');
// Folder where munee is located
define('MUNEE_FOLDER', dirname(dirname(dirname(__DIR__))));
// Define Webroot if hasn't already been defined
defined('WEBROOT') || define('WEBROOT', $_SERVER['DOCUMENT_ROOT']);
// Define the cache path
define('CACHE', MUNEE_FOLDER . DS . 'cache');

/**
 * Register the CSS Asset Class
 */
Registry::register(array('css', 'less'), function (\munee\Request $Request) {
    $Css = new \munee\asset\Css($Request);

    return $Css;
});

/**
 * Register the JavaScript Asset Class
 */
Registry::register('js', function (\munee\Request $Request) {
    $JavaScript = new \munee\asset\JavaScript($Request);

    return $JavaScript;
});

/**
 * Register the Image Asset Class
 */
Registry::register(array('jpg', 'jpeg', 'gif', 'png'), function (\munee\Request $Request) {
    $Image = new \munee\asset\Image($Request);

    return $Image;
});