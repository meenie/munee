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
use scssc;

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
return false;
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
     * Overriding to run the file through LESS if need be.
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
            $compiledLess['compiled'] = $this->fixRelativeImagePaths($compiledLess['compiled'], $originalFile);
            file_put_contents($cacheFile, serialize($compiledLess));
        } elseif ($this->isScss($originalFile)) {
            $scss = new scssc();
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

            file_put_contents($cacheFile, serialize($content));
        } else {
            $content = file_get_contents($originalFile);

$content_orig = $content;
$fh = fopen(MUNEE_CACHE.'/stat.txt','a');
$start = microtime(true);
            $content = self::expandCssImports($content_orig,$originalFile);
$end = microtime(true);
$time = $end - $start;
fwrite($fh,"
expandCssImports Stats
START: $start
END:   $end
DIFF:  $time\n");

$content = $content_orig;
$start = microtime(true);
            $content = self::parseImports($content,$originalFile);
$end = microtime(true);
$time = $end - $start;
fwrite($fh,"
parseImports Stats
START: $start
END:   $end
DIFF:  $time\n");
fclose($fh);
            file_put_contents($cacheFile, $this->fixRelativeImagePaths($content, $originalFile));
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
     * Fixes relative paths to absolute paths
     *
     * @param $content
     * @param $originalFile
     *
     * @return string
     *
     * @throws CompilationException
     */
    protected function fixRelativeImagePaths($content, $originalFile)
    {
        $regEx = '%(url[\\s]*\\()[\\s\'"]*([^\\)\'"]*)[\\s\'"]*(\\))%';

        $webroot = $this->request->webroot;
        $changedContent = preg_replace_callback($regEx, function ($match) use ($originalFile, $webroot) {
            $filePath = trim($match[2]);
            // Skip conversion if the first character is a '/' since it's already an absolute path
            if ($filePath[0] !== '/') {
                $basePath = SUB_FOLDER  . str_replace($webroot, '', dirname($originalFile));
                $basePathParts = array_reverse(array_filter(explode('/', $basePath)));
                $numOfRecursiveDirs = substr_count($filePath, '../');
                if ($numOfRecursiveDirs > count($basePathParts)) {
                    throw new CompilationException(
                        'Error in stylesheet <strong>' . $originalFile .
                        '</strong>. The following URL goes above webroot: <strong>' . $filePath .
                        '</strong>'
                    );
                }

                $basePathParts = array_slice($basePathParts, $numOfRecursiveDirs);
                $basePath = implode('/', array_reverse($basePathParts));

                if (! empty($basePath) && $basePath[0] != '/') {
                    $basePath = '/' . $basePath;
                }

                $filePath = $basePath . '/' . $filePath;
                $filePath = str_replace(array('../', './'), '', $filePath);
            }

            return $match[1] . $filePath . $match[3];
        }, $content);

        if (null !== $changedContent) {
            $content = $changedContent;
        }

        return $content;
    }

    private static function parseImports($content,$origFile)
    {
        $dir   = dirname($origFile);
        // matches any type of import rule
        preg_match_all("/(@import[^;].+?);/im",$content,$imports,PREG_PATTERN_ORDER);
        if(count($imports))
        {
            foreach($imports[1] as $i => $item)
            {
                if( false !== ($start = strpos($item,'url')))
                {
                    $paren1  = strpos($item,'(',$start)+1;
                    $paren2  = strpos($item,')',$paren1);
                    $url     = substr($item,$paren1,$paren2-$paren1);
                    $url     = str_replace(array('"',"'"),'',$url);
                    $rest    = substr($item,$paren2+1);

                    if(strlen($rest)>1) $media = $rest;
                    else                $media = NULL;

                    // get the file
                    $file = $dir.'/'.$url;
                    if (is_file($file))
                    {
                        $string = file_get_contents($file);
                        $newDir = dirname($file);
                        // replace imports in current file
                        $string = self::parseImports($string,$file);
                        // replace urls
                        if ($newDir !== $dir) {
                            $tmp = $dir.'/';
                            if (substr($newDir, 0, strlen($tmp)) === $tmp) {
                                $string = preg_replace('#\burl\(["\']?(?=[.\w])(?!\w+:)#', '$0' . substr($newDir, strlen($tmp)) . '/', $string);
                            }
                        }
                        if($media)
                        {
                            $string = '@media '.trim($media).' {'
                                    . $string
                                    . '}';
                        }
                        $content = str_replace($imports[0][$i],$string,$content);
                    }
                }
            }
        }
        return $content;
    }

    private static function expandCssImports($content,$origFile)
    {
        $dir = dirname($origFile);
        return
            preg_replace_callback(
                '#@import\s+(?:url)?[(\'"]+(.+)[)\'"]+;#U',
                function($m) use ($dir) {
                    $file = $dir.'/'.$m[1];
                    if (!is_file($file)) { return $m[0]; } // no such file
                    $string = file_get_contents($file);
                    $newDir = dirname($file);
                    $s = self::expandCssImports($string,$file);
                    if ($newDir !== $dir) {
                        $tmp = $dir.'/';
                        if (substr($newDir, 0, strlen($tmp)) === $tmp) {
                            $string = preg_replace('#\burl\(["\']?(?=[.\w])(?!\w+:)#', '$0' . substr($newDir, strlen($tmp)) . '/', $string);
                        } elseif (strpos($string, 'url(') !== FALSE) {
                            return $m[0];
                        }
                    }
                    return $string;
                },
                $content
            );
    }   // end function expandCssImports()

}