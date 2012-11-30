<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee;

use munee\asset\AssetNotFoundException;

class Dispatcher
{
    /**
     * @param Request $Request
     *
     * @return string
     *
     * @throws AssetNotFoundException
     * @throws ErrorException
     */
    public static function run(Request $Request)
    {
        try {
            $response = new Response($Request);
            return $response->render();
        } catch (AssetNotFoundException $e) {
            header("HTTP/1.0 404 Not Found");
            header("Status: 404 Not Found");
            return 'Error: ' . $e->getMessage();
        } catch (ErrorException $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
}