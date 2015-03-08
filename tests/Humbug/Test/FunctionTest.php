<?php
/**
 * Humbug
 *
 * @category   Humbug
 * @package    Humbug
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2015 Pádraic Brady (http://blog.astrumfutura.com)
 * @license    https://github.com/padraic/file_get_contents/blob/master/LICENSE New BSD License
 */

namespace Humbug\Test;

use Humbug\FileGetContents;

class FunctionTest extends \PHPUnit_Framework_TestCase
{

    private static $result;

    public function setup()
    {
        if (PHP_VERSION_ID >= 50600) {
            $this->markTestSkipped('Under PHP 5.6+ no requests will be modified.');
        }
        if (null === self::$result) {
            $result = humbug_get_contents('https://howsmyssl.com/a/check');
            self::$result = json_decode($result, true);
        }
    }

    public function teardown()
    {
        @unlink(sys_get_temp_dir() . '/humbug.tmp');
    }

    public function testRating()
    {
        $this->assertEquals('Improvable', self::$result['rating']);
    }

    public function testTlsCompression()
    {
        $this->assertFalse(self::$result['tls_compression_supported']);
    }

    public function testSslNotUsed()
    {
        $this->assertEquals(stripos(self::$result['tls_version'], 'tls 1.'), 0);
    }

    public function testBeastVulnerability()
    {
        $this->assertFalse(self::$result['beast_vuln']);
    }

    public function testInsecureCipherSuites()
    {
        $this->assertEmpty(self::$result['insecure_cipher_suites']);
    }

    public function testUnknownCipherSuites()
    {
        $this->assertFalse(self::$result['unknown_cipher_suite_supported']);
    }

    public function testFileGetContentsWillPassThrough()
    {
        file_put_contents(sys_get_temp_dir() . '/humbug.tmp', ($expected = uniqid()), LOCK_EX);
        $this->assertEquals(file_get_contents(sys_get_temp_dir() . '/humbug.tmp'), $expected);
    }

    public function testCanGetResponseHeaders()
    {
        humbug_set_headers(['Accept-Language: da\r\n']);
        humbug_get_contents('http://padraic.github.io');
        $this->assertTrue(count(humbug_get_headers()) > 0);
    }

    public function testCanSetRequestHeaders()
    {
        humbug_set_headers(array(
            'Accept-Language: da',
            'User-Agent: Humbug'
        ));
        $out = humbug_get_contents('http://myhttp.info/');
        $this->assertEquals(1, preg_match('%'.preg_quote('<td>Accept language</td><td>da</td>').'%', $out));
        $this->assertEquals(1, preg_match('%'.preg_quote('<td>User agent</td><td>Humbug</td>').'%', $out));
    }
    
}