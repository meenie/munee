<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee\Asset\Filter\Css;

use Munee\Asset\Filter;
use Munee\Utils;

/**
 * Minify Filter for CSS
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
     * CSS Minification
     *
     * @param string $file
     * @param array $arguments
     *
     * @return void
     */
    public function doFilter($file, $arguments)
    {
        if (! $arguments['minify']) {
            return;
        }

        $content = file_get_contents($file);
        if (Utils::isSerialized($content, $content)) {
            $content['compiled'] = $this->_minify($content['compiled']);
            $content = serialize($content);
        } else {
            $content = $this->_minify($content);
        }

        file_put_contents($file, $content);
    }

    /**
     * CSS Minify Helper
     *
     * @param string $content
     *
     * @return string
     */
    protected function _minify($content)
    {
        $regexs = array(
            // Remove Comments
            '%/\*[^*]*\*+([^/][^*]*\*+)*/%',
            // Fixing extra spacing between classes so there is only one space
            '%(\w)\s{2,}\.%',
        );
        $replaces = array(
            '',
            '$1 .'
        );
        $content = preg_replace($regexs, $replaces, $content);

        // Remove Tabs, Spaces, New Lines, and Unnecessary Space
        $find = array(
            '{ ',
            ' }',
            '; ',
            ', ',
            ' {',
            '} ',
            ': ',
            ' ,',
            ' ;',
            ';}',
            "\r\n",
            "\r",
            "\n",
            "\t",
            '  ',
            '    '
        );
        $replace = array(
            '{',
            '}',
            ';',
            ',',
            '{',
            '}',
            ':',
            ',',
            ';',
            '}',
            ''
        );

        return str_replace($find, $replace, $content);
    }
}