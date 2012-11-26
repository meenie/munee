<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee;

use munee\Response;

class Dispatcher
{
    public static function run(\munee\Request $request)
    {
        static::_setPaths();
        $response = new Response($request);

        return $response->render();
    }

    protected static function _setPaths()
    {
        // DIRECTORY_SEPARATOR alias
        define('DS' , DIRECTORY_SEPARATOR);
        // Current working directory alias
        define('MUNEE_FOLDER', dirname(dirname(__DIR__)));
        // Define Webroot
        defined('WEBROOT') || define('WEBROOT', $_SERVER['DOCUMENT_ROOT']);
        // Define the cache path
        define('CACHE', MUNEE_FOLDER . DS . 'cache');
    }
}