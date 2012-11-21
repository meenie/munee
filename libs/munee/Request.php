<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee;

use munee\ErrorException;

/**
 * Munee Request Class
 *
 * @author Cody Lundquist
 */
class Request
{
    /**
     * @var string
     */
    public $type;
    /**
     * @var bool
     */
    public $minify;
    /**
     * @var array
     */
    public $files;

    /**
     * Constructor
     */
    public function __construct()
    {
        if (! isset($_GET['minify']) || empty($_GET['type']) || empty($_GET['files'])) {
            throw new ErrorException('Make sure you are using the correct .htaccess rules.');
        }

        $this->type = 'less' == $_GET['type'] ? 'css' : $_GET['type'];
        $this->minify = ! empty($_GET['minify']);
        $this->files = explode(',', $_GET['files']);
    }
}