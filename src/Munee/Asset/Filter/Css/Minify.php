<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee\Asset\Filter\Css;

use Munee\Asset\Filter;
use Munee\Utils;
use CSSmin;

/**
 * Minify Filter for CSS
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
     * CSS Minification
     *
     * @param string $file
     * @param array $arguments
     * @param array $cssOptions
     *
     * @return void
     */
    public function doFilter($file, $arguments, $cssOptions)
    {
        if (! $arguments['minify']) {
            return;
        }

        $content = file_get_contents($file);
        $compressor = new CSSmin();

        if (Utils::isSerialized($content, $content)) {
            $content['compiled'] = $compressor->run($content['compiled']);
            $content = serialize($content);
        } else {
            $content = $compressor->run($content);
        }

        file_put_contents($file, $content);
    }
}
