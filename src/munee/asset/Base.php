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

        // Set cache dir if needed
        if (empty($this->_cacheDir)) {
            $this->_cacheDir = CACHE;
        }
        // Set cache dir if needed
        Utils::createDir($this->_cacheDir);

        // If we have cache, use it.  If not, lets churn some files
        if (! $this->_content = $this->_checkCache()) {
            $files = $this->_request->files;

            // Only one file? Nothing special
            if (count($files) === 1) {
                $file = WEBROOT . array_shift($files);
                $this->_content = $this->_getFileContent($file);
            // Lets combine all the files and split them up with comments.
            } else {
                foreach ($files as $file) {
                    $file = WEBROOT . $file;
                    $filename = str_replace(WEBROOT, '', $file);
                    $this->_content .= "/*!\n";
                    $this->_content .= " *\n";
                    $this->_content .= " * Content from file: {$filename}\n";
                    $this->_content .= " *\n";
                    $this->_content .= " */\n\n";
                    $this->_content .= $this->_getFileContent($file) . "\n";
                }
            }

            // Run the afterFilter callback
            $this->_afterFilter();

            // Create the cache
            $this->_createCache($this->_content);

            // Set the lastModifiedDate
            $this->_lastModifiedDate = time();
        }
    }

    /**
     * All Base Sub-Classes must create this method to set their additional headers
     */
    abstract public function getHeaders();


    /**
     * Magic Method so you can echo out a the Asset Class
     *
     * @return string
     */
    public function __toString()
    {
        return $this->_content;
    }

    /**
     * Return the current content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->__toString();
    }

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
     * Callback method called after the content is collected
     */
    protected function _afterFilter() {}


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

        return file_get_contents($file);
    }

    /**
     * Checks to see if cache exists and is the latest, if it does, return it
     *
     * @return bool|string
     */
    protected function _checkCache()
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
    protected function _createCache($content)
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
        return $this->_cacheDir . DS . md5(serialize($this->_request->files)) .
            (! empty($this->_request->params['minify']) ? '-minified' : null);
    }
}