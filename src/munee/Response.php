<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee;

use munee\ErrorException;
use munee\asset\AssetNotFoundException;

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

    public function __construct(Request $request)
    {
        $this->_request = $request;
    }

    /**
     * Instantiates the correct Asset Class and returns a response
     *
     * @return string
     * @throws ErrorException
     * @throws AssetNotFoundException
     */
    public function render()
    {
        try {
            $class = 'munee\\asset\\' . ucfirst($this->_request->type);
            if (! class_exists($class)) {
                throw new ErrorException("The following Asset Class cannot be found: {$class}");
            }
            $Asset = new $class($this->_request);

            return $Asset->render();
        } catch (AssetNotFoundException $e) {
            header("HTTP/1.0 404 Not Found");
            header("Status: 404 Not Found");
            return 'Error: ' . $e->getMessage();
        } catch (ErrorException $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
}