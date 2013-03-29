<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee;

/**
 * Munee Response Class
 *
 * @author Cody Lundquist
 */
class Response
{
    /**
     * @var boolean
     */
    public $notModified = false;

    /**
     * @var Object
     */
    protected $_assetType;
    
    /**
     * @var object
     */
    public $headerController;

    /**
     * Constructor
     *
     * @param object $AssetType
     *
     * @throws ErrorException
     */
    public function __construct($AssetType)
    {
        // Must be a Sub-Class of \Munee\asset\Type
        $baseClass = '\\Munee\\asset\\Type';
        if (! is_subclass_of($AssetType, $baseClass)) {
            throw new ErrorException(
                get_class($AssetType) . ' is not a sub class of ' . $baseClass
            );
        }

        $this->_assetType = $AssetType;

        $this->setHeaderController(new Asset\HeaderSetter);
 
        $AssetType->_response = $this;
    }

    /**
     * Returns the Asset Types content.
     * It will try and use Gzip to compress the content and save bandwidth
     *
     * @return string
     */
    public function render()
    {
        $content = $this->_assetType->getContent();
        if (! $ret = ob_gzhandler($content, PHP_OUTPUT_HANDLER_START | PHP_OUTPUT_HANDLER_END)) {
            $ret = $content;
        }

        return $ret;
    }
    
    /**
     * Set controller for setting headers.
     * 
     * @param string $header_controller
     * 
     * @return onject
     * 
     * @throws ErrorException
     */
    public function setHeaderController($header_controller)
    {
        if(!($header_controller instanceof Asset\HeaderSetter))
            throw new ErrorException('Header controller must be an instance of HeaderSetter.');
            
        $this->headerController = $header_controller;

        return $this;
    }

    /**
     * Set Headers for Response
     * 
     * @throws ErrorException
     */
    public function setHeaders()
    {
        $lastModifiedDate = $this->_assetType->getLastModifiedDate();
        $eTag = md5($lastModifiedDate . $this->_assetType->getContent());
        $checkModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ?
            $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false;
        $checkETag = isset($_SERVER['HTTP_IF_NONE_MATCH']) ?
            $_SERVER['HTTP_IF_NONE_MATCH'] : false;

        if (
            ($checkModifiedSince && strtotime($checkModifiedSince) == $lastModifiedDate) ||
            $checkETag == $eTag
        ) {
            $this->headerController->statusCode('HTTP/1.1', 304, 'Not Modified');
            $this->notModified = true;
        } else {
            // We don't want the browser to handle any cache, Munee will handle that.
            $this->headerController->headerField('Cache-Control', 'must-revalidate');
            $this->headerController->headerField('Last-Modified', gmdate('D, d M Y H:i:s', $lastModifiedDate) . ' GMT');
            $this->headerController->headerField('ETag', $eTag);
            $this->_assetType->getHeaders();
        }
    }
}