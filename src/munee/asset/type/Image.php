<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\asset\type;

use munee\asset\Base;
use munee\asset\NotFoundException;
use munee\asset\type\image\Filter;

/**
 * Handles Images
 *
 * @author Cody Lundquist
 */
class Image extends Base
{
    /**
     * Overload the _getFileContent of Base and handle images a bit different
     *
     * @param string $image
     *
     * @return string
     *
     * @throws NotFoundException
     */
    protected function _getFileContent($image)
    {
        if (! file_exists($image)) {
            throw new NotFoundException('Image could not be found: ' . str_replace(WEBROOT, '', $image));
        }

        $filteredImage = Filter::run($image, $this->_request->params);
        $this->_lastModifiedDate = $filteredImage['changed'] ? time() : filemtime($filteredImage['image']);

        return file_get_contents($filteredImage['image']);
    }

    /**
     * Set additional headers just for an Image
     */
    public function getHeaders()
    {
        switch ($this->_request->ext) {
            case 'jpg':
            case 'jpeg':
                header("Content-Type: image/jpg");
                break;
            case 'png':
                header("Content-Type: image/png");
                break;
            case 'gif':
                header("Content-Type: image/gif");
                break;
        }
    }
}