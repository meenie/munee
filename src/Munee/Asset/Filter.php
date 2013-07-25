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
     * List of allowed params for a particular filter
     *
     * @var array
     */
    protected $allowedParams = array();

    /**
     * Getter for $allowedParams
     *
     * @return array
     */
    public function getAllowedParams()
    {
        return $this->allowedParams;
    }

    /**
     * A Sub-Class uses this method to manipulate the image based on the params passed in
     *
     * @param string $originalFile
     * @param array $arguments
     * @param array $typeOptions
     */
    abstract public function doFilter($originalFile, $arguments, $typeOptions);
}