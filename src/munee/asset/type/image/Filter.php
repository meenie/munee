<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\asset\type\image;

use munee\ErrorException;
use munee\Utils;

/**
 * Filter to manipulate images
 *
 * @author Cody Lundquist
 */
abstract class Filter
{
    /**
     * @var array
     */
    protected $_params = array();

    /**
     * @var string
     */
    protected $_originalImage;

    /**
     * @var string
     */
    protected $_newImage;

    /**
     * @var array
     */
    protected $_allowedParams = array();

    /**
     * @var int
     */
    protected static $_numberOfAllowedResizes = 3;

    /**
     * @var int
     */
    protected static $_resizeTimeLimit = 300;

    /**
     * @param $originalImage
     * @param $params
     *
     * @return array
     *
     * @throws ErrorException
     */
    public static function run($originalImage, $params)
    {
        $ret = array(
            'image' => $originalImage,
            'changed' => false
        );

        if (empty($params)) {
            return $ret;
        }

        $cacheDir = CACHE . DS . 'images';
        Utils::createDir($cacheDir);
        $hashedName = self::_generateFileNameHash($originalImage, $params);
        $newImage = $cacheDir . DS . $hashedName;
        $ret['image'] = $newImage;

        // No need to recreate it if it already exists and original copy hasn't changed
        if (file_exists($newImage) && filemtime($originalImage) < filemtime($newImage)) {
            return $ret;
        }

        self::_checkReferrer();
        self::_checkNumberOfAllowedFilters($newImage);

        // Copy the file to the cache dir if we are going to manipulate it
        copy($originalImage, $newImage);

        // Run through the list of params and instantiate each filter
        foreach ($params as $param => $options) {
            $filterClass = 'munee\\asset\\type\\image\\filter\\' . ucfirst($param);
            if (class_exists($filterClass)) {
                $Filter = new $filterClass($originalImage, $newImage, $options);
                $Filter->_filter();
            }
        }

        $ret['changed'] = true;

        return $ret;
    }

    /**
     * Sets some class variables so a filter can do what it do baby
     *
     * @param string $originalImage
     * @param string $newImage
     * @param string $params
     */
    public function __construct($originalImage, $newImage, $params)
    {
        $this->_params = $this->_parseParams($params);
        $this->_originalImage = $originalImage;
        $this->_newImage = $newImage;
    }

    /**
     * A Sub-Class uses this method to manipulate the image based on the params passed in
     */
    abstract protected function _filter();

    /**
     * Generate File Name Hash based on resize arguments
     *
     * @param string $file - full path to file and file name
     * @param array $params
     *
     * @return string
     */
    protected static function _generateFileNameHash($file, $params)
    {
        return md5($file) . '-' . md5(serialize($params)) . '.' . pathinfo($file, PATHINFO_EXTENSION);
    }

    /**
     * Check to make sure the referrer domain is the same as the domain where the image exists.
     *
     * @throws ErrorException
     */
    protected static function _checkReferrer()
    {
        if (! isset($_SERVER['HTTP_REFERER'])) {
            throw new ErrorException('Direct resizing is not allowed.');
        }

        $referrer = preg_replace('%^https?://%', '', $_SERVER['HTTP_REFERER']);
        if (! preg_match("%^{$_SERVER['SERVER_NAME']}%", $referrer)) {
            throw new ErrorException('Referer does not match the correct domain.');
        }
    }


    /**
     * Check number of allowed resizes within a set time limit
     *
     * @throws ErrorException
     */
    protected static function _checkNumberOfAllowedFilters($newImage)
    {
        $pathInfo = pathinfo($newImage);
        $fileNameHash = preg_replace('%-.*$%', '', $pathInfo['filename']);
        // Grab all the similar files
        $cachedImages = glob($pathInfo['dirname'] . DS . $fileNameHash . '*');
        // Loop through and remove the ones that are older than the time limit
        foreach ($cachedImages as $k => $image) {
            if (filemtime($image) < time() - static::$_resizeTimeLimit) {
                unset($cachedImages[$k]);
            }
        }
        // Check and see if we've reached the maximum allowed resizes within the current time limit.
        if (count($cachedImages) >= static::$_numberOfAllowedResizes) {
            throw new ErrorException('You cannot create anymore resizes at this time.');
        }
    }

    /**
     * Parse a string of resize arguments
     *
     * @param string $params
     *
     * @return array
     *
     * @throws ErrorException
     */
    protected function _parseParams($params)
    {
        $ret = array();
        $regExs = $this->_getAllowedParamsRegEx();

        // Set defaults if need be
        foreach ($this->_allowedParams as $param => $options) {
            if (! empty($options['default']) && empty($ret[$param])) {
                $ret[$param] = $options['default'];
            }
        }

        // Grab out the values
        foreach ($regExs as $param => $regEx) {
            if (preg_match("%{$regEx}%", $params, $match)) {
                $val = strtolower($match[$param]);
                $ret[$param] = $val;
            }
        }

        // Cast the values based on the allowed params options
        foreach ($ret as $param => $val) {
            $cast = isset($this->_allowedParams[$param]['cast']) ?
                $this->_allowedParams[$param]['cast'] : 'string';
            switch ($cast) {
                case 'integer';
                    $ret[$param] = (integer) $val;
                    break;
                case 'boolean';
                    $ret[$param] = 'true' == $val;
                    break;
                default:
                    $ret[$param] = (string) $val;
                    break;
            }
        }

        return $ret;
    }

    /**
     * Builds the Regular Expressions for all the Allowed Parameters
     *
     * @return array
     */
    protected function _getAllowedParamsRegEx()
    {
        $ret = array();
        foreach ($this->_allowedParams as $param => $options) {
            $p = $param;
            if (! empty($options['alias'])) {
                $alias = implode('|', (array) $options['alias']);
                $p .= "|{$alias}";
            }

            $ret[$param] = "(?:{$p})\\[(?P<{$param}>{$options['regex']})\\]";
        }

        return $ret;
    }
}