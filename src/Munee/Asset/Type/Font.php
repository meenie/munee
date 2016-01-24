<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee\Asset\Type;

use Munee\Asset\Type;
use CoffeeScript;

/**
 * Handles Font files
 *
 * @author Gregory Goijaers
 */
class Font extends Type
{
    /**
     * Set headers for Font
     */
    public function getHeaders()
    {
        $this->response->headerController->headerField('Content-Type', 'application/x-opentype');
        // TODO check if the header is correct
    }

}
