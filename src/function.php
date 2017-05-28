<?php

/*
 * This file is part of the Humbug package.
 *
 * (c) 2015 Pádraic Brady <padraic.brady@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Humbug\FileGetContents;

if (!function_exists('humbug_get_contents')) {
    function humbug_get_contents($filename, $use_include_path = false, $context = null)
    {
        static $fileGetContents = null;

        if ('https' == parse_url($filename, PHP_URL_SCHEME) && PHP_VERSION_ID < 50600) {
            if (!isset($fileGetContents)) {
                $fileGetContents = new FileGetContents();
            }

            return $fileGetContents->get($filename, $context);
        } elseif (FileGetContents::hasNextRequestHeaders()) {
            if ($context === null) {
                $context = stream_context_create();
            }
            $context = FileGetContents::setHttpHeaders($context);
        }
        $return = file_get_contents($filename, $use_include_path, $context);
        if (isset($http_response_header)) {
            FileGetContents::setLastResponseHeaders($http_response_header);
        }

        return $return;
    }
}

if (!function_exists('humbug_get_headers')) {
    function humbug_get_headers()
    {
        return FileGetContents::getLastResponseHeaders();
    }
}

if (!function_exists('humbug_set_headers')) {
    function humbug_set_headers(array $headers)
    {
        FileGetContents::setNextRequestHeaders($headers);
    }
}
