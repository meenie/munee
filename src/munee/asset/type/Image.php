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
use munee\ErrorException;

/**
 * Handles Images
 *
 * @author Cody Lundquist
 */
class Image extends Base
{
    /**
     * @var string
     */
    protected $_imageCacheDir;

    /**
     * @var array
     */
    protected $_allowedParams = array(
        'width' => array(
            'alias' => 'w',
            'regex' => '\d+'
        ),
        'height' => array(
            'alias' => 'h',
            'regex' => '\d+'
        ),
        'crop' => array(
            'alias' => 'c',
            'regex' => 'exact|scaled',
            'default' => 'scaled'
        )
    );
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

        $this->_imageCacheDir = CACHE . DS . 'images';
        $this->_createDir($this->_imageCacheDir);

        $file = WEBROOT . array_shift($this->_request->files);

        if (! file_exists($file)) {
            throw new NotFoundException('Image could not be found: ' . $file);
        }

        if (! empty($this->_request->get['resize'])) {
            $file = $this->_resize($file, $this->_request->get['resize']);
        }

        $this->_cacheClientSide = true;
        $this->_lastModifiedDate = filemtime($file);
        $this->_content = file_get_contents($file);
    }

    /**
     * Set additional headers just for an Image
     */
    public function getHeaders()
    {
        header("Content-Type: image/png");
    }

    /**
     * Use Imagine to resize an image and return it's new path
     *
     * @param string $file - path to file
     * @param string $params
     *
     * @return string
     */
    protected function _resize($file, $params)
    {
        $params = $this->_parseParams($params);
        $hashedName = $this->_generateFileNameHash($file, $params);
        $newFile = $this->_imageCacheDir . DS . $hashedName;

        // No need to recreate it if it already exists
        if (file_exists($newFile)) {
            return $newFile;
        }

        $Imagine = new \Imagine\Gd\Imagine();
        $image = $Imagine->open($file);

        $size = $image->getSize();
        $width = $size->getWidth();
        $height = $size->getHeight();
        if (! empty($params['height']) && ! empty($params['width'])) {
            $width = (int) $params['width'];
            $height = (int) $params['height'];
        } elseif (! empty($params['height'])) {
            $height = (int) $params['height'];
        } elseif (! empty($params['width'])) {
            $width = (int) $params['width'];
        }

        $mode = $params['crop'] == 'exact' ?
            \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND :
            \Imagine\Image\ImageInterface::THUMBNAIL_INSET;

        $newSize = new \Imagine\Image\Box($width, $height);

        $Imagine->open($file)->thumbnail($newSize, $mode)->save($newFile);

        return $newFile;
    }

    /**
     * Generate File Name Hash based on resize arguments
     *
     * @param string $file - full path to file and file name
     * @param array $params
     *
     * @return string
     */
    protected function _generateFileNameHash($file, $params)
    {
        return md5($file . serialize($params)) . '.' . $this->_request->ext;
    }

    /**
     * Parse a string of resize arguments
     *
     * @param string $params
     *
     * @return array
     *
     * @throws ErrorException
     */
    protected function _parseParams($params)
    {
        $regExs = $this->_getAllowedParamsRegEx();

        // Grab out the values
        foreach ($regExs as $param => $regEx) {
            if (preg_match("%{$regEx}%", $params, $match)) {
                $ret[$param] = $match[$param];
            }
        }

        // Set defaults if need be
        foreach ($this->_allowedParams as $param => $options) {
            if (! empty($options['default']) && empty($ret[$param])) {
                $ret[$param] = $options['default'];
            }
        }

        // Need at least a height or a width
        if (empty($ret['height']) && empty($ret['width'])) {
            throw new ErrorException('You must set at least the height (h) or the width (w)');
        }

        return $ret;
    }

    /**
     * Builds the Regular Expressions for all the Allowed Parameters
     *
     * @return array
     */
    protected function _getAllowedParamsRegEx()
    {
        $ret = array();
        foreach ($this->_allowedParams as $param => $options) {
            $p = $param;
            if (! empty($options['alias'])) {
                $p .= "|{$options['alias']}";
            }

            $ret[$param] = "(?:{$p})\\[(?P<{$param}>{$options['regex']})\\]";
        }

        return $ret;
    }
}