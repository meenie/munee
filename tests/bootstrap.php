<?php

/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */
spl_autoload_register(function ($class) {
    $ds = DIRECTORY_SEPARATOR;
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    foreach (array('src', 'tests') as $dirPrefix) {
        $file = dirname(__DIR__) . $ds . $dirPrefix . $ds . $path . '.php';
        if (file_exists($file)) {
            require_once $file;
            break;
        }
    }
});