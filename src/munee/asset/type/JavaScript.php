<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\asset\type;

use munee\Request;
use munee\asset\Base;

/**
 * Handles JavaScript
 *
 * @author Cody Lundquist
 */
class JavaScript extends Base
{
    /**
     * Generates the JS content based on the request
     *
     * @param \munee\Request $Request
     */
    public function __construct(Request $Request)
    {
        $this->_cacheDir = CACHE . DS . 'js';
        parent::__construct($Request);
    }

    /**
     * Set additional headers just for CSS
     */
    public function getHeaders()
    {
        header("Content-Type: text/javascript");
    }

    /**
     * Callback function called after the content is collected
     *
     * Doing minification if needed
     */
    protected function _afterFilter()
    {
        if (empty($this->_request->params['minify'])) {
            return;
        }

        $this->_cacheClientSide = true;
        $this->_content = \JShrink\Minifier::minify($this->_content);
    }
}