<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee;

/**
 * Munee Utils Class
 *
 * @author Cody Lundquist
 */
class Utils
{
    /**
     * Creates directories
     *
     * @param $dir
     *
     * @return bool
     * @throws ErrorException
     */
    public static function createDir($dir)
    {
        if (! is_dir($dir) && ! mkdir($dir, 0777, true)) {
            throw new ErrorException("The follow directory could not be made, please create it: {$dir}");
        }

        return true;
    }

    /**
     * Recursively remove a directory and all of it's contents
     *
     * @param $dir
     *
     * @throws ErrorException
     */
    public static function removeDir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ('.' != $object && '..' != $object) {
                    if ('dir' == filetype($dir . DS . $object)) {
                        static::removeDir($dir . DS . $object);
                    } else {
                        if (! unlink($dir . DS . $object)) {
                            throw new ErrorException(
                                'The following file could not be deleted: ' . $dir . DS . $object
                            );
                        }
                    }
                }
            }

            if (! rmdir($dir)) {
                throw new ErrorException("The following directory could not be deleted: {$dir}");
            }
        }
    }

    /**
     * Check to see if a string is unserializable
     *
     * @param $value
     * @param null $result
     *
     * @return boolean
     *
     * @see https://gist.github.com/1415653
     */
    public static function isSerialized($value, &$result = null)
    {
        // Bit of a give away this one
        if (! is_string($value)) {
            return false;
        }
        // Serialized FALSE, return TRUE. unserialize() returns FALSE on an
        // invalid string or it could return FALSE if the string is serialized
        // FALSE, eliminate that possibility.
        if ('b:0;' === $value) {
            $result = false;

            return true;
        }
        $length = strlen($value);
        $end = '';
        if (isset($value[0])) {
            switch ($value[0]) {
                case 's':
                    if ('"' !== $value[$length - 2]) {
                        return false;
                    }
                    break;
                case 'b':
                case 'i':
                case 'd':
                    // This looks odd but it is quicker than isset()ing
                    $end .= ';';
                case 'a':
                case 'O':
                    $end .= '}';
                    if (':' !== $value[1]) {
                        return false;
                    }
                    switch ($value[2]) {
                        case '0':
                        case '1':
                        case '2':
                        case '3':
                        case '4':
                        case '5':
                        case '6':
                        case '7':
                        case '8':
                        case '9':
                            break;
                        default:
                            return false;
                    }
                case 'N':
                    $end .= ';';
                    if ($value[$length - 1] !== $end[0]) {
                        return false;
                    }
                    break;
                default:
                    return false;
            }
        }
        if (($result = @unserialize($value)) === false) {
            $result = null;

            return false;
        }

        return true;
    }
}