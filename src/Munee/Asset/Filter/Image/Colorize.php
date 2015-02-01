<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee\Asset\Filter\Image;

use Munee\Asset\Filter;
use Imagine\Gd\Imagine;
use Imagine\Image\Palette\RGB as Color;

/**
 * Colorize Filter for Images
 *
 * @author Cody Lundquist
 */
class Colorize extends Filter
{
    /**
     * List of allowed params for this particular filter
     *
     * @var array
     */
    protected $allowedParams = array(
        'colorize' => array(
            'regex' => '[A-Fa-f0-9]{3}$|^[A-Fa-f0-9]{6}',
            'cast' => 'string'
        )
    );

    /**
     * Colorize an image
     *
     * @param string $file
     * @param array $arguments
     * @param array $typeOptions
     *
     * @return void
     */
    public function doFilter($file, $arguments, $typeOptions)
    {
        $Imagine = new Imagine();
        $image = $Imagine->open($file);
        $colour = new Color();
        $colour = $colour->color('#' . $arguments['colorize']);
        $image->effects()->colorize($colour);
        $image->save($file);
    }
}