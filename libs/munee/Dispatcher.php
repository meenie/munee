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
        $response = new Response($request);

        return $response->render();
    }
}