<?php

/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */
$ds = DIRECTORY_SEPARATOR;
$muneePath = __DIR__ . $ds . '..';

spl_autoload_register(function ($class) use ($ds, $muneePath) {
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    foreach (array('src', 'tests') as $dirPrefix) {
        $file = $muneePath . $ds . $dirPrefix . $ds . $path . '.php';
        if (file_exists($file)) {
            require_once $file;
            break;
        }
    }
});

defined('WEBROOT') || define('WEBROOT', __DIR__ . $ds . 'tmp');

require_once $muneePath . $ds . 'config' . $ds . 'bootstrap.php';