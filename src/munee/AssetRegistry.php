<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee;

use \munee\ErrorException;
use \Closure;

class AssetRegistry
{
    /**
     * @var string Registered Classes
     */
    protected static $registry = array();

    /**
     * Add a new resolver to the registry array.
     *
     * @param  string $name The id
     * @param  Closure $resolve Closure that creates instance
     */
    public static function register($name, Closure $resolve)
    {
        static::$registry[$name] = $resolve;
    }

    /**
     * Return the AssetClass based on the Asset Type in the Request Class
     *
     * @param Request $Request
     *
     * @return Object
     *
     * @throws ErrorException
     */
    public static function getAssetClass(Request $Request)
    {
        if (static::registered($Request->type)) {
            $name = static::$registry[$Request->type];

            return $name($Request);
        }

        throw new ErrorException("The following Asset Type is not registered: {$Request->type}");
    }

    /**
     * Determine whether the id is registered
     *
     * @param  string $name The id
     *
     * @return bool Whether to id exists or not
     */
    public static function registered($name)
    {
        return array_key_exists($name, static::$registry);
    }
}