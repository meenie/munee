<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2013
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace Munee\Cases;

use Munee\Utils;

/**
 * Tests for the \Munee\Utils Class
 *
 * @author Cody Lundquist
 */
class UtilsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Can Create/Remove Directories?
     */
    public function testCreateRemoveDirectories()
    {
        $testDir = WEBROOT . DS . 'test' . DS . 'sub_dir';
        $this->assertFalse(is_dir($testDir));

        Utils::createDir($testDir);
        $this->assertTrue(is_dir($testDir));

        Utils::removeDir(WEBROOT . DS . 'test');
    }

    /**
     * Lets test all types of serialisation!
     */
    public function testIsSerialized()
    {
        $notSerializedString = 'nope';
        $this->assertFalse(Utils::isSerialized($notSerializedString));

        $notString = 42;
        $this->assertFalse(Utils::isSerialized($notString));

        $wrongQuotesSerial = "s:1:'foo';";
        $this->assertFalse(Utils::isSerialized($wrongQuotesSerial));
        $malformedStringSerial = 's:1:"foo";';
        $this->assertFalse(Utils::isSerialized($malformedStringSerial));
        $correctStringSerial = 's:3:"foo";';
        $this->assertTrue(Utils::isSerialized($correctStringSerial));

        $malformedArraySerial = 'a:1:{s:1:"foo";}';
        $this->assertFalse(Utils::isSerialized($malformedArraySerial));
        $correctArraySerial = 'a:1:{s:3:"foo";s:3:"bar";}';
        $this->assertTrue(Utils::isSerialized($correctArraySerial));

        $malformedObjectSerial = 'O8:"stdClass":0:{}';
        $this->assertFalse(Utils::isSerialized($malformedObjectSerial));
        $malformedObjectSerial = 'O:::"stdClass":0:{}';
        $this->assertFalse(Utils::isSerialized($malformedObjectSerial));
        $correctObjectSerial = 'O:8:"stdClass":0:{}';
        $this->assertTrue(Utils::isSerialized($correctObjectSerial));

        $malformedNullSerial = 'N:';
        $this->assertFalse(Utils::isSerialized($malformedNullSerial));
        $correctNullSerial = 'N;';
        $this->assertTrue(Utils::isSerialized($correctNullSerial));

        $isTrue = false;
        $serializedTrue = 'b:1;';
        $this->assertTrue(Utils::isSerialized($serializedTrue, $isTrue));
        $this->assertTrue($isTrue);

        $isFalse = false;
        $serializedFalse = 'b:0;';
        $this->assertTrue(Utils::isSerialized($serializedFalse, $isFalse));
        $this->assertFalse($isFalse);

        $checkArray = array('foo' => 'bar');
        $testArray = array();
        Utils::isSerialized('a:1:{s:3:"foo";s:3:"bar";}', $testArray);
        $this->assertSame($checkArray, $testArray);
    }
}
