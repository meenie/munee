<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee;

class Loader
{
    /**
     * @var array
     */
    static $paths = array();

    /**
     * Register one or more directories
     * @param $paths string|array
     */
    public static function register($paths)
    {
        static::$paths = array_merge(static::$paths, (array) $paths);
        spl_autoload_register('munee\Loader::_load');
    }

    /**
     * Autoloader Function
     * @param $class
     *
     * @return boolean
     * @throws LoaderException
     */
    protected static function _load($class)
    {
        foreach (static::$paths as $path) {
            $fileName = $path . DS . str_replace('\\', DS, $class) . '.php';
            if (file_exists($fileName)) {
                return require $fileName;
            }
        }

        throw new LoaderException("The following class could not be loaded: {$class}");
    }
}

class LoaderException extends \Exception {}