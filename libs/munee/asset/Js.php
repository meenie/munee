<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\asset;

use \munee\Asset;
use \munee\asset\AssetNotFoundException;

/**
 * Handles JS
 *
 * @author Cody Lundquist
 */
class Js extends Asset
{
    /**
     * @var string
     */
    protected $_contentType = 'text/javascript';

    /**
     * @var string
     */
    protected $_jsCacheDir;

    /**
     * Generates the JS content based on the request
     *
     * @return string
     * @throws AssetNotFoundException
     */
    protected function _getContent()
    {
        $this->_jsCacheDir = CACHE . DS . 'js';
        $this->_createDir($this->_jsCacheDir);

        if (! $ret = $this->_checkJsCache()) {
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
                $filename = str_replace(WEBROOT, '', $file);
                $ret .= "/*!\n";
                $ret .= " *\n";
                $ret .= " * Content from file: {$filename}\n";
                $ret .= " *\n";
                $ret .= " */\n\n";
                $ret .= file_get_contents($file) . "\n";
            }
            if ($this->_request->minify) {
                $ret = $this->_jsMinify($ret);
            }

            $this->_createJsCache($ret);
            $this->_lastModifiedDate = time();
        }

        return $ret;
    }

    /**
     * Minifies the JavaScript
     *
     * @param $content
     *
     * @return string
     */
    protected function _jsMinify($content)
    {
        return \jshrink\JShrink::minify($content);
    }

    /**
     * Checks to see if cache exists and is the latest, if it does, return it
     *
     * @return bool|string
     */
    protected function _checkJsCache()
    {
        $hashFile = $this->_generateHashFilename();
        if (! file_exists($hashFile)) {
            return false;
        }
        $hashFileLastModified = filemtime($hashFile);
        foreach ($this->_request->files as $file) {
            $file = WEBROOT . $file;
            if (! file_exists($file) || filemtime($file) > $hashFileLastModified) {
                return false;
            }
        }
        $this->_lastModifiedDate = $hashFileLastModified;

        return file_get_contents($hashFile);
    }

    /**
     * Caches a file based on it's type and file names.
     *
     * @param $content
     */
    protected function _createJsCache($content)
    {
        $hashFile = $this->_generateHashFilename();
        file_put_contents($hashFile, $content);
    }

    /**
     * Generate hash filename
     *
     * @return string;
     */
    protected function _generateHashFilename()
    {
        return $this->_jsCacheDir . DS . md5(serialize($this->_request->files)) .
            ($this->_request->minify ? '-minified' : null);
    }
}