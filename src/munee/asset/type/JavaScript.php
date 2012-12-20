<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\asset\type;

use munee\asset\Base;

/**
 * Handles JavaScript
 *
 * @author Cody Lundquist
 */
class JavaScript extends Base
{
    /**
     * Set additional headers just for CSS
     */
    public function getHeaders()
    {
        header("Content-Type: text/javascript");
    }

    /**
     * Callback function called after the content is collected but before the content is cached
     *
     * Doing minification if needed
     *
     * @param string $content
     *
     * @return string
     */
    protected function _beforeCreateCacheCallback($content)
    {
        if (! empty($this->_request->params['minify'])) {
            $this->_cacheClientSide = true;
            $content = \JShrink\Minifier::minify($content);
        }

        return $content;
    }
}