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
use RuntimeException;

if (function_exists('humbug_get_contents')) {
    throw new RuntimeException(
        'Function has already been defined'
    );
}

function humbug_get_contents($filename, $use_include_path = false, $context = null, $offset = -1, $maxlen = null)
{
    static $fileGetContents = null;
    if ('https' == parse_url($filename, PHP_URL_SCHEME) && PHP_VERSION_ID < 50600) {
        if (!isset($fileGetContents)) {
            $fileGetContents = new FileGetContents;
        }
        return $fileGetContents->get($filename, $context, $offset, $maxlen);
    }
    return file_get_contents($filename, $use_include_path, $context, $offset, $maxlen);
}