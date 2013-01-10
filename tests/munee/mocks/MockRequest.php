<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\mocks;

use munee\Request;

/**
 * Mock Request class for Response Test
 *
 * @author Cody Lundquist
 */
class MockRequest extends Request
{
    /**
     * Override function to just set Extension
     */
    public function __construct()
    {
        $this->ext = 'foo';
    }
}