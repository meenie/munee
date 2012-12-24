<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\asset\type;

use munee\asset\Type;

/**
 * Handles JavaScript
 *
 * @author Cody Lundquist
 */
class JavaScript extends Type
{
    /**
     * Set headers for JavaScript
     */
    public function getHeaders()
    {
        header("Content-Type: text/javascript");
    }
}