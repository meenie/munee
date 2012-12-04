<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\asset\type;

use munee\Request;
use munee\Utils;
use munee\asset\Base;
use munee\asset\NotFoundException;
use lessc;

/**
 * Handles CSS
 *
 * @author Cody Lundquist
 */
class Css extends Base
{
    /**
     * Generates the CSS content based on the request
     *
     * @param \munee\Request $Request
     * 
     * @throws NotFoundException
     */
    public function __construct(Request $Request)
    {
        parent::__construct($Request);
        
        $lessTmpDir = CACHE . DS . 'css';
        Utils::createDir($lessTmpDir);

        $files = (array) $this->_request->files;
        foreach ($files as $file) {
            $file = WEBROOT . $file;
            if (! file_exists($file)) {
                throw new NotFoundException('File could not be found: ' . $file);
            }
            $hashedFile = $lessTmpDir . DS . md5($file);
            if (file_exists($hashedFile)) {
                $cache = unserialize(file_get_contents($hashedFile));
            } else {
                $cache = $file;
            }
            $less = new lessc();
            $newCache = $less->cachedCompile($cache);
            if (! is_array($cache) || $newCache['updated'] > $cache['updated']) {
                file_put_contents($hashedFile, serialize($newCache));
                $this->_content .= $newCache['compiled'];
            } else {
                $this->_content .= $cache['compiled'];
            }

            if ($newCache['updated'] > $this->_lastModifiedDate) {
                $this->_lastModifiedDate = $newCache['updated'];
            }
        }

        if (! empty($this->_request->params['minify'])) {
            $this->_minify();
        }
    }

    /**
     * Set additional headers just for CSS
     */
    public function getHeaders()
    {
        header("Content-Type: text/css");
    }

    /**
     * Minifies the CSS
     */
    protected function _minify()
    {
        $this->_cacheClientSide = true;

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
        $this->_content = preg_replace($regexs, $replaces, $this->_content);
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

        $this->_content = str_replace($find, $replace, $this->_content);
    }
}