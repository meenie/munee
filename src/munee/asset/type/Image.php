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
     * Generates the JS content based on the request
     *
     * @param \munee\Request $Request
     *
     * @throws NotFoundException
     */
    public function __construct(Request $Request)
    {
        parent::__construct($Request);

        $file = WEBROOT . array_shift($this->_request->files);

        if (! file_exists($file)) {
            throw new NotFoundException('Image could not be found: ' . $file);
        }

        $file = Filter::run($file, $this->_request->params);

        $this->_cacheClientSide = true;
        $this->_lastModifiedDate = filemtime($file);
        $this->_content = file_get_contents($file);
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