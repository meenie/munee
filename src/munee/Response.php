<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee;

use munee\asset\Registry;

/**
 * Munee Response Class
 */
class Response
{
    /**
     * @var Request
     */
    protected $_request;

    /**
     * Constructor
     *
     * @param Request $Request
     */
    public function __construct(Request $Request)
    {
        $this->_request = $Request;
    }

    /**
     * Instantiates the correct Asset Class and returns a response
     *
     * @return string
     */
    public function render()
    {
        return Registry::getClass($this->_request)->render();
    }
}