<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\asset;

use \munee\ErrorException;
use \Closure;

class Registry
{
    /**
     * @var string Registered Classes
     */
    protected static $registry = array();

    /**
     * Add a new resolver to the registry array.
     *
     * @param string|array $extensions
     * @param Closure $resolve
     */
    public static function register($extensions, Closure $resolve)
    {
        static::$registry[] = compact('extensions', 'resolve');
    }


    /**
     * Return the AssetClass based on the file extension in the Request Class
     *
     * @param \munee\Request $Request
     *
     * @return Object
     *
     * @throws ErrorException
     */
    public static function getClass(\munee\Request $Request)
    {
        foreach (static::$registry as $registered) {
            if (in_array($Request->ext, (array) $registered['extensions'])) {
                return $registered['resolve']($Request);
            }
        }

        throw new ErrorException("The following extension is not handled: {$Request->ext}");
    }
}