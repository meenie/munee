<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee\Asset;

use Munee\ErrorException;
use Munee\Request;
use Munee\Response;
use Munee\Utils;
use Munee\Asset\NotFoundException;

/**
 * Base Asset Class
 * All Asset Types need to extend this and implement the getHeaders() method.
 *
 * @author Cody Lundquist
 */
abstract class Type
{
    /**
     * Stores the Request Options for the Asset Type
     *
     * @var array
     */
    protected $options = array();

    /**
     * Stores the list of filters that will be applied to the requested asset.
     *
     * @var array
     */
    protected $filters = array();

    /**
     * Stores the path to the cache directory
     *
     * @var string
     */
    protected $cacheDir;

    /**
     * Stores the last modified date (Epoch) for the requested asset
     *
     * @var integer
     */
    protected $lastModifiedDate = 0;

    /**
     * Stores the content of the asset
     *
     * @var string
     */
    protected $content;

    /**
     * Reference to the \Munee\Request class
     *
     * @var \Munee\Request
     */
    protected $request;
    
    /**
     * Reference to the \Munee\Response class
     *
     * @var \Munee\Response
     */
    protected $response;

    /**
     * All Type Sub-Classes must create this method to set their additional headers
     */
    abstract public function getHeaders();

    /**
     * Constructor
     *
     * @param \Munee\Request $Request
     *
     * @throws NotFoundException
     */
    public function __construct(Request $Request)
    {
        $this->request = $Request;

        // Pull in filters based on the raw params that were passed in
        $rawParams = $Request->getRawParams();
        $assetShortName = preg_replace('%^.*\\\\%','', get_class($this));
        $allowedParams = array();
        foreach (array_keys($rawParams) as $filterName) {
            $filterClass = 'Munee\\Asset\\Filter\\' . $assetShortName . '\\' . ucfirst($filterName);
            if (class_exists($filterClass)) {
                $Filter = new $filterClass();
                $allowedParams += $Filter->getAllowedParams();
                $this->filters[$filterName] = $Filter;
            }
        }

        // Parse the raw params based on a map of allowedParams for those filters
        $this->request->parseParams($allowedParams);

        $this->cacheDir = MUNEE_CACHE . DS . $assetShortName;

        $optionsKey = strtolower($assetShortName);
        // Set the AssetType options if someone were passed in through the Request Class
        if (isset($this->request->options[$optionsKey])) {
            $this->options = array_merge(
                $this->options,
                $this->request->options[$optionsKey]
            );
        }

        // Create cache dir if needed
        Utils::createDir($this->cacheDir);
    }

    /**
     * Process all files in the request and set the content
     */
    public function init()
    {
        $content = array();
        foreach ($this->request->files as $file) {
            $cacheFile = $this->generateCacheFile($file);

            if (! $fileContent = $this->checkCache($file, $cacheFile)) {
                $this->setupFile($file, $cacheFile);
                $fileContent = $this->getFileContent($file, $cacheFile);
            }

            $content[] = $this->afterGetFileContent($fileContent);
        }

        $this->content = implode("\n", $content);
    }

    /**
     * Sets the Munee\Response class to the AssetType
     *
     * @param $Response
     *
     * @throws \Munee\ErrorException
     */
    public function setResponse($Response)
    {
        if (! $Response instanceof Response) {
            throw new ErrorException('Response class must be an instance of Munee\Response');
        }

        $this->response = $Response;
    }

    /**
     * Grabs the content for the Response class
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Return a this requests Last Modified Date.
     *
     * @return integer timestamp
     */
    public function getLastModifiedDate()
    {
        return $this->lastModifiedDate;
    }

    /**
     * If an exception is handled this function will fire and clean up any files
     * that have been cached as they have not properly compiled.
     */
    public function cleanUpAfterError()
    {
        foreach ($this->request->files as $file) {
            $cacheFile = $this->generateCacheFile($file);
            unlink($cacheFile);
        }
    }

    /**
     * Callback method called before filters are run
     *
     * @param string $originalFile
     * @param string $cacheFile
     */
    protected function beforeFilter($originalFile, $cacheFile) {}

    /**
     * Callback function called after filters are run
     *
     * @param string $originalFile
     * @param string $cacheFile
     */
    protected function afterFilter($originalFile, $cacheFile) {}

    /**
     * Callback function called after _getFileContent() is called
     *
     * @param string $content
     *
     * @return string
     */
    protected function afterGetFileContent($content)
    {
        return $content;
    }

    /**
     * Checks to see if the file exists and then copies it to the cache folder for further manipulation
     *
     * @param $originalFile
     * @param $cacheFile
     *
     * @throws NotFoundException
     */
    protected function setupFile($originalFile, $cacheFile)
    {
        // Check if the file exists
        if (! file_exists($originalFile)) {
            throw new NotFoundException('File does not exist: ' . str_replace($this->request->webroot, '', $originalFile));
        }

        // Copy the original file to the cache location
        copy($originalFile, $cacheFile);
    }

    /**
     * Grab a files content but check to make sure it exists first
     *
     * @param string $originalFile
     * @param string $cacheFile
     *
     * @return string
     *
     * @throws NotFoundException
     */
    protected function getFileContent($originalFile, $cacheFile)
    {
        $this->beforeFilter($originalFile, $cacheFile);
        // Run through each filter
        foreach ($this->filters as $filterName => $Filter) {
            $arguments = isset($this->request->params[$filterName]) ?
                $this->request->params[$filterName] : array();
            if (! is_array($arguments)) {
                $arguments = array($filterName => $arguments);
            }
            // Do not minify if .min. is in the filename as it has already been minified
            if(strpos($originalFile, '.min.') !== FALSE) {
                $arguments['minify'] = false;
            }
            $Filter->doFilter($cacheFile, $arguments, $this->options);
        }

        $this->afterFilter($originalFile, $cacheFile);
        $this->lastModifiedDate = time();

        return file_get_contents($cacheFile);
    }

    /**
     * Checks to see if cache exists and is the latest, if it does, return it
     *
     * @param string $originalFile
     * @param string $cacheFile
     *
     * @return bool|string
     */
    protected function checkCache($originalFile, $cacheFile)
    {
        if (! file_exists($cacheFile) || ! file_exists($originalFile)) {
            return false;
        }

        $cacheFileLastModified = filemtime($cacheFile);
        if (filemtime($originalFile) > $cacheFileLastModified) {
            return false;
        }

        if ($this->lastModifiedDate < $cacheFileLastModified) {
            $this->lastModifiedDate = $cacheFileLastModified;
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
    protected function generateCacheFile($file)
    {
        $cacheSalt = serialize(array(
            $this->request->options,
            MUNEE_USING_URL_REWRITE,
            MUNEE_DISPATCHER_FILE
        ));
        $params = serialize($this->request->params);
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        $fileHash = md5($file);
        $optionsHash = md5($params . $cacheSalt);

        $cacheDir = $this->cacheDir . DS . substr($fileHash, 0, 2);

        Utils::createDir($cacheDir);

        return $cacheDir . DS . substr($fileHash, 2) . '-' . $optionsHash . '.' . $ext;
    }
}
