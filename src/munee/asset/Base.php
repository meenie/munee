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