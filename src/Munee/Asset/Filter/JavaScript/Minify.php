<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee\Asset\Filter\JavaScript;

use Munee\Asset\Filter;

/**
 * Minify Filter for JavaScript
 *
 * @author Cody Lundquist
 */
class Minify extends Filter
{
    /**
     * @var array
     */
    protected $_allowedParams = array(
        'minify' => array(
            'alias' => 'm',
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

        file_put_contents($file, \JShrink\Minifier::minify(file_get_contents($file)));
    }
}