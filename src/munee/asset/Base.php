<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\asset;

use munee\Request;
use munee\Utils;

/**
 * Base Asset Class
 * All Asset Types need to extend this and implement the getHeaders() method.
 *
 * @author Cody Lundquist
 */
abstract class Base
{
    /**
     * @var array
     */
    protected $_options = array();

    /**
     * @var boolean
     */
    protected $_cacheClientSide = false;

    /**
     * @var string
     */
    protected $_cacheDir;

    /**
     * @var integer
     */
    protected $_lastModifiedDate = 0;

    /**
     * @var string
     */
    protected $_content;

    /**
     * @var object
     */
    protected $_request;

    /**
     * Constructor
     *
     * @param \munee\Request $Request
     *
     * @throws NotFoundException
     */
    public function __construct(Request $Request)
    {
        $this->_request = $Request;
        $this->_cacheDir = CACHE . DS . strtolower(basename(get_class($this)));

        // Set cache dir if needed
        Utils::createDir($this->_cacheDir);
    }

    /**
     * Grabs the content for the Response class
     *
     * @return string
     */
    public function getContent()
    {
        $this->_content = $this->_processFiles();

        return $this->_content;
    }

    /**
     * All Base Sub-Classes must create this method to set their additional headers
     */
    abstract public function getHeaders();

    /**
     * Return a file's Last Modified Date.
     *
     * @return integer timestamp
     */
    public function getLastModifiedDate()
    {
        return $this->_lastModifiedDate;
    }

    /**
     * Return true/false if a response should be cached client side
     *
     * @return boolean
     */
    public function getCacheClientSide()
    {
        return (boolean) $this->_cacheClientSide;
    }

    /**
     * Set Options
     *
     * @param $options
     */
    public function setOptions($options)
    {
        foreach ($options as $name => $value) {
            $this->_options[$name] = $value;
        }
    }

    /**
     * Set a single Option
     *
     * @param $name
     * @param $value
     */
    public function setOption($name, $value)
    {
        $this->_options[$name] = $value;
    }

    /**
     * Process all files in the request and return content
     *
     * @return string
     */
    protected function _processFiles()
    {
        $ret = null;
        foreach ($this->_request->files as $file) {
            $ret .= $this->_getFileContent($file) . "\n";
        }

        return $ret;
    }

    /**
     * Callback method called after the content is collected and cached
     *
     * @param string $content
     *
     * @return string
     */
    protected function _getFileContentCallback($content)
    {
        return $content;
    }

    /**
     * Callback function called after the content is collected but before the content is cached
     *
     * @param string $content
     * @param string $file
     *
     * @return string
     */
    protected function _beforeCreateCacheCallback($content, $file)
    {
        return $content;
    }

    /**
     * Grab a files content but check to make sure it exists first
     *
     * @param $file
     *
     * @return string
     *
     * @throws NotFoundException
     */
    protected function _getFileContent($file)
    {
        if (! file_exists($file)) {
            throw new NotFoundException('File could not be found: ' . $file);
        }

        if (! $content = $this->_checkCache($file)) {
            $content = file_get_contents($file);

            $content = $this->_beforeCreateCacheCallback($content, $file);
            $this->_createCache($file, $content);

            $content = $this->_getFileContentCallback($content);

            $this->_lastModifiedDate = time();
        }

        return $content;
    }

    /**
     * Checks to see if cache exists and is the latest, if it does, return it
     *
     * @param string $file
     *
     * @return bool|string
     */
    protected function _checkCache($file)
    {
        $hashFile = $this->_generateHashFilename($file);
        if (! file_exists($hashFile)) {
            return false;
        }

        $hashFileLastModified = filemtime($hashFile);
        if (! file_exists($file) || filemtime($file) > $hashFileLastModified) {
            return false;
        }

        if ($this->_lastModifiedDate < $hashFileLastModified) {
            $this->_lastModifiedDate = $hashFileLastModified;
        }

        return file_get_contents($hashFile);
    }

    /**
     * Caches a file based on it's filename and content.
     *
     * @param string $file
     * @param string $content
     */
    protected function _createCache($file, $content)
    {
        file_put_contents($this->_generateHashFilename($file), $content);
    }

    /**
     * Generate hash for a file based on it's path, name, and params.
     *
     * @param string $file
     *
     * @return string;
     */
    protected function _generateHashFilename($file)
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        return $this->_cacheDir . DS . md5($file . serialize($this->_request->params)) . '.' . $ext;
    }
}