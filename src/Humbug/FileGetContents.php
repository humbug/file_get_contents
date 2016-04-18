<?php
/**
 * Humbug
 *
 * @category   Humbug
 * @package    Humbug
 * @copyright  Copyright (c) 2015 Pádraic Brady (http://blog.astrumfutura.com)
 * @license    https://github.com/padraic/file_get_contents/blob/master/LICENSE New BSD License
 */

namespace Humbug;

use Composer\CaBundle\CaBundle;

/**
 * This is largely extracted from the Composer Installer where originally implemented.
 */
class FileGetContents
{

    protected $options = array('http' => array());

    protected static $lastResponseHeaders;

    protected static $nextRequestHeaders;

    public function __construct()
    {
        $this->checkConfig();
        $options = $this->getTlsStreamContextDefaults(null);
        $this->options = array_replace_recursive($this->options, $options);
    }

    public function get($filename, $context = null)
    {
        $context = $this->getStreamContext($filename);
        self::setHttpHeaders($context);
        $result = file_get_contents($filename, null, $context);
        self::setLastResponseHeaders($http_response_header);
        return $result;
    }

    public static function setLastResponseHeaders($headers)
    {
        self::$lastResponseHeaders = $headers;
    }

    public static function getLastResponseHeaders()
    {
        return self::$lastResponseHeaders;
    }
    
    public static function setNextRequestHeaders(array $headers)
    {
        self::$nextRequestHeaders = $headers;
    }

    public static function hasNextRequestHeaders()
    {
        return !empty(self::$nextRequestHeaders);
    }

    public static function getNextRequestHeaders()
    {
        $return = self::$nextRequestHeaders;
        self::$nextRequestHeaders = null;
        return $return;
    }

    public static function setHttpHeaders($context)
    {
        $headers = self::getNextRequestHeaders();
        if (!empty($headers)) {
            $options = stream_context_get_options($context);
            if (!isset($options['http'])) {
                $options['http'] = array('header'=>array());
            } elseif (!isset($options['http']['header'])) {
                $options['http']['header'] = array();
            } elseif (is_string($options['http']['header'])) {
                $options['http']['header'] = explode("\r\n", $options['http']['header']);
            }
            $headers = empty($options['http']['headers']) ? $headers : array_merge($options['http']['headers'], $headers);
            stream_context_set_option(
                $context,
                'http',
                'header',
                $headers
            );
        }
        return $context;
    }

    protected function checkConfig()
    {   
        if (!extension_loaded('openssl')) {
            throw new \RuntimeException(
                'The openssl extension is not loaded but is required for secure HTTPS connections'
            );
        }
    }

    protected function getStreamContext($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (PHP_VERSION_ID < 50600) {
            $this->options['ssl']['CN_match'] = $host;
            $this->options['ssl']['SNI_server_name'] = $host;
        }
        return $this->getMergedStreamContext($url);
    }

    protected function getTlsStreamContextDefaults($cafile)
    {
        $ciphers = implode(':', array(
            'ECDHE-RSA-AES128-GCM-SHA256',
            'ECDHE-ECDSA-AES128-GCM-SHA256',
            'ECDHE-RSA-AES256-GCM-SHA384',
            'ECDHE-ECDSA-AES256-GCM-SHA384',
            'DHE-RSA-AES128-GCM-SHA256',
            'DHE-DSS-AES128-GCM-SHA256',
            'kEDH+AESGCM',
            'ECDHE-RSA-AES128-SHA256',
            'ECDHE-ECDSA-AES128-SHA256',
            'ECDHE-RSA-AES128-SHA',
            'ECDHE-ECDSA-AES128-SHA',
            'ECDHE-RSA-AES256-SHA384',
            'ECDHE-ECDSA-AES256-SHA384',
            'ECDHE-RSA-AES256-SHA',
            'ECDHE-ECDSA-AES256-SHA',
            'DHE-RSA-AES128-SHA256',
            'DHE-RSA-AES128-SHA',
            'DHE-DSS-AES128-SHA256',
            'DHE-RSA-AES256-SHA256',
            'DHE-DSS-AES256-SHA',
            'DHE-RSA-AES256-SHA',
            'AES128-GCM-SHA256',
            'AES256-GCM-SHA384',
            'AES128-SHA256',
            'AES256-SHA256',
            'AES128-SHA',
            'AES256-SHA',
            'AES',
            'CAMELLIA',
            'DES-CBC3-SHA',
            '!aNULL',
            '!eNULL',
            '!EXPORT',
            '!DES',
            '!RC4',
            '!MD5',
            '!PSK',
            '!aECDH',
            '!EDH-DSS-DES-CBC3-SHA',
            '!EDH-RSA-DES-CBC3-SHA',
            '!KRB5-DES-CBC3-SHA',
            '!ADH'
        ));

        $options = array(
            'ssl' => array(
                'ciphers' => $ciphers,
                'verify_peer' => true,
                'verify_depth' => 7,
                'SNI_enabled' => true,
            )
        );

        if (!$cafile) {
            $cafile = CaBundle::getSystemCaRootBundlePath();
        }
        if (is_dir($cafile)) {
            $options['ssl']['capath'] = $cafile;
        } elseif ($cafile) {
            $options['ssl']['cafile'] = $cafile;
        } else {
            throw new \RuntimeException('A valid cafile could not be located locally.');
        }

        if (version_compare(PHP_VERSION, '5.4.13') >= 0) {
            $options['ssl']['disable_compression'] = true;
        }

        return $options;
    }

