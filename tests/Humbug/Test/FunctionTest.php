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
        $this->assertEquals('Probably Okay', self::$result['rating']);
    }

    public function testTlsCompression()
    {
        $this->assertFalse(self::$result['tls_compression_supported']);
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
    
}