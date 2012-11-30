<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\asset;

use \munee\AssetBase;
use \munee\asset\AssetNotFoundException;

/**
 * Handles CSS
 *
 * @author Cody Lundquist
 */
class Css extends AssetBase
{
    /**
     * @var string
     */
    protected $_contentType = 'text/css';

    /**
     * Generates the CSS content based on the request
     *
     * @return string
     * @throws AssetNotFoundException
     */
    protected function _getContent()
    {
        $lessTmpDir = CACHE . DS . 'css';
        $this->_createDir($lessTmpDir);

        $files = $this->_request->files;
        if (! is_array($files)) {
            $files = array($files);
        }
        $ret = '';
        foreach ($files as $file) {
            $file = WEBROOT . $file;
            if (! file_exists($file)) {
                throw new AssetNotFoundException('File could not be found: ' . $file);
            }
            $hashedFile = $lessTmpDir . DS . md5($file);
            if (file_exists($hashedFile)) {
                $cache = unserialize(file_get_contents($hashedFile));
            } else {
                $cache = $file;
            }
            $less = new \lessc();
            $newCache = $less->cachedCompile($cache);
            if (! is_array($cache) || $newCache['updated'] > $cache['updated']) {
                file_put_contents($hashedFile, serialize($newCache));
                $ret .= $newCache['compiled'];
            } else {
                $ret .= $cache['compiled'];
            }

            if ($newCache['updated'] > $this->_lastModifiedDate) {
                $this->_lastModifiedDate = $newCache['updated'];
            }
        }
        if ($this->_request->minify) {
            $ret = $this->_cssMinify($ret);
        }

        return $ret;
    }

    /**
     * Minifies the CSS
     *
     * @param $content
     *
     * @return mixed
     */
    protected function _cssMinify($content)
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
        // Remove Tabs, Spaces, New Lines, and Unnecessary Space */
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