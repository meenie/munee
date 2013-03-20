<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee\Asset;

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
     * @param string $originalFile
     * @param array $arguments
     */
    abstract public function doFilter($originalFile, $arguments);
}