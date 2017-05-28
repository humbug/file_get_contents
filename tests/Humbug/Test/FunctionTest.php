<?php

/*
 * This file is part of the Humbug package.
 *
 * (c) 2015 Pádraic Brady <padraic.brady@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Humbug\Test;

use PHPUnit\Framework\TestCase;

if (!class_exists('\PHPUnit\Framework\TestCase', true)) {
    class_alias('\PHPUnit_Framework_TestCase', 'TestCase');
}

/**
 * @coversNothing
 */
class FunctionTest extends TestCase
{
    private static $result;

    public function setup()
    {
        if (null === self::$result) {
            $result = humbug_get_contents('https://www.howsmyssl.com/a/check');
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
        humbug_set_headers(array('Accept-Language: da\r\n'));
        humbug_get_contents('http://padraic.github.io');
        $this->assertTrue(count(humbug_get_headers()) > 0);
    }

    public function testCanSetRequestHeaders()
    {
        humbug_set_headers(array(
            'Accept-Language: da',
            'User-Agent: Humbug'
        ));
        $out = humbug_get_contents('http://www.procato.com/my+headers/');
        $this->assertEquals(1, preg_match('%'.preg_quote('<th>Accept-Language</th><td>da</td>').'%', $out));
        $this->assertEquals(1, preg_match('%'.preg_quote('<th>User-Agent</th><td>Humbug</td>').'%', $out));
    }
}
