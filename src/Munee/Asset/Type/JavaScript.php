<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee\Asset\Type;

use Munee\Asset\Type;
use CoffeeScript;

/**
 * Handles JavaScript
 *
 * @author Cody Lundquist
 */
class JavaScript extends Type
{
    /**
     * Set headers for JavaScript
     */
    public function getHeaders()
    {
        $this->response->headerController->headerField('Content-Type', 'text/javascript');
    }


    /**
     * Callback method called before filters are run
     *
     * Overriding to run the file through CoffeeScript compiler if it has a .coffee extension
     *
     * @param string $originalFile
     * @param string $cacheFile
     */
    protected function beforeFilter($originalFile, $cacheFile)
    {
        if ('coffee' == pathinfo($originalFile, PATHINFO_EXTENSION)) {
            $coffeeScript = CoffeeScript\Compiler::compile(file_get_contents($originalFile), array('header' => false));
            file_put_contents($cacheFile, $coffeeScript);
        }
    }
}
