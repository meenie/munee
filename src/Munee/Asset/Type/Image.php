<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee\Asset\Type;

use Munee\ErrorException;
use Munee\Asset\Type;
use Munee\Utils;

/**
 * Handles Images
 *
 * @author Cody Lundquist
 */
class Image extends Type
{
    /**
     * Stores the Request options for this Asset Type
     *
     * @var array
     */
    protected $options = array(
        // How many filters can be done within the `allowedFiltersTimeLimit`
        'numberOfAllowedFilters' => 3,
        // Number of seconds - default is 5 minutes
        'allowedFiltersTimeLimit' => 300,
        // Should the referrer be checked for security
        'checkReferrer' => true,
        // Use a placeholder for missing images?
        'placeholders' => false,
        'maxAllowedResizeWidth' => 1920,
        'maxAllowedResizeHeight' => 1080,
        /**
         * Can easily change which image processor to use. Values can be:
         * GD - Default
         * Imagick
         * Gmagick
         */
        'imageProcessor' => 'GD'
    );

    /**
     * Stores the specific placeholder that will be used for this requested asset, if any.
     *
     * @var bool
     */
    protected $placeholder = false;

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
    protected function checkCache($originalFile, $cacheFile)
    {
        if (! $return = parent::checkCache($originalFile, $cacheFile)) {
            /**
             * If using the placeholder when the original file doesn't exist
             * and it has already been cached, return the cached contents.
             * Also make sure the placeholder hasn't been modified since being cached.
             */
            $this->placeholder = $this->parsePlaceholders($originalFile);
            if (! file_exists($originalFile) && $this->placeholder) {
                return parent::checkCache($this->placeholder, $cacheFile);
            }
        }

        return $return;
    }

    /**
     * Overwrite the _setupFile function so placeholder images can be shown instead of broken images
     *
     *
     * @param string $originalFile
     * @param string $cacheFile
     */
    protected function setupFile($originalFile, $cacheFile)
    {
        if (count($this->filters) > 0) {
            $this->checkNumberOfAllowedFilters($cacheFile);
            if ($this->options['checkReferrer']) {
                $this->checkReferrer();
            }
        }

        if (! file_exists($originalFile)) {
            // If we are using a placeholder and that exists, use it!
            if ($this->placeholder && file_exists($this->placeholder)) {
                $originalFile = $this->placeholder;
            }
        }

        parent::setupFile($originalFile, $cacheFile);
    }

    /**
     * Set additional headers just for an Image
     */
    public function getHeaders()
    {
        switch ($this->request->ext) {
            case 'jpg':
            case 'jpeg':
                $this->response->headerController->headerField('Content-Type', 'image/jpg');
                break;
            case 'png':
                $this->response->headerController->headerField('Content-Type', 'image/png');
                break;
            case 'gif':
                $this->response->headerController->headerField('Content-Type', 'image/gif');
                break;
        }
    }

    /**
     * Check to make sure the referrer domain is the same as the domain where the image exists.
     *
     * @throws ErrorException
     */
    protected function checkReferrer()
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
     * @param string $cacheFile
     *
     * @throws ErrorException
     */
    protected function checkNumberOfAllowedFilters($cacheFile)
    {
        $pathInfo = pathinfo($cacheFile);
        $fileNameHash = preg_replace('%-.*$%', '', $pathInfo['filename']);
        // Grab all the similar files
        $cachedImages = glob($pathInfo['dirname'] . DS . $fileNameHash . '*');

        if (! is_array($cachedImages)) {
            $cachedImages = array();
        }

        // Loop through and remove the ones that are older than the time limit
        foreach ($cachedImages as $k => $image) {
            if (filemtime($image) < time() - $this->options['allowedFiltersTimeLimit']) {
                unset($cachedImages[$k]);
            }
        }

        // Check and see if we've reached the maximum allowed resizes within the current time limit.
        if (count($cachedImages) >= $this->options['numberOfAllowedFilters']) {
            throw new ErrorException('You cannot create anymore resizes/manipulations at this time.');
        }
    }

    /**
     * Checks the 'placeholders' Request Option to see if placeholders should be used for missing images
     * It uses a wildcard syntax (*) to see which placeholder should be used for a particular set of images.
     *
     * @param string $file
     *
     * @return boolean|string
     *
     * @throws ErrorException
     */
    protected function parsePlaceholders($file)
    {
        $ret = false;
        if (! empty($this->options['placeholders'])) {
            // If it's a string, use the image for all missing images.
            if (is_string($this->options['placeholders'])) {
                $this->options['placeholders'] = array('*' => $this->options['placeholders']);
            }

            foreach ($this->options['placeholders'] as $path => $placeholder) {
                // Setup path for regex
                $escapedWebroot = preg_quote($this->request->webroot);
                $regex = '^' . $escapedWebroot . str_replace(array('*', $this->request->webroot), array('.*?', ''), $path) . '$';
                if (preg_match("%{$regex}%", $file)) {
                    if ('http' == substr($placeholder, 0, 4)) {
                        $ret = $this->getImageByUrl($placeholder);
                    } else {
                        $ret = $placeholder;
                    }
                    break;
                }
            }
        }

        return $ret;
    }

    /**
     * Grabs an image by URL from another server
     *
     * @param string $url
     *
     * @return string
     */
    protected function getImageByUrl($url)
    {
        $cacheFolder = MUNEE_CACHE . DS . 'placeholders';
        Utils::createDir($cacheFolder);
        $requestOptions = serialize($this->request->options);
        $originalFile = array_shift($this->request->files);

        $fileName = $cacheFolder . DS . md5($url) . '-' . md5($requestOptions . $originalFile);
        if (! file_exists($fileName)) {
            file_put_contents($fileName, file_get_contents($url));
        }

        return $fileName;
    }
}
