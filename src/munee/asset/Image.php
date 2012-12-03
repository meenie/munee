<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\asset;

use \munee\asset\Base;
use \munee\asset\NotFoundException;

/**
 * Handles JavaScript
 *
 * @author Cody Lundquist
 */
class Image extends Base
{
    /**
     * Generates the JS content based on the request
     *
     * @return string
     * @throws NotFoundException
     */
    protected function _getContent()
    {
        $imageCacheDir = CACHE . DS . 'images';
        $this->_createDir($imageCacheDir);

        $file = WEBROOT . array_shift($this->_request->files);

        if (! file_exists($file)) {
            throw new NotFoundException('Image could not be found: ' . $file);
        }
        $this->_lastModifiedDate = filemtime($file);

        return file_get_contents($file);
    }

    /**
     * Set additional headers just for CSS
     */
    protected function _getHeaders()
    {
        header("Content-Type: image/png");
    }
}