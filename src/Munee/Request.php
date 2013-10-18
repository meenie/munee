<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee;

use Munee\ErrorException;
use Munee\Asset\Registry;

/**
 * Munee Request Class
 *
 * @author Cody Lundquist
 */
class Request
{
    /**
     * Stores the path to Webroot
     *
     * @var string
     */
    public $webroot = WEBROOT;

    /**
     * Stores the file extension of the current request
     *
     * @var string
     */
    public $ext;

    /**
     * Stores the array of passed in parameters
     *
     * @var array
     */
    public $params = array();

    /**
     * Stores the array of files passed in
     *
     * @var array
     */
    public $files = array();

    /**
     * Stores the array of Request Options
     *
     * @var array
     */
    public $options = array();

    /**
     * Stores the array of Raw $_GET parameters
     *
     * @var array
     */
    protected $rawParams = array();

    /**
     * Stores the array of allowed parameters for the particular asset being processed
     *
     * @var array
     */
    protected $allowedParams = array();
    
    /**
     * Stores the string of raw files passed in from $_GET
     *
     * @var string
     */
    protected $rawFiles;
    
    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct($options = array())
    {
        $this->options = $options;
        $this->rawFiles = isset($_GET['files']) ? $_GET['files'] : '';
        unset($_GET['files']);

        $this->rawParams = $_GET;
    }
    
    /**
     * Sets the document root.
     *
     * @param string $path
     * 
     * @return object
     */
    public function setWebroot($path)
    {
        $this->webroot = $path;
        
        return $this;
    }
    
    /**
     * Sets either an individual _rawParams key - or overwrites the whole array.
     *
     * @param mixed $key
     * @param mixed $value
     * 
     * @return object
     */
    public function setRawParam($key, $value = null)
    {
        if (is_array($key)) {
            $this->rawParams = $key;
        } else {
            $this->rawParams[$key] = $value;
        }

        return $this;
    }

    /**
     * Returns the pre-parsed raw params
     *
     * @return array
     */
    public function getRawParams()
    {
        return $this->rawParams;
    }
    
    /**
     * Sets the $rawFiles.
     *
     * @param string $files
     * 
     * @return object
     */
    public function setFiles($files)
    {
        $this->rawFiles = $files;

        return $this;
    }

    /**
     * Parses the $rawFiles and does sanity checks
     *
     * @throws ErrorException
     * @throws Asset\NotFoundException
     */
    public function init()
    {
        if (empty($this->rawFiles)) {
            throw new ErrorException('No file specified; make sure you are using the correct .htaccess rules.');
        }

        // Handle legacy code for minifying
        if (preg_match('%^/minify/%', $this->rawFiles)) {
            $this->rawFiles = substr($this->rawFiles, 7);
            $this->setRawParam('minify', 'true');
        }

        $this->ext = strtolower(pathinfo($this->rawFiles, PATHINFO_EXTENSION));
        $supportedExtensions = Registry::getSupportedExtensions($this->ext);
        // Suppressing errors because Exceptions thrown in the callback cause Warnings.
        $webroot = $this->webroot;
        $this->files = @array_map(function($v) use ($supportedExtensions, $webroot) {
            // Make sure all the file extensions are supported
            if (! in_array(strtolower(pathinfo($v, PATHINFO_EXTENSION)), $supportedExtensions)) {
                throw new ErrorException('All requested files need to be: ' . implode(', ', $supportedExtensions));
            }
            // Strip any parent directory slugs (../) - loop through until they are all gone
            $count = 1;
            while ($count > 0) {
                $v = preg_replace('%(/\\.\\.?|\\.\\.?/)%', '', $v, -1, $count);
                // If there is no slash prefix, add it back in
                if (substr($v, 0, 1) != '/') {
                    $v = '/' . $v;
                }
            }

            // Remove sub-folder if in the path, it shouldn't be there.
            $v = str_replace(SUB_FOLDER, '', $v);

            return $webroot . $v;
        }, explode(',', $this->rawFiles));
    }

