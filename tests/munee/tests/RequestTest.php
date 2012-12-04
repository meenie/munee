<?php
/**
 * Munee: Optimising Your Assets
 *
 * @copyright Cody Lundquist 2012
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace munee\tests;

use munee\Request;

/**
 * Tests for the \munee\Request Class
 *
 * @author Cody Lundquist
 */
class RequestTest extends \PHPUnit_Framework_TestCase
{
    public function testNoQueryString()
    {
        $this->setExpectedException('munee\ErrorException');
        new Request();
    }

    public function testWithQueryString()
    {
        $_GET['minify'] = 'minify/';
        $_GET['ext'] = 'css';
        $_GET['files'] = '/css/foo.css';

        $request = new Request();

        $this->assertEquals(true, $request->minify);
        $this->assertEquals('css', $request->ext);
        $this->assertEquals(array('/css/foo.css'), $request->files);

        $_GET['minify'] = '';
        $_GET['ext'] = 'js';
        $_GET['files'] = '/js/foo.js,/js/bar.js';

        $request = new Request();

        $this->assertEquals(false, $request->minify);
        $this->assertEquals('js', $request->ext);
        $this->assertEquals(array('/js/foo.js', '/js/bar.js'), $request->files);
    }
}