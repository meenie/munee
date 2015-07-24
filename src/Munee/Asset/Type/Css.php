<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee\Asset\Type;

use Munee\Utils;
use Munee\Asset\Type;
use lessc;
use Leafo\ScssPhp\Compiler as ScssCompiler;
use Sabberworm\CSS\Settings as CssSettings;
use Sabberworm\CSS\Parser as CssParser;
use Sabberworm\CSS\Property\Import;
use Sabberworm\CSS\Value\URL;

/**
 * Handles CSS
 *
 * @author Cody Lundquist
 */
class Css extends Type
{
    /**
     * Stores the Request options for this Asset Type
     *
     * @var array
     */
    protected $options = array(
        'lessifyAllCss' => false,
        'scssifyAllCss' => false
    );

    /**
     * Set additional headers just for CSS
     */
    public function getHeaders()
    {
        $this->response->headerController->headerField('Content-Type', 'text/css');
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
    protected function checkCache($originalFile, $cacheFile)
    {
        if (! $ret = parent::checkCache($originalFile, $cacheFile)) {
            return false;
        }

        if (Utils::isSerialized($ret, $ret)) {
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
     * Overriding to run the file through LESS/SCSS if need be.
     * Also want to fix any relative paths for images.
     *
     * @param string $originalFile
     * @param string $cacheFile
     *
     * @throws CompilationException
     */
    protected function beforeFilter($originalFile, $cacheFile)
    {
        if ($this->isLess($originalFile)) {
            $less = new lessc();
            try {
                $compiledLess = $less->cachedCompile($originalFile);
            } catch (\Exception $e) {
                throw new CompilationException('Error in LESS Compiler', 0, $e);
            }
            $compiledLess['compiled'] = $this->fixRelativePaths($compiledLess['compiled'], $originalFile);
            file_put_contents($cacheFile, serialize($compiledLess));
        } elseif ($this->isScss($originalFile)) {
            $scss = new ScssCompiler();
            $scss->addImportPath(pathinfo($originalFile, PATHINFO_DIRNAME));
            try {
                $compiled = $scss->compile(file_get_contents($originalFile));
            } catch (\Exception $e) {
                throw new CompilationException('Error in SCSS Compiler', 0, $e);
            }

            $content = compact('compiled');
            $parsedFiles = $scss->getParsedFiles();
            $parsedFiles[] = $originalFile;
            foreach ($parsedFiles as $file) {
                $content['files'][$file] = filemtime($file);
            }

            $content['compiled'] = $this->fixRelativePaths($content['compiled'], $originalFile);
            file_put_contents($cacheFile, serialize($content));
        } else {
            $content = file_get_contents($originalFile);
            file_put_contents($cacheFile, $this->fixRelativePaths($content, $originalFile));
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
    protected function afterGetFileContent($content)
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
    protected function isLess($file)
    {
        return 'less' == pathinfo($file, PATHINFO_EXTENSION) || $this->options['lessifyAllCss'];
    }

    /**
     * Check if it's a SCSS file or if we should run all CSS through SCSS
     *
     * @param string $file
     *
     * @return boolean
     */
    protected function isScss($file)
    {
        return 'scss' == pathinfo($file, PATHINFO_EXTENSION) || $this->options['scssifyAllCss'];
    }

    /**
     * Use CssParser to go through and convert all relative paths to absolute
     *
     * @param string $content
     * @param string $originalFile
     *
     * @return string
     */
    protected function fixRelativePaths($content, $originalFile)
    {
        $cssParserSettings = CssSettings::create()->withMultibyteSupport(false);
        $cssParser = new CssParser($content, $cssParserSettings);
        $cssDocument = $cssParser->parse();

        $cssBlocks = $cssDocument->getAllValues();

        $this->fixUrls($cssBlocks, $originalFile);

        return $cssDocument->render();
    }

    /**
     * Recursively go through the CSS Blocks and update relative links to absolute
     *
     * @param $cssBlocks
     * @param $originalFile
     * @throws CompilationException
     */
    protected function fixUrls($cssBlocks, $originalFile) {
        foreach ($cssBlocks as $cssBlock) {
            if ($cssBlock instanceof Import) {
                $this->fixUrls($cssBlock->atRuleArgs(), $originalFile);
            } else {
                if (! $cssBlock instanceof URL) {
                    continue;
                }

                $originalUrl = $cssBlock->getURL()->getString();
                $url = $this->relativeToAbsolute($originalUrl, $originalFile);
                $cssBlock->getURL()->setString($url);
            }
        }
    }

    /**
     * Convert the passed in url from relative to absolute taking care not to convert urls that are already
     * absolute, point to a different domain/protocol, or are base64 encoded "data:image" strings.
     * It will also prefix a url with the munee dispatcher file URL if *not* using URL Rewrites (.htaccess).
     *
     * @param $originalUrl
     * @param $originalFile
     *
     * @return string
     * @throws CompilationException
     */
    protected function relativeToAbsolute($originalUrl, $originalFile)
    {
        $webroot = $this->request->webroot;
        $url = $originalUrl;
        if (
            $originalUrl[0] !== '/' &&
            strpos($originalUrl, '://') === false &&
            strpos($originalUrl, 'data:image') === false
        ) {
            $basePath = SUB_FOLDER  . str_replace($webroot, '', dirname($originalFile));
            $basePathParts = array_reverse(array_filter(explode('/', $basePath)));
            $numOfRecursiveDirs = substr_count($originalUrl, '../');
            if ($numOfRecursiveDirs > count($basePathParts)) {
                throw new CompilationException(
                    'Error in stylesheet <strong>' . $originalFile .
                    '</strong>. The following URL goes above webroot: <strong>' . $url . '</strong>'
                );
            }

            $basePathParts = array_slice($basePathParts, $numOfRecursiveDirs);
            $basePath = implode('/', array_reverse($basePathParts));

            if (! empty($basePath) && $basePath[0] != '/') {
                $basePath = '/' . $basePath;
            }

            $url = $basePath . '/' . $originalUrl;
            $url = str_replace(array('../', './'), '', $url);
        }

        // If not using URL Rewrite
        if (! MUNEE_USING_URL_REWRITE) {
            $dispatcherUrl = MUNEE_DISPATCHER_FILE . '?files=';
            // If url is not already pointing to munee dispatcher file,
            // isn't pointing to another domain/protocol,
            // and isn't using data:image
            if (
                strpos($url, $dispatcherUrl) !== 0 &&
                strpos($originalUrl, '://') === false &&
                strpos($originalUrl, 'data:image') === false
            ) {
                $url = str_replace('?', '&', $url);
                $url = $dispatcherUrl . $url;
            }
        }

        return $url;
    }
}
