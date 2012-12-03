<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\asset;

use munee\ErrorException;

/**
 * Asset Class - All Asset Classes extend this one and have to implement the abstract method.
 *
 * @author Cody Lundquist
 */
abstract class Base
{
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
     * @var string
     */
    protected $_webroot;

    /**
     * Constructor
     *
     * @param \munee\Request $Request
     */
    public function __construct(\munee\Request $Request)
    {
        $this->_createDir(CACHE);
        $this->_request = $Request;
    }

    /**
     * All Base Sub-Classes must create this method and return their content
     *
     * @return string
     */
    abstract protected function _getContent();


    /**
     * All Base Sub-Classes must create this method to set their additional headers
     */
    abstract protected function _getHeaders();

    /**
     * Render out the asset's content
     * Also set gzip Encoding to save some bandwidth (Only if the server can handle it)
     * 
     * @return string
     */
    public function render()
    {
        $content = $this->_getContent();
        $this->_setHeaders();
        ob_start('ob_gzhandler') || ob_start();
        echo $content;
        ob_flush();

        return ob_get_clean();
    }

    /**
     * Set Headers for Response
     */
    protected function _setHeaders()
    {
        if (! $this->_request->minify) {
            $eTag = md5($this->_lastModifiedDate . $this->_content);
            $checkModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ?
                $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false;
            $checkETag = isset($_SERVER['HTTP_IF_NONE_MATCH']) ?
                $_SERVER['HTTP_IF_NONE_MATCH'] : false;

            if (
                ($checkModifiedSince && strtotime($checkModifiedSince) == $this->_lastModifiedDate) ||
                $checkETag == $eTag
            ) {
                header("HTTP/1.1 304 Not Modified");
                exit;
            } else {
                header("Last-Modified: " . gmdate("D, d M Y H:i:s", $this->_lastModifiedDate) . " GMT");
                header('Cache-Control: public');
                header('ETag: ' . $eTag);
            }
        } else {
            // Do not cache if not minified
            header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        }

        $this->_getHeaders();
    }

    /**
     * Creates directories
     *
     * @param $dir
     *
     * @return bool
     * @throws ErrorException
     */
    protected function _createDir($dir)
    {
        if (! is_dir($dir) && ! mkdir($dir, 0777, true)) {
            throw new ErrorException(
                'The follow directory could not be made, please create it: ' . $dir);
        }

        return true;
    }
}