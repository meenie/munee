<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee\Asset;

use Munee\ErrorException;
use Munee\Request;
use Closure;

/**
 * Registers extensions against a Closure which will instantiate the Asset Type handler
 *
 * @author Cody Lundquist
 */
class Registry
{
    /**
     * @var string Registered Classes
     */
    protected static $_registry = array();

    /**
     * Register a resolver with a list of extensions
     *
     * @param string|array $extensions
     * @param Closure $resolve
     */
    public static function register($extensions, Closure $resolve)
    {
        $extensions = (array) $extensions;
        static::$_registry[] = compact('extensions', 'resolve');
    }

    /**
     * Un-Register one or more extensions
     *
     * @param $extensions
     */
    public static function unRegister($extensions)
    {
        foreach ((array) $extensions as $extension) {
            foreach (static::$_registry as &$registered) {
                $key = array_search($extension, $registered['extensions'], true);
                if (false !== $key) {
                    unset($registered['extensions'][$key]);
                }

                if (empty($registered['extensions'])) {
                    unset($registered);
                }
            }
        }
    }

    /**
     * Return the AssetClass based on the file extension in the Request Class
     *
     * @param \Munee\Request $Request
     *
     * @return Object
     *
     * @throws ErrorException
     */
    public static function getClass(Request $Request)
    {
        foreach (static::$_registry as $registered) {
            if (in_array($Request->ext, $registered['extensions'])) {
                return $registered['resolve']($Request);
            }
        }

        throw new ErrorException("The following extension is not handled: {$Request->ext}");
    }

    /**
     * Get Supported Extensions
     *
     * @param string $extension
     *
     * @return array
     *
     * @throws ErrorException
     */
    public static function getSupportedExtensions($extension)
    {
        foreach (static::$_registry as $registered) {
            if (in_array($extension, (array) $registered['extensions'])) {
                return $registered['extensions'];
            }
        }

        throw new ErrorException("The following extension is not handled: {$extension}");
    }
}