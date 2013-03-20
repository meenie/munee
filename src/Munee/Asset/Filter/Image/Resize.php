<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee\Asset\Filter\Image;

use Munee\Asset\Filter;
use Munee\ErrorException;
use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\Color;
use Imagine\Image\Point;

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
        'resize' => array(
            'alias' => 'r',
            'arguments' => array(
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
                    'regex' => 'true|false|t|f|yes|no|y|n',
                    'default' => 'false',
                    'cast' => 'boolean'
                ),
                'stretch' => array(
                    'alias' => 's',
                    'regex' => 'true|false|t|f|yes|no|y|n',
                    'default' => 'false',
                    'cast' => 'boolean'
                ),
                'fill' => array(
                    'alias' => 'f',
                    'regex' => 'true|false|t|f|yes|no|y|n',
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
            )
        )
    );

    /**
     * Use Imagine to resize an image and return it's new path
     *
     * @param string $originalImage
     * @param array $arguments
     *
     * @return string
     *
     * @throws ErrorException
     */
    public function doFilter($originalImage, $arguments)
    {
        // Need at least a height or a width
        if (empty($arguments['height']) && empty($arguments['width'])) {
            throw new ErrorException('You must set at least the height (h) or the width (w)');
        }

        $Imagine = new \Imagine\Gd\Imagine();
        $image = $Imagine->open($originalImage);

        $size = $image->getSize();
        $originalWidth = $size->getWidth();
        $originalHeight = $size->getHeight();
        $width = $originalWidth;
        $height = $originalHeight;
        if (! empty($arguments['height']) && ! empty($arguments['width'])) {
            if ($originalWidth > $arguments['width'] || $arguments['stretch']) {
                $width = $arguments['width'];
            }
            if ($originalHeight > $arguments['height'] || $arguments['stretch']) {
                $height = $arguments['height'];
            }
        } elseif (! empty($arguments['height'])) {
            if ($originalHeight > $arguments['height'] || $arguments['stretch']) {
                $height = $arguments['height'];
            }
        } elseif (! empty($arguments['width'])) {
            if ($originalWidth > $arguments['width'] || $arguments['stretch']) {
                $width = $arguments['width'];
            }
        }

        $mode = $arguments['exact'] ?
            ImageInterface::THUMBNAIL_OUTBOUND :
            ImageInterface::THUMBNAIL_INSET;

        $newSize = new Box($width, $height);

        $newImage = $image->thumbnail($newSize, $mode);
        if ($arguments['fill']) {
            $adjustedSize = $newImage->getSize();
            $canvasWidth = isset($arguments['width']) ? $arguments['width'] : $adjustedSize->getWidth();
            $canvasHeight = isset($arguments['height']) ? $arguments['height'] : $adjustedSize->getHeight();
            $canvas = $Imagine->create(
                new Box($canvasWidth, $canvasHeight),
                new Color($arguments['fillColour'])
            );

            // Put image in the middle of the canvas
            $newImage = $canvas->paste($newImage, new Point(
                (int) (($canvasWidth - $adjustedSize->getWidth()) / 2),
                (int) (($canvasHeight - $adjustedSize->getHeight()) / 2)
            ));
        }

        $newImage->save($originalImage, array('quality' => $arguments['quality']));
    }
}