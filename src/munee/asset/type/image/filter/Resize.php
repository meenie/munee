<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\asset\type\image\filter;

use munee\asset\type\image\Filter;
use munee\ErrorException;

/**
 * Resize Filter to resize/crop/fill/stretch images
 *
 * @author Cody Lundquist
 */
class Resize extends Filter
{
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
        )
    );

    /**
     * Use Imagine to resize an image and return it's new path
     *
     * @return string
     *
     * @throws ErrorException
     */
    protected function _filter()
    {
        // Need at least a height or a width
        if (empty($this->_params['height']) && empty($this->_params['width'])) {
            throw new ErrorException('You must set at least the height (h) or the width (w)');
        }

        $Imagine = new \Imagine\Gd\Imagine();
        $image = $Imagine->open($this->_newImage);

        $size = $image->getSize();
        $originalWidth = $size->getWidth();
        $originalHeight = $size->getHeight();
        $width = $originalWidth;
        $height = $originalHeight;
        if (! empty($this->_params['height']) && ! empty($this->_params['width'])) {
            if ($originalWidth > $this->_params['width'] || $this->_params['stretch']) {
                $width = $this->_params['width'];
            }
            if ($originalHeight > $this->_params['height'] || $this->_params['stretch']) {
                $height = $this->_params['height'];
            }
        } elseif (! empty($this->_params['height'])) {
            if ($originalHeight > $this->_params['height'] || $this->_params['stretch']) {
                $height = $this->_params['height'];
            }
        } elseif (! empty($this->_params['width'])) {
            if ($originalWidth > $this->_params['width'] || $this->_params['stretch']) {
                $width = $this->_params['width'];
            }
        }

        $mode = $this->_params['exact'] ?
            \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND :
            \Imagine\Image\ImageInterface::THUMBNAIL_INSET;

        $newSize = new \Imagine\Image\Box($width, $height);

        $newImage = $image->thumbnail($newSize, $mode);
        if ($this->_params['fill']) {
            $adjustedSize = $newImage->getSize();
            $canvasWidth = isset($this->_params['width']) ? $this->_params['width'] : $adjustedSize->getWidth();
            $canvasHeight = isset($this->_params['height']) ? $this->_params['height'] : $adjustedSize->getHeight();
            $canvas = $Imagine->create(
                new \Imagine\Image\Box($canvasWidth, $canvasHeight),
                new \Imagine\Image\Color($this->_params['fillColour'])
            );

            // Put image in the middle of the canvas
            $newImage = $canvas->paste($newImage, new \Imagine\Image\Point(
                (int) (($canvasWidth - $adjustedSize->getWidth()) / 2),
                (int) (($canvasHeight - $adjustedSize->getHeight()) / 2)
            ));
        }

        $newImage->save($this->_newImage, array('quality' => $this->_params['quality']));
    }
}