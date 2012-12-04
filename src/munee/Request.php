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
    public $ext;
    /**
     * @var array
     */
    public $get;
    /**
     * @var array
     */
    public $files;

    /**
     * Constructor
     */
    public function __construct()
    {
        if (empty($_GET['ext']) || empty($_GET['files'])) {
            throw new ErrorException('Make sure you are using the correct .htaccess rules.');
        }

        // Handle legacy code for minifying
        if (preg_match('%^/minify/%', $_GET['files'])) {
            $_GET['files'] = substr($_GET['files'], 7);
            $_GET['minify'] = true;
        }

        $this->ext = $_GET['ext'];
        unset($_GET['ext']);
        $this->files = explode(',', $_GET['files']);
        unset($_GET['files']);
        $this->get = $_GET;
    }
}