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
     * @var string
     */
    public $ext;

    /**
     * @var array
     */
    public $params = array();

    /**
     * @var array
     */
    public $files = array();

    /**
     * @var array
     */
    public $options = array();

    /**
     * @var array
     */
    protected $_rawParams = array();

    /**
     * @var array
     */
    protected $_allowedParams = array();

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct($options = array())
    {
        $this->options = $options;
    }

    /**
     * Parses the $_GET global variable and does sanity checks
     *
     * @throws ErrorException
     * @throws Asset\NotFoundException
     */
    public function init()
    {
        if (empty($_GET['files'])) {
            throw new ErrorException('Make sure you are using the correct .htaccess rules.');
        }

        // Handle legacy code for minifying
        if (preg_match('%^/minify/%', $_GET['files'])) {
            $_GET['files'] = substr($_GET['files'], 7);
            $_GET['minify'] = 'true';
        }

        $this->ext = pathinfo($_GET['files'], PATHINFO_EXTENSION);
        $supportedExtensions = Registry::getSupportedExtensions($this->ext);
        // Suppressing errors because Exceptions thrown in the callback cause Warnings.
        $this->files = @array_map(function($v) use ($supportedExtensions) {
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

            return WEBROOT . $v;
        }, explode(',', $_GET['files']));

        unset($_GET['files']);
        $this->_rawParams = $_GET;
    }

    /**
     * Returns the pre-parsed raw params
     *
     * @return array
     */
    public function getRawParams()
    {
        return $this->_rawParams;
    }

    /**
     * Parse query string parameter arguments based on mapped allowed params
     *
     * @param array $allowedParams
     */
    public function parseParams($allowedParams)
    {
        $this->_allowedParams = $allowedParams;
        $this->_setDefaultParams();

        foreach ($this->_rawParams as $checkParam => $value) {
            if (! $paramOptions = $this->_getParamOptions($checkParam)) {
                continue;
            }

            $param = $paramOptions['param'];
            $options = $paramOptions['options'];

            $paramValue = $this->_getParamValue($param, $options, $value);
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
    protected function _setDefaultParams()
    {
        foreach ($this->_allowedParams as $param => $options) {
            $this->params[$param] = null;
            if (! empty($options['arguments'])) {
                $this->params[$param] = array();
                foreach ($options['arguments'] as $arg => $opts) {
                    if (! empty($opts['default'])) {
                        $cast = ! empty($opts['cast']) ? $opts['cast'] : 'string';
                        $this->params[$param][$arg] = $this->_castValue($cast, $opts['default']);
                    }
                }
            } elseif (! empty($options['default'])) {
                $cast = ! empty($options['cast']) ? $options['cast'] : 'string';
                $this->params[$param] = $this->_castValue($cast, $options['default']);
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
    protected function _getParamOptions($checkParam)
    {
        if (isset($this->_allowedParams[$checkParam])) {
            return array('param' => $checkParam, 'options' => $this->_allowedParams[$checkParam]);
        } else {
            foreach ($this->_allowedParams as $param => $options) {
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
    protected function _getParamValue($param, $paramOptions, $value)
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
                    $ret[$arg] = $this->_getParamValue($arg, $opts, $match[2]);
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

            return $this->_castValue($cast, $value);
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
    protected function _castValue($cast, $value)
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