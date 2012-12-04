<?php

namespace munee;

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
}