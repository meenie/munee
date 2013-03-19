<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee;

/**
 * The outermost layer of Munee that wraps everything in a Try/Catch block and also instantiates the Render Class
 *
 * @author Cody Lundquist
 */
class Dispatcher
{
    /**
     * Dispatch default options
     *
     * @var array
     */
    static $_defaultOptions = array('setHeaders' => true);

    /**
     * 1) Initialise the Request
     * 2) Grab the AssetType based on the request and initialise it
     * 3) Instantiate the Response class, set the headers, and then return the content
     *
     * Rap everything in a Try/Catch block for error handling
     *
     * @param Request $Request
     * @param array $options
     *
     * @return string
     *
     * @catch NotFoundException
     * @catch ErrorException
     */
    public static function run(Request $Request, $options = array())
    {
        try {
            /**
             * Merge in default options
             */
            $options = array_merge(self::$_defaultOptions, $options);
            /**
             * Initialise the Request
             */
            $Request->init();
            /**
             * Grab the correct AssetType
             */
            $AssetType = asset\Registry::getClass($Request);
            /**
             * Initialise the AssetType
             */
            $AssetType->init();
            /**
             * Create a response
             */
            $Response = new Response($AssetType);
            /**
             * Set the headers if told to do so
             */
            if ($options['setHeaders']) {
                $Response->setHeaders();
            }
            /**
             * If the content hasn't been modified return null so only headers are sent
             * otherwise return the content
             */
            return $Response->notModified ? null : $Response->render();
        } catch (asset\NotFoundException $e) {
            header("HTTP/1.0 404 Not Found");
            header("Status: 404 Not Found");
            return 'Error: ' . $e->getMessage();
        } catch (ErrorException $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
}