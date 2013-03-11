<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\asset\type;

use munee\ErrorException;
use munee\asset\Type;

/**
 * Handles Images
 *
 * @author Cody Lundquist
 */
class Image extends Type
{
    /**
     * @var array
     */
    protected $_options = array(
        // How many filters can be done within the `allowedFiltersTimeLimit`
        'numberOfAllowedFilters' => 3,
        // Number of seconds - default is 5 minutes
        'allowedFiltersTimeLimit' => 300,
        // Should the referrer be checked for security
        'checkReferrer' => true,
        'placeholders' => false
    );

    protected $_placeholdersDefault = array(
        'generate' => array(
            'color' => '#DDDDDD',
            'text' => 'Placeholder'
        ),
        'image' => null
    );

    /**
     * Overwrite the _setupFile function so placeholder images can be shown instead of broken images
     *
     *
     * @param string $originalFile
     * @param string $cacheFile
     */
    protected function _setupFile($originalFile, $cacheFile)
    {
        if (! file_exists($originalFile)) {

            if (! empty($this->_options['placeholder']) && file_exists($this->_options['placeholder'])) {
                $originalFile = $this->_options['placeholder'];
            }
        }

        parent::_setupFile($originalFile, $cacheFile);

    }

    /**
     * Checks to see if cache exists and is the latest, if it does, return it
     *
     * Extra security checks for images
     *
     * @param string $originalFile
     * @param string $cacheFile
     *
     * @return bool|string
     */
    protected function _checkCache($originalFile, $cacheFile)
    {
        if (! $return = parent::_checkCache($originalFile, $cacheFile)) {
            /**
             * If using the placeholder when the original file doesn't
             * and it has already been cached, return the cached contents.
             * Also make sure the placeholder hasn't been modified since being cached.
             */
            if (
                ! file_exists($originalFile) &&
                ! empty($this->_options['placeholder']) &&
                file_exists($this->_options['placeholder']) &&
                file_exists($cacheFile) &&
                filemtime($cacheFile) > filemtime($this->_options['placeholder'])
            ) {
                return file_get_contents($cacheFile);
            }

            if ($this->_options['checkReferrer']) {
                $this->_checkReferrer();
            }

            $this->_checkNumberOfAllowedFilters($cacheFile);
        }

        return $return;
    }

    /**
     * Set additional headers just for an Image
     */
    public function getHeaders()
    {
        switch ($this->_request->ext) {
            case 'jpg':
            case 'jpeg':
                header("Content-Type: image/jpg");
                break;
            case 'png':
                header("Content-Type: image/png");
                break;
            case 'gif':
                header("Content-Type: image/gif");
                break;
        }
    }

    /**
     * Check to make sure the referrer domain is the same as the domain where the image exists.
     *
     * @throws ErrorException
     */
    protected function _checkReferrer()
    {
        if (! isset($_SERVER['HTTP_REFERER'])) {
            throw new ErrorException('Direct image manipulation is not allowed.');
        }

        $referrer = preg_replace('%^https?://%', '', $_SERVER['HTTP_REFERER']);
        if (! preg_match("%^{$_SERVER['SERVER_NAME']}%", $referrer)) {
            throw new ErrorException('Referrer does not match the correct domain.');
        }
    }

    /**
     * Check number of allowed resizes within a set time limit
     *
     * @param string $checkImage
     *
     * @throws ErrorException
     */
    protected function _checkNumberOfAllowedFilters($checkImage)
    {
        $pathInfo = pathinfo($checkImage);
        $fileNameHash = preg_replace('%-.*$%', '', $pathInfo['filename']);
        // Grab all the similar files
        $cachedImages = glob($pathInfo['dirname'] . DS . $fileNameHash . '*');
        // Loop through and remove the ones that are older than the time limit
        foreach ($cachedImages as $k => $image) {
            if (filemtime($image) < time() - $this->_options['allowedFiltersTimeLimit']) {
                unset($cachedImages[$k]);
            }
        }
        // Check and see if we've reached the maximum allowed resizes within the current time limit.
        if (count($cachedImages) >= $this->_options['numberOfAllowedFilters']) {
            throw new ErrorException('You cannot create anymore resizes/manipulations at this time.');
        }
    }

    protected function _parsePlaceholders($file)
    {
        if (! empty($this->_options['placeholders'])) {

        }
    }
}