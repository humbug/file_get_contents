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

    public function testPassesHowsMySslChecks()
    {
        $result = humbug_get_contents('https://www.howsmyssl.com/a/check');
        $result = json_decode($result, true);
    }
    
}