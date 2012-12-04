<?php

use munee\asset\Registry;

// DIRECTORY_SEPARATOR alias
define('DS' , DIRECTORY_SEPARATOR);
// Folder where munee is located
define('MUNEE_FOLDER', dirname(__DIR__));
// Define Webroot if hasn't already been defined
defined('WEBROOT') || define('WEBROOT', $_SERVER['DOCUMENT_ROOT']);
// Define the cache path
define('CACHE', MUNEE_FOLDER . DS . 'cache');

/**
 * Register the CSS Asset Class with the extensions .css and .less
 */
Registry::register(array('css', 'less'), function (\munee\Request $Request) {
    $Css = new \munee\asset\type\Css($Request);

    return $Css;
});

/**
 * Register the JavaScript Asset Class with the extension .js
 */
Registry::register('js', function (\munee\Request $Request) {
    $JavaScript = new \munee\asset\type\JavaScript($Request);

    return $JavaScript;
});

/**
 * Register the Image Asset Class with the extensions .jpg, .jpeg, .gif, and .png
 */
Registry::register(array('jpg', 'jpeg', 'gif', 'png'), function (\munee\Request $Request) {
    $Request->minify = true;
    $Image = new \munee\asset\type\Image($Request);

    return $Image;
});