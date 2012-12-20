<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\asset\type;

use munee\Utils;
use munee\asset\Base;
use lessc;

/**
 * Handles CSS
 *
 * @author Cody Lundquist
 */
class Css extends Base
{
    /**
     * @var array
     */
    protected $_options = array(
        'validateCache' => true,
        'lessifyAllCss' => false
    );

    /**
     * Set additional headers just for CSS
     */
    public function getHeaders()
    {
        header("Content-Type: text/css");
    }

    /**
     * Checks to see if cache exists and is the latest, if it does, return it
     * It also checks to see if this is LESS cache and makes sure all imported files are the latest
     *
     * @param string $file
     *
     * @return bool|string
     */
    protected function _checkCache($file)
    {
        if (! $ret = parent::_checkCache($file)) {
            return false;
        }

        if ($this->_isLess($file)) {
            if (! Utils::isSerialized($ret, $lessCache)) {
                return $ret;
            }
            foreach ($lessCache['files'] as $file => $lastModified) {
                if (filemtime($file) > $lastModified) {
                    return false;
                }
            }

            $ret = $lessCache['compiled'];
        }

        return $ret;
    }

    /**
     * Callback function called after the content is collected but before the content is cached
     * We want to run the file through LESS if need be.
     *
     * @param string $content
     * @param string $file
     *
     * @return string
     */
    protected function _beforeCreateCacheCallback($content, $file)
    {

        if ($isLess = $this->_isLess($file)) {
            $less = new lessc();
            $content = $less->cachedCompile($file);
        }

        if (! empty($this->_request->params['minify'])) {
            if ($isLess) {
                $content['compiled'] = $this->_minify($content['compiled']);
            } else {
                $content = $this->_minify($content);
            }
        }

        // If content is an array, we want to serialize before we return it
        return is_array($content) ? serialize($content) : $content;
    }

    /**
     * Callback method called after the content is collected and cached
     * Check if the content is serialized.  If it is, we have LESS cache
     * and we want to return whats in the `compiled` array key
     *
     * @param string $content
     *
     * @return string
     */
    protected function _getFileContentCallback($content)
    {
        if (Utils::isSerialized($content, $content)) {
            $content = $content['compiled'];
        }

        return $content;
    }

    /**
     * Check if it's a LESS file or if we should run all CSS through LESS
     *
     * @param string $file
     *
     * @return boolean
     */
    protected function _isLess($file)
    {
        return 'less' == pathinfo($file, PATHINFO_EXTENSION) || $this->_options['lessifyAllCss'];
    }

    /**
     * CSS Minification
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