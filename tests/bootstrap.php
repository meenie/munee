<?php

/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
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

require $muneePath . $ds . 'config' . $ds . 'bootstrap.php';