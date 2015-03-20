<?php

use Munee\Asset\Registry;
// DIRECTORY_SEPARATOR alias
defined('DS') || define('DS' , DIRECTORY_SEPARATOR);
// Define Sub-Folder the Munee Dispatcher file is in
$subFolder = dirname($_SERVER['SCRIPT_NAME']);
defined('SUB_FOLDER') || define('SUB_FOLDER', '/' === $subFolder ? '' : $subFolder);
// Define Webroot if hasn't already been defined
defined('WEBROOT') || define('WEBROOT', str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['SCRIPT_FILENAME']) . SUB_FOLDER);
// Folder where Munee is located
defined('MUNEE_FOLDER') || define('MUNEE_FOLDER', dirname(__DIR__));
// Define the cache path
defined('MUNEE_CACHE') || define('MUNEE_CACHE', MUNEE_FOLDER . DS . 'cache');
// Define default character encoding
defined('MUNEE_CHARACTER_ENCODING') || define('MUNEE_CHARACTER_ENCODING', 'UTF-8');
// Are we using Munee with URL Rewrite (.htaccess file)?
$requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
defined('MUNEE_USING_URL_REWRITE') || define('MUNEE_USING_URL_REWRITE', strpos($requestUri, 'files=') === false);
// Munee dispatcher file if not using URL Rewrite
defined('MUNEE_DISPATCHER_FILE') || define('MUNEE_DISPATCHER_FILE', ! MUNEE_USING_URL_REWRITE ? $_SERVER['SCRIPT_NAME'] : '');

// If mbstring is installed, set the encoding default
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding(MUNEE_CHARACTER_ENCODING);
}

/**
 * Register the CSS Asset Class with the extensions .css, .less, and .scss
 */
Registry::register(array('css', 'less', 'scss'), function (\Munee\Request $Request) {
    return new \Munee\Asset\Type\Css($Request);
});

/**
 * Register the JavaScript Asset Class with the extension .js
 */
Registry::register(array('js', 'coffee'), function (\Munee\Request $Request) {
    return new \Munee\Asset\Type\JavaScript($Request);
});

/**
 * Register the Image Asset Class with the extensions .jpg, .jpeg, .gif, and .png
 */
Registry::register(array('jpg', 'jpeg', 'gif', 'png'), function (\Munee\Request $Request) {
    return new \Munee\Asset\Type\Image($Request);
});
