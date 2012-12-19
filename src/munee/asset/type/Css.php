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
     * @var array
     */
    protected $_options = array(
        'validateCache' => true,
        'lessifyAllCss' => false
    );

    /**
     * Generates the JS content based on the request
     *
     * @param \munee\Request $Request
     */
    public function __construct(Request $Request)
    {
        $this->_cacheDir = CACHE . DS . 'css';
        parent::__construct($Request);
    }

    /**
     * Generates the CSS content based on the request
     *
     * @param string $file
     *
     * @return string
     *
     * @throws NotFoundException
     */
    public function _getFileContent($file)
    {
        $lessTmpDir = CACHE . DS . 'css';
        Utils::createDir($lessTmpDir);

        $file = WEBROOT . $file;
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (! file_exists($file)) {
            throw new NotFoundException('File could not be found: ' . $file);
        }

        if ('less' == $ext || $this->_options['lessifyAllCss']) {
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
                $content = $newCache['compiled'];
            } else {
                $content = $cache['compiled'];
            }

            if ($newCache['updated'] > $this->_lastModifiedDate) {
                $this->_lastModifiedDate = $newCache['updated'];
            }
        } else {
            $content = file_get_contents($file);
        }

        return $content;
    }

    /**
     * Set additional headers just for CSS
     */
    public function getHeaders()
    {
        header("Content-Type: text/css");
    }

    /**
     * Callback function called after the content is collected
     *
     * Doing minification if needed
     *
     * @return string
     */
    protected function _afterFilter()
    {
        if (empty($this->_request->params['minify'])) {
            return;
        }

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

        $this->_content = str_replace($find, $replace, $this->_content);
    }
}