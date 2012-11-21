<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

// Set the path constants
require 'paths.php';

// Register LIBS and VENDORS as SPL Autoloader directories
require LIBS . DS . 'munee' . DS . 'Loader.php';
\munee\Loader::register(array(LIBS, VENDORS));