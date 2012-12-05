<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee;

/**
 * Munee Response Class
 *
 * @author Cody Lundquist
 */
class Response
{
    /**
     * @var Request
     */
    protected $_request;

    /**
     * Constructor
     *
     * @param Request $Request
     */
    public function __construct(Request $Request)
    {
        $this->_request = $Request;
    }

    /**
     * Instantiates the correct Asset Class, sets the correct headers, and returns a response.
     * It will try and use Gzip to compress the content and save bandwidth
     *
     * @return string
     */
    public function render()
    {
        $AssetClass = asset\Registry::getClass($this->_request);
        $this->_setHeaders($AssetClass);

        ob_start('ob_gzhandler') || ob_start();
        echo $AssetClass;
        ob_flush();

        return ob_get_clean();
    }

    /**
     * Set Headers for Response
     * 
     * @param object $AssetClass
     *
     * @throws ErrorException
     */
    protected function _setHeaders($AssetClass)
    {
        // Must be a Sub-Class of \munee\asset\Base
        if (is_subclass_of($AssetClass, '\\asset\\Base')) {
            throw new ErrorException(
                get_class($AssetClass) . ' is not a sub class of \\munee\\asset\\Base'
            );
        }

        // We don't want the browser to handle any cache, Munee will handle that.
        header("Cache-Control: no-cache");

        $lastModifiedDate = $AssetClass->getlastModifiedDate();
        $eTag = md5($lastModifiedDate . $AssetClass->getContent());
        $checkModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ?
            $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false;
        $checkETag = isset($_SERVER['HTTP_IF_NONE_MATCH']) ?
            $_SERVER['HTTP_IF_NONE_MATCH'] : false;

        if (
            ($checkModifiedSince && strtotime($checkModifiedSince) == $lastModifiedDate) ||
            $checkETag == $eTag
        ) {
            header("HTTP/1.1 304 Not Modified");
            exit;
        } else {
            header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastModifiedDate) . " GMT");
            header('ETag: ' . $eTag);
        }

        $AssetClass->getHeaders();
    }
}