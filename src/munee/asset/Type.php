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
abstract class Type
{
    /**
     * @var array
     */
    protected $_options = array();

    /**
     * @var array
     */
    protected $_params = array();

    /**
     * @var array
     */
    protected $_filters = array();

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

        // Pull in filters based on the raw params that were passed in
        $rawParams = $Request->getRawParams();
        $assetShortName = strtolower(preg_replace('%^.*\\\\%','', get_class($this)));
        $allowedParams = array();
        foreach (array_keys($rawParams) as $filterName) {
            $filterClass = 'munee\\asset\\filter\\' . $assetShortName . '\\' . ucfirst($filterName);
            if (class_exists($filterClass)) {
                $Filter = new $filterClass();
                $allowedParams += $Filter->getAllowedParams();
                $this->_filters[$filterName] = $Filter;
            }
        }

        // Parse the raw params based on a map of allowedParams for those filters
        $this->_request->parseParams($allowedParams);

        $this->_cacheDir = MUNEE_CACHE . DS . $assetShortName;

        // Set the AssetType options if someone were passed in through the Request Class
        if (isset($this->_request->options[$assetShortName])) {
            $this->_options = array_merge(
                $this->_options,
                $this->_request->options[$assetShortName]
            );
        }

        // Create cache dir if needed
        Utils::createDir($this->_cacheDir);
    }

    /**
     * Process all files in the request and set the content
     */
    public function init()
    {
        $content = array();
        foreach ($this->_request->files as $file) {
            $fileContent = $this->_getFileContent($file);
            $content[] = $this->_afterGetFileContent($fileContent);
        }

        $this->_content = implode("\n", $content);
    }

    /**
     * Grabs the content for the Response class
     *
     * @return string
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * All Type Sub-Classes must create this method to set their additional headers
     */
    abstract public function getHeaders();

    /**
     * Return a this requests Last Modified Date.
     *
     * @return integer timestamp
     */
    public function getLastModifiedDate()
    {
        return $this->_lastModifiedDate;
    }

    /**
     * Callback method called before filters are run
     *
     * @param string $originalFile
     * @param string $cacheFile
     */
    protected function _beforeFilter($originalFile, $cacheFile) {}

    /**
     * Callback function called after filters are run
     *
     * @param string $originalFile
     * @param string $cacheFile
     */
    protected function _afterFilter($originalFile, $cacheFile) {}

    /**
     * Callback function called after _getFileContent() is called
     *
     * @param string $content
     *
     * @return string
     */
    protected function _afterGetFileContent($content)
    {
        return $content;
    }

    /**
     * Grab a files content but check to make sure it exists first
     *
     * @param $originalFile
     *
     * @return string
     *
     * @throws NotFoundException
     */
    protected function _getFileContent($originalFile)
    {
        $cacheFile = $this->_generateCacheFile($originalFile);
        if (! $content = $this->_checkCache($originalFile, $cacheFile)) {
            // Copy the original file to the cache location
            copy($originalFile, $cacheFile);

            $this->_beforeFilter($originalFile, $cacheFile);
            // Run through each filter
            foreach ($this->_filters as $filterName => $Filter) {
                $arguments = isset($this->_request->params[$filterName]) ?
                    $this->_request->params[$filterName] : array();
                if (! is_array($arguments)) {
                    $arguments = array($filterName => $arguments);
                }
                $Filter->doFilter($cacheFile, $arguments);
            }

            $this->_afterFilter($originalFile, $cacheFile);
            $this->_lastModifiedDate = time();
            $content = file_get_contents($cacheFile);
        }

        return $content;
    }

    /**
     * Checks to see if cache exists and is the latest, if it does, return it
     *
     * @param string $originalFile
     * @param string $cacheFile
     *
     * @return bool|string
     */
    protected function _checkCache($originalFile, $cacheFile)
    {
        if (! file_exists($cacheFile)) {
            return false;
        }

        $cacheFileLastModified = filemtime($cacheFile);
        if (filemtime($originalFile) > $cacheFileLastModified) {
            return false;
        }

        if ($this->_lastModifiedDate < $cacheFileLastModified) {
            $this->_lastModifiedDate = $cacheFileLastModified;
        }

        return file_get_contents($cacheFile);
    }

    /**
     * Generate File Name Hash based on filename, request params and request options
     *
     * @param string $file
     *
     * @return string
     */
    protected function _generateCacheFile($file)
    {
        $requestOptions = serialize($this->_request->options);
        $params = serialize($this->_request->params);
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        return $this->_cacheDir . DS . md5($file) . '-' .
            md5($params . $requestOptions) . '.' . $ext;
    }
}