    /**
     * Parse query string parameter arguments based on mapped allowed params
     *
     * @param array $allowedParams
     */
    public function parseParams($allowedParams)
    {
        $this->allowedParams = $allowedParams;
        $this->setDefaultParams();

        foreach ($this->rawParams as $checkParam => $value) {
            if (! $paramOptions = $this->getParamOptions($checkParam)) {
                continue;
            }

            $param = $paramOptions['param'];
            $options = $paramOptions['options'];

            $paramValue = $this->getParamValue($param, $options, $value);
            if (isset($this->params[$param]) && is_array($this->params[$param])) {
                $this->params[$param] = array_merge($this->params[$param], $paramValue);
            } else {
                $this->params[$param] = $paramValue;
            }
        }
    }

    /**
     * Setup the default values for the allowed parameters
     */
    protected function setDefaultParams()
    {
        foreach ($this->allowedParams as $param => $options) {
            $this->params[$param] = null;
            if (! empty($options['arguments'])) {
                $this->params[$param] = array();
                foreach ($options['arguments'] as $arg => $opts) {
                    if (! empty($opts['default'])) {
                        $cast = ! empty($opts['cast']) ? $opts['cast'] : 'string';
                        $this->params[$param][$arg] = $this->castValue($cast, $opts['default']);
                    }
                }
            } elseif (! empty($options['default'])) {
                $cast = ! empty($options['cast']) ? $options['cast'] : 'string';
                $this->params[$param] = $this->castValue($cast, $options['default']);
            }
        }
    }


    /**
     * Grabs the params options taking into account any aliases
     *
     * @param $checkParam
     *
     * @return bool|array
     */
    protected function getParamOptions($checkParam)
    {
        if (isset($this->allowedParams[$checkParam])) {
            return array('param' => $checkParam, 'options' => $this->allowedParams[$checkParam]);
        } else {
            foreach ($this->allowedParams as $param => $options) {
                if (! empty($options['alias']) && in_array($checkParam, (array) $options['alias'])) {
                    return compact('param', 'options');
                }
            }
        }

        return false;
    }


    /**
     * Grabs a value from a param by running it through supplied regex
     *
     * @param $param
     * @param $paramOptions
     * @param $value
     *
     * @return string|array
     *
     * @throws \Munee\ErrorException
     */
    protected function getParamValue($param, $paramOptions, $value)
    {
        if (! empty($paramOptions['arguments'])) {
            $ret = array();
            foreach ($paramOptions['arguments'] as $arg => $opts) {
                $p = $arg;
                if (! empty($opts['alias'])) {
                    $alias = implode('|', (array) $opts['alias']);
                    $p .= "|\\b{$alias}";
                }
                $regex = "(\\b{$p})\\[(.*?)\\]";
                if (preg_match("%{$regex}%", $value, $match)) {
                    $ret[$arg] = $this->getParamValue($arg, $opts, $match[2]);
                }
            }

            return $ret;
        } else {
            // Using RegEx?
            if (! empty($paramOptions['regex'])) {
                if (! preg_match("%^(?:{$paramOptions['regex']})$%", $value)) {
                    throw new ErrorException("'{$value}' is not a valid value for: {$param}");
                }
            }

            $cast = ! empty($paramOptions['cast']) ? $paramOptions['cast'] : 'string';

            return $this->castValue($cast, $value);
        }
    }


    /**
     * Helper function to cast values
     *
     * @param $cast
     * @param $value
     *
     * @return bool|int|string
     */
    protected function castValue($cast, $value)
    {
        switch ($cast) {
            case 'integer';
                $value = (integer) $value;
                break;
            case 'boolean';
                $value = in_array($value, array('true', 't', 'yes', 'y'));
                break;
            case 'string':
            default:
                $value = (string) $value;
        }

        return $value;
    }
}