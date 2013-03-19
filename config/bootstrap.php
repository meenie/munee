<?php

use Munee\asset\Registry;

// DIRECTORY_SEPARATOR alias
defined('DS') || define('DS' , DIRECTORY_SEPARATOR);
// Folder where Munee is located
defined('MUNEE_FOLDER') || define('MUNEE_FOLDER', dirname(__DIR__));
// Define Webroot if hasn't already been defined
defined('WEBROOT') || define('WEBROOT', str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['SCRIPT_FILENAME']));
// Define the cache path
defined('MUNEE_CACHE') || define('MUNEE_CACHE', MUNEE_FOLDER . DS . 'cache');

/**
 * Register the CSS Asset Class with the extensions .css and .less
 */
Registry::register(array('css', 'less', 'scss'), function (\Munee\Request $Request) {
    return new \Munee\asset\type\Css($Request);
});

/**
 * Register the JavaScript Asset Class with the extension .js
 */
Registry::register(array('js', 'coffee'), function (\Munee\Request $Request) {
    return new \Munee\asset\type\JavaScript($Request);
});

/**
 * Register the Image Asset Class with the extensions .jpg, .jpeg, .gif, and .png
 */
Registry::register(array('jpg', 'jpeg', 'gif', 'png'), function (\Munee\Request $Request) {
    return new \Munee\asset\type\Image($Request);
});