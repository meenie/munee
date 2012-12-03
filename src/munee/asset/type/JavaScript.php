<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\asset\type;

use munee\Request;
use munee\asset\Base;
use munee\asset\NotFoundException;
use JShrink\Minifier;

/**
 * Handles JavaScript
 *
 * @author Cody Lundquist
 */
class JavaScript extends Base
{
    /**
     * @var string
     */
    protected $_jsCacheDir;

    /**
     * Generates the JS content based on the request
     *
     * @param \munee\Request $Request
     *
     * @throws NotFoundException
     */
    public function __construct(Request $Request)
    {
        parent::__construct($Request);

        $this->_jsCacheDir = CACHE . DS . 'js';
        $this->_createDir($this->_jsCacheDir);

        if (! $this->_content = $this->_checkJsCache()) {
            $files = $this->_request->files;
            if (! is_array($files)) {
                $files = array($files);
            }
            $this->_content = '';
            foreach ($files as $file) {
                $file = WEBROOT . $file;
                if (! file_exists($file)) {
                    throw new NotFoundException('File could not be found: ' . $file);
                }
                $filename = str_replace(WEBROOT, '', $file);
                $this->_content .= "/*!\n";
                $this->_content .= " *\n";
                $this->_content .= " * Content from file: {$filename}\n";
                $this->_content .= " *\n";
                $this->_content .= " */\n\n";
                $this->_content .= file_get_contents($file) . "\n";
            }
            if ($this->_request->minify) {
                $this->_minify();
            }

            $this->_createJsCache($this->_content);
            $this->_lastModifiedDate = time();
        }
    }

    /**
     * Set additional headers just for CSS
     */
    public function getHeaders()
    {
        header("Content-Type: text/javascript");
    }

    /**
     * Minifies the JavaScript
     *
     * @return string
     */
    protected function _minify()
    {
        $this->_content = Minifier::minify($this->_content);
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