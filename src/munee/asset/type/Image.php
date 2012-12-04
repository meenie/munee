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
            'alias' => array('w'),
            'regex' => '\d+',
            'cast' => 'integer'
        ),
        'height' => array(
            'alias' => 'h',
            'regex' => '\d+',
            'cast' => 'integer'
        ),
        'quality' => array(
            'alias' => array('q', 'qlty'),
            'regex' => '\d{1,2}(?!\d)|100',
            'default' => 75,
            'cast' => 'integer'
        ),
        'exact' => array(
            'alias' => 'e',
            'regex' => 'true|false',
            'default' => 'false',
            'cast' => 'boolean'
        ),
        'stretch' => array(
            'alias' => 's',
            'regex' => 'true|false',
            'default' => 'false',
            'cast' => 'boolean'
        ),
        'fill' => array(
            'alias' => 'f',
            'regex' => 'true|false',
            'default' => 'false',
            'cast' => 'boolean'
        ),
        'fillColour' => array(
            'alias' => array(
                'fc',
                'fillColor',
                'fillcolor',
                'fill_color',
                'fill-color',
                'fillcolour',
                'fill_colour',
                'fill-colour'
            ),
            'regex' => '[A-Fa-f0-9]{3,6}',
            'default' => 'ffffff',
            'cast' => 'string'
        ),
    );

    /**
     * @var int
     */
    protected $_numberOfAllowedResizes = 3;

    /**
     * @var int
     */
    protected $_resizeTimelimit = 300;

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

        }
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

        $this->_checkReferrer();
        $this->_checkNumberOfAllowedResizes($hashedName);

        $Imagine = new \Imagine\Gd\Imagine();
        $image = $Imagine->open($file);

        $size = $image->getSize();
        $originalWidth = $size->getWidth();
        $originalHeight = $size->getHeight();
        $width = $originalWidth;
        $height = $originalHeight;
        if (! empty($params['height']) && ! empty($params['width'])) {
            if ($originalWidth > $params['width'] || $params['stretch']) {
                $width = $params['width'];
            }
            if ($originalHeight > $params['height'] || $params['stretch']) {
                $height = $params['height'];
            }
        } elseif (! empty($params['height'])) {
            if ($originalHeight > $params['height'] || $params['stretch']) {
                $height = $params['height'];
            }
        } elseif (! empty($params['width'])) {
            if ($originalWidth > $params['width'] || $params['stretch']) {
                $width = $params['width'];
            }
        }

        $mode = $params['exact'] ?
            \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND :
            \Imagine\Image\ImageInterface::THUMBNAIL_INSET;

        $newSize = new \Imagine\Image\Box($width, $height);

        $newImage = $Imagine->open($file)->thumbnail($newSize, $mode);
        if ($params['fill']) {
            $adjustedSize = $newImage->getSize();
            $canvasWidth = isset($params['width']) ? $params['width'] : $adjustedSize->getWidth();
            $canvasHeight = isset($params['height']) ? $params['height'] : $adjustedSize->getHeight();
            $canvas = $Imagine->create(
                new \Imagine\Image\Box($canvasWidth, $canvasHeight),
                new \Imagine\Image\Color($params['fillColour'])
            );

            // Put image in the middle of the canvas
            $newImage = $canvas->paste($newImage, new \Imagine\Image\Point(
                (int) (($canvasWidth - $adjustedSize->getWidth()) / 2),
                (int) (($canvasHeight - $adjustedSize->getHeight()) / 2)
            ));
        }

        $newImage->save($newFile, array('quality' => $params['quality']));

        return $newFile;
    }


    /**
     * Check to make sure the referrer domain is the same as the domain where the image exists.
     *
     * @throws ErrorException
     */
    protected function _checkReferrer()
    {
        if (! isset($_SERVER['HTTP_REFERER'])) {
            throw new ErrorException('Direct resizing is not allowed.');
        }

        $referrer = preg_replace('%^https?://%', '', $_SERVER['HTTP_REFERER']);
        if (! preg_match("%^{$_SERVER['SERVER_NAME']}%", $referrer)) {
            throw new ErrorException('Referer does not match the correct domain.');
        }
    }


    /**
     * Check number of allowed resizes within a set timelimit
     *
     * @throws ErrorException
     */
    protected function _checkNumberOfAllowedResizes($hashedName)
    {
        $fileNameHash = preg_replace('%-.*\..+$%', '', $hashedName);
        // Grab all the similar files
        $cachedImages = glob($this->_imageCacheDir . DS . $fileNameHash . '*');
        // Loop through and remove the ones that are older than the time limit
        foreach ($cachedImages as $k => $image) {
            if (filemtime($image) < time() - $this->_resizeTimelimit) {
                unset($cachedImages[$k]);
            }
        }
        // Check and see if we've reached the maximum allowed resizes within the current timelimit.
        if (count($cachedImages) >= $this->_numberOfAllowedResizes) {
            throw new ErrorException('You cannot create anymore resizes at this time.');
        }
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
        return md5($file) . '-' . md5(serialize($params)) . '.' . $this->_request->ext;
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
        $ret = array();
        $regExs = $this->_getAllowedParamsRegEx();

        // Set defaults if need be
        foreach ($this->_allowedParams as $param => $options) {
            if (! empty($options['default']) && empty($ret[$param])) {
                $ret[$param] = $options['default'];
            }
        }

        // Grab out the values
        foreach ($regExs as $param => $regEx) {
            if (preg_match("%{$regEx}%", $params, $match)) {
                $val = strtolower($match[$param]);
                $ret[$param] = $val;
            }
        }

        // Cast the values based on the allowed params options
        foreach ($ret as $param => $val) {
            $cast = isset($this->_allowedParams[$param]['cast']) ?
                $this->_allowedParams[$param]['cast'] : 'string';
            switch ($cast) {
                case 'integer';
                    $ret[$param] = (integer) $val;
                    break;
                case 'boolean';
                    $ret[$param] = 'true' == $val;
                    break;
                default:
                    $ret[$param] = (string) $val;
                    break;
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
                $alias = implode('|', (array) $options['alias']);
                $p .= "|{$alias}";
            }

            $ret[$param] = "(?:{$p})\\[(?P<{$param}>{$options['regex']})\\]";
        }

        return $ret;
    }
}