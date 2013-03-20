<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee\Mocks;

use Munee\Request;

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