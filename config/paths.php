<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

// DIRECTORY_SEPARATOR alias
define('DS' , DIRECTORY_SEPARATOR);
// Current working directory alias
define('CWD', dirname(__DIR__));
// Define Webroot
define('WEBROOT', dirname(dirname($_SERVER['SCRIPT_FILENAME'])));
// Define libraries path
define('LIBS', CWD . DS . 'libs');
// Define vendors path
define('VENDORS', CWD . DS . 'vendors');
// Define the cache path
define('CACHE', CWD . DS . 'cache');