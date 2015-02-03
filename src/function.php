<?php
/**
 * Humbug
 *
 * @category   Humbug
 * @package    Humbug
 * @copyright  Copyright (c) 2015 Pádraic Brady (http://blog.astrumfutura.com)
 * @license    https://github.com/padraic/file_get_contents/blob/master/LICENSE New BSD License
 */

use Humbug\FileGetContents;

if (!function_exists('humbug_get_contents')) {
    function humbug_get_contents($filename, $use_include_path = false, $context = null) {
        static $fileGetContents = null;
        if ('https' == parse_url($filename, PHP_URL_SCHEME) && PHP_VERSION_ID < 50600) {
            if (!isset($fileGetContents)) {
                $fileGetContents = new FileGetContents;
            }
            return $fileGetContents->get($filename, $context, $offset, $maxlen);
        }
        return file_get_contents($filename, $use_include_path, $context);
    }
} else {
    throw new \RuntimeException(
        'Function has already been defined'
    );
}