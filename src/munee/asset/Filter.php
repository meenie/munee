<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\asset;

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
    protected $_allowedParams = array();

    /**
     * @return array
     */
    public function getAllowedParams()
    {
        return $this->_allowedParams;
    }

    /**
     * A Sub-Class uses this method to manipulate the image based on the params passed in
     *
     * @param string $originalImage
     * @param array $arguments
     */
    abstract public function filter($originalFile, $arguments);
}