    /**
     * function copied from Composer\Util\StreamContextFactory::getContext
     *
     * This function is part of Composer.
     *
     * (c) Nils Adermann <naderman@naderman.de>
     *     Jordi Boggiano <j.boggiano@seld.be>
     *
     * @param string $url URL the context is to be used for
     * @return resource Default context
     * @throws \\RuntimeException if https proxy required and OpenSSL uninstalled
     */
    protected function getMergedStreamContext($url)
    {
        $options = $this->options;

        // Handle system proxy
        if (!empty($_SERVER['HTTP_PROXY']) || !empty($_SERVER['http_proxy'])) {
            // Some systems seem to rely on a lowercased version instead...
            $proxy = parse_url(!empty($_SERVER['http_proxy']) ? $_SERVER['http_proxy'] : $_SERVER['HTTP_PROXY']);
        }

        if (!empty($proxy)) {
            $proxyURL = isset($proxy['scheme']) ? $proxy['scheme'] . '://' : '';
            $proxyURL .= isset($proxy['host']) ? $proxy['host'] : '';

            if (isset($proxy['port'])) {
                $proxyURL .= ":" . $proxy['port'];
            } elseif ('http://' == substr($proxyURL, 0, 7)) {
                $proxyURL .= ":80";
            } elseif ('https://' == substr($proxyURL, 0, 8)) {
                $proxyURL .= ":443";
            }

            // http(s):// is not supported in proxy
            $proxyURL = str_replace(array('http://', 'https://'), array('tcp://', 'ssl://'), $proxyURL);

            if (0 === strpos($proxyURL, 'ssl:') && !extension_loaded('openssl')) {
                throw new \RuntimeException('You must enable the openssl extension to use a proxy over https');
            }

            $options['http'] = array(
                'proxy'           => $proxyURL,
            );

            // enabled request_fulluri unless it is explicitly disabled
            switch (parse_url($url, PHP_URL_SCHEME)) {
                case 'http': // default request_fulluri to true
                    $reqFullUriEnv = getenv('HTTP_PROXY_REQUEST_FULLURI');
                    if ($reqFullUriEnv === false || $reqFullUriEnv === '' || (strtolower($reqFullUriEnv) !== 'false' && (bool) $reqFullUriEnv)) {
                        $options['http']['request_fulluri'] = true;
                    }
                    break;
                case 'https': // default request_fulluri to true
                    $reqFullUriEnv = getenv('HTTPS_PROXY_REQUEST_FULLURI');
                    if ($reqFullUriEnv === false || $reqFullUriEnv === '' || (strtolower($reqFullUriEnv) !== 'false' && (bool) $reqFullUriEnv)) {
                        $options['http']['request_fulluri'] = true;
                    }
                    break;
            }


            if (isset($proxy['user'])) {
                $auth = urldecode($proxy['user']);
                if (isset($proxy['pass'])) {
                    $auth .= ':' . urldecode($proxy['pass']);
                }
                $auth = base64_encode($auth);

                $options['http']['header'] = "Proxy-Authorization: Basic {$auth}\r\n";
            }
        }

        return stream_context_create($options);
    }

    /**
     * @deprecated
     */
    public static function getSystemCaRootBundlePath()
    {
        return CaBundle::getSystemCaRootBundlePath();
    }

    /**
     * @deprecated
     */
    protected static function validateCaFile($contents) {
        // assume the CA is valid if php is vunerable to
        // https://www.sektioneins.de/advisories/advisory-012013-php-openssl_x509_parse-memory-corruption-vulnerability.html
        if (!CaBundle::isOpensslParseSafe()) {
            return !empty($contents);
        }

        return (bool) openssl_x509_parse($contents);
    }
}
