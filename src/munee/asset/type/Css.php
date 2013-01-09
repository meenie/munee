<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\asset\type;

use munee\Utils;
use munee\asset\Type;
use lessc;

/**
 * Handles CSS
 *
 * @author Cody Lundquist
 */
class Css extends Type
{
    /**
     * @var array
     */
    protected $_options = array(
        'lessifyAllCss' => false
    );

    /**
     * Set additional headers just for CSS
     */
    public function getHeaders()
    {
        header("Content-Type: text/css");
    }

    /**
     * Checks to see if cache exists and is the latest, if it does, return it
     * It also checks to see if this is LESS cache and makes sure all imported files are the latest
     *
     * @param string $originalFile
     * @param string $cacheFile
     *
     * @return bool|string|array
     */
    protected function _checkCache($originalFile, $cacheFile)
    {
        if (! $ret = parent::_checkCache($originalFile, $cacheFile)) {
            return false;
        }

        if ($this->_isLess($cacheFile)) {
            if (! Utils::isSerialized($ret, $ret)) {
                // If for some reason the file isn't serialized, just return the content
                return $ret;
            }
            // Go through each file and make sure none of them have changed
            foreach ($ret['files'] as $file => $lastModified) {
                if (filemtime($file) > $lastModified) {
                    return false;
                }
            }

            $ret = serialize($ret);
        }

        return $ret;
    }

    /**
     * Callback method called before filters are run
     *
     * Overriding to run the file through LESS if need be.
     * Also want to fix any relative paths for images.
     *
     * @param string $originalFile
     * @param string $cacheFile
     */
    protected function _beforeFilter($originalFile, $cacheFile)
    {
        if ($this->_isLess($originalFile)) {
            $less = new lessc();
            $compiledLess = $less->cachedCompile($originalFile);
            $compiledLess['compiled'] = $this->_fixRelativeImagePaths($compiledLess['compiled'], $originalFile);
            file_put_contents($cacheFile, serialize($compiledLess));
        } else {
            $content = file_get_contents($originalFile);
            file_put_contents($cacheFile, $this->_fixRelativeImagePaths($content, $originalFile));
        }
    }

    /**
     * Callback method called after the content is collected and/or cached
     * Check if the content is serialized.  If it is, we have LESS cache
     * and we want to return whats in the `compiled` array key
     *
     * @param string $content
     *
     * @return string
     */
    protected function _afterGetFileContent($content)
    {
        if (Utils::isSerialized($content, $content)) {
            $content = $content['compiled'];
        }

        return $content;
    }

    /**
     * Check if it's a LESS file or if we should run all CSS through LESS
     *
     * @param string $file
     *
     * @return boolean
     */
    protected function _isLess($file)
    {
        return 'less' == pathinfo($file, PATHINFO_EXTENSION) || $this->_options['lessifyAllCss'];
    }

    /**
     * Fixes relative paths to absolute paths
     *
     * @param $content
     * @param $originalFile
     *
     * @return string
     */
    protected function _fixRelativeImagePaths($content, $originalFile)
    {
        $regEx = '%(background(?:-image)?:.*?url[\\s]*\()[\\s\'"]*(\.\.[^\\)\'"]*)[\\s\'"]*(\\)[\\s]*)%';

        $changedContent = preg_replace_callback($regEx, function($match) use ($originalFile) {
            $basePath = str_replace(WEBROOT, '', dirname($originalFile)) . '/' . trim($match[2]);
            $count = 1;
            while ($count > 0) {
                $basePath = preg_replace('%\\w+/\\.\\./%', '', $basePath, -1, $count);
            }

            return $match[1] . $basePath . $match[3];
        }, $content);

        if (null !== $changedContent) {
            $content = $changedContent;
        }

        return $content;
    }
}