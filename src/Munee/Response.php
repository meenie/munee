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
     * Used to check if the request is Not Modified so Munee can return 304 if that is the case
     *
     * @var boolean
     */
    public $notModified = false;

    /**
     * Instance of a Asset\HeaderSetter class
     *
     * @var Asset\HeaderSetter
     */
    public $headerController;

    /**
     * Instance of a Asset\Type dynamically instantiated in the Constructor
     *
     * @var Asset\Type
     */
    protected $assetType;

    /**
     * Constructor
     *
     * @param object $AssetType
     *
     * @throws ErrorException
     */
    public function __construct($AssetType)
    {
        // Must be a Sub-Class of \Munee\Asset\Type
        $baseClass = '\\Munee\\asset\\Type';
        if (! is_subclass_of($AssetType, $baseClass)) {
            throw new ErrorException(
                get_class($AssetType) . ' is not a sub class of ' . $baseClass
            );
        }

        $this->assetType = $AssetType;

        $AssetType->setResponse($this);
    }

    /**
     * Set controller for setting headers.
     * 
     * @param object $headerController
     * 
     * @return self
     * 
     * @throws ErrorException
     */
    public function setHeaderController($headerController)
    {
        if(! $headerController instanceof Asset\HeaderSetter) {
            throw new ErrorException('Header controller must be an instance of Asset\HeaderSetter.');
        }

        $this->headerController = $headerController;

        return $this;
    }

    /**
     * Set Headers for Response
     *
     * @param integer $maxAge - Used with the cache-control to tell the browser how long it should wait before revalidating
     *
     * @return self
     *
     * @throws ErrorException
     */
    public function setHeaders($maxAge)
    {
        $lastModifiedDate = $this->assetType->getLastModifiedDate();
        $eTag = md5($lastModifiedDate . $this->assetType->getContent());
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
            $this->headerController->headerField('Cache-Control', 'max-age=' . $maxAge . ', must-revalidate');
            $this->headerController->headerField('Last-Modified', gmdate('D, d M Y H:i:s', $lastModifiedDate) . ' GMT');
            $this->headerController->headerField('ETag', $eTag);
            $this->assetType->getHeaders();
        }

        return $this;
    }

    /**
     * Returns the Asset Types content.
     * It will try and use Gzip to compress the content and save bandwidth
     *
     * @return string
     */
    public function render()
    {
        $content = $this->assetType->getContent();
        /**
         * Do not use ob_gzhandler() if zlib.output_compression ini option is set
         * This will gzip the output twice and the text will be garbled
         */
        if (@ini_get('zlib.output_compression')) {
            $ret = $content;
        } else if (! $ret = ob_gzhandler($content, PHP_OUTPUT_HANDLER_START | PHP_OUTPUT_HANDLER_END)) {
            $ret = $content;
        }

        return $ret;
    }
}