<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee\Mocks;

use Munee\Asset\Type;

/**
 * Mock Asset Type class for Response Test
 *
 * @author Cody Lundquist
 */
class MockAssetType extends Type
{
    /**
     * Don't want to do any constructing
     */
    public function __construct() {
        $this->content = 'foo';
    }

    /**
     * Override function to set test header
     */
    public function getHeaders()
    {
        header("Content-Type: text/test");
    }

    /**
     * Override function to return an arbitrary timestamp
     *
     * @return int
     */
    public function getLastModifiedDate()
    {
        return 123456789;
    }
}