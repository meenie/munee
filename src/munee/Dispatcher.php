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
     * Instantiate the Response class and wrap everything in a Try/Catch block for error handling
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
            $Response = new Response($Request);

            return $Response->render();
        } catch (asset\NotFoundException $e) {
            header("HTTP/1.0 404 Not Found");
            header("Status: 404 Not Found");
            return 'Error: ' . $e->getMessage();
        } catch (ErrorException $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
}