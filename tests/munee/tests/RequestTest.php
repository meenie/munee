<?php
namespace munee\tests;

require dirname(dirname(dirname(__DIR__))) .'/config/bootstrap.php';

use munee\Request;

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
        $_GET['type'] = 'css';
        $_GET['files'] = '/css/foo.css';

        $request = new Request();

        $this->assertEquals(true, $request->minify);
        $this->assertEquals('css', $request->type);
        $this->assertEquals(array('/css/foo.css'), $request->files);

        $_GET['minify'] = '';
        $_GET['type'] = 'js';
        $_GET['files'] = '/js/foo.js,/js/bar.js';

        $request = new Request();

        $this->assertEquals(false, $request->minify);
        $this->assertEquals('js', $request->type);
        $this->assertEquals(array('/js/foo.js', '/js/bar.js'), $request->files);
    }
}