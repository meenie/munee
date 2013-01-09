<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee;

/**
 * The outermost layer of Munee that wraps everything in a Try/Catch block and also instantiates the Render Class
 *
 * @author Cody Lundquist
 */
class Dispatcher
{
    /**
     * 1) Initialise the Request
     * 2) Grab the AssetType based on the request and initialise it
     * 3) Instantiate the Response class, set the headers, and then return the content
     *
     * Rap everything in a Try/Catch block for error handling
     *
     * @param Request $Request
     *
     * @return string
     *
     * @catch NotFoundException
     * @catch ErrorException
     */
    public static function run(Request $Request)
    {
        try {
            // Initialise the Request
            $Request->init();
            // Grab the correct AssetType
            $AssetType = asset\Registry::getClass($Request);
            // Initialise the AssetType
            $AssetType->init();
            // Create a response
            $Response = new Response($AssetType);
            // Set The Headers
            $Response->setHeaders();
            if (! $Response->notModified) {
                // Return the content
                return $Response->render();
            } else {
                return null;
            }
        } catch (asset\NotFoundException $e) {
            header("HTTP/1.0 404 Not Found");
            header("Status: 404 Not Found");
            return 'Error: ' . $e->getMessage();
        } catch (ErrorException $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
}