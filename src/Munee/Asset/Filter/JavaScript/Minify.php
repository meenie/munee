<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee\Asset\Filter\JavaScript;

use Munee\Asset\Filter;
use JShrink\Minifier;

/**
 * Minify Filter for JavaScript
 *
 * @author Cody Lundquist
 */
class Minify extends Filter
{
    /**
     * List of allowed params for this particular filter
     *
     * @var array
     */
    protected $allowedParams = array(
        'minify' => array(
            'regex' => 'true|false|t|f|yes|no|y|n',
            'default' => 'false',
            'cast' => 'boolean'
        )
    );

    /**
     * JavaScript Minification
     *
     * @param string $file
     * @param array $arguments
     * @param array $javaScriptOptions
     *
     * @return void
     */
    public function doFilter($file, $arguments, $javaScriptOptions)
    {
        if (! $arguments['minify']) {
            return;
        }

        file_put_contents($file, Minifier::minify(file_get_contents($file)));
    }
}