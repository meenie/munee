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
     * @var string
     */
    public $ext;
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
        try {
            // Fixing a legacy issue
            if (isset($_GET['type'])) {
                $_GET['ext'] = $_GET['type'];
            }

            if (! isset($_GET['minify']) || empty($_GET['ext']) || empty($_GET['files'])) {
                throw new ErrorException('Make sure you are using the correct .htaccess rules.');
            }

            $this->ext = $_GET['ext'];
            $this->minify = ! empty($_GET['minify']);
            $this->files = explode(',', $_GET['files']);
        } catch (ErrorException $e) {
            echo 'Error: ' . $e->getMessage();
            exit;
        }
    }